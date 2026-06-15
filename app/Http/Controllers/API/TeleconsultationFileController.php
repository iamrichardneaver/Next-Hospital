<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Teleconsultation;
use App\Models\TeleconsultationFile;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TeleconsultationFileController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:teleconsultation.files.view')->only(['index', 'statistics']);
        $this->middleware('permission:teleconsultation.files.upload')->only(['store']);
        $this->middleware('permission:teleconsultation.files.download')->only(['download']);
        $this->middleware('permission:teleconsultation.files.delete')->only(['destroy']);
        $this->middleware('permission:teleconsultation.files.consent')->only(['giveConsent', 'revokeConsent']);
    }

    /**
     * Get files for a teleconsultation.
     */
    public function index(Request $request, Teleconsultation $teleconsultation): JsonResponse
    {
        $query = $teleconsultation->sharedFiles()->with('uploader');

        // Filter by category
        if ($request->has('category')) {
            $query->byCategory($request->category);
        }

        // Filter by shared with patient
        if ($request->has('shared_with_patient')) {
            $query->where('is_shared_with_patient', $request->shared_with_patient);
        }

        $files = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $files
        ]);
    }

    /**
     * Upload a file for teleconsultation.
     */
    public function store(Request $request, Teleconsultation $teleconsultation): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:51200', // 50MB max
            'file_category' => 'required|in:prescription,lab_result,scan,document,image,other',
            'description' => 'nullable|string|max:500',
            'is_shared_with_patient' => 'boolean',
            'requires_consent' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $file = $request->file('file');
        $fileName = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
        $filePath = $file->storeAs('teleconsultation/files', $fileName, 'public');

        $teleconsultationFile = TeleconsultationFile::create([
            'teleconsultation_id' => $teleconsultation->id,
            'uploaded_by' => Auth::id(),
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'file_url' => Storage::url($filePath),
            'file_type' => $file->getClientOriginalExtension(),
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'file_category' => $request->file_category,
            'description' => $request->description,
            'is_shared_with_patient' => $request->is_shared_with_patient ?? true,
            'requires_consent' => $request->requires_consent ?? false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'File uploaded successfully',
            'data' => $teleconsultationFile->load('uploader')
        ], 201);
    }

    /**
     * Download a file.
     */
    public function download(TeleconsultationFile $file): JsonResponse
    {
        // Check if user has access to this file
        if (!$this->canAccessFile($file)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to access this file'
            ], 403);
        }

        if (!Storage::disk('public')->exists($file->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'File not found'
            ], 404);
        }

        $fileUrl = Storage::url($file->file_path);
        
        return response()->json([
            'success' => true,
            'download_url' => $fileUrl,
            'file_name' => $file->file_name,
            'file_size' => $file->file_size,
        ]);
    }

    /**
     * Update file sharing settings.
     */
    public function update(Request $request, TeleconsultationFile $file): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'is_shared_with_patient' => 'boolean',
                'requires_consent' => 'boolean',
                'description' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if user can modify this file
            if ($file->uploaded_by !== Auth::id() && !Auth::user()->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to modify this file'
                ], 403);
            }

            $file->update($request->only([
                'is_shared_with_patient',
                'requires_consent',
                'description'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'File updated successfully',
                'data' => $file->load('uploader')
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating teleconsultation file: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'file_id' => $file->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update file. Please try again.'
            ], 500);
        }
    }

    /**
     * Give consent for file sharing.
     */
    public function giveConsent(TeleconsultationFile $file): JsonResponse
    {
        if (!$file->requires_consent) {
            return response()->json([
                'success' => false,
                'message' => 'This file does not require consent'
            ], 400);
        }

        $file->giveConsent();

        return response()->json([
            'success' => true,
            'message' => 'Consent given successfully',
            'data' => $file
        ]);
    }

    /**
     * Revoke consent for file sharing.
     */
    public function revokeConsent(TeleconsultationFile $file): JsonResponse
    {
        if (!$file->consent_given) {
            return response()->json([
                'success' => false,
                'message' => 'Consent not given for this file'
            ], 400);
        }

        $file->revokeConsent();

        return response()->json([
            'success' => true,
            'message' => 'Consent revoked successfully',
            'data' => $file
        ]);
    }

    /**
     * Delete a file.
     */
    public function destroy(TeleconsultationFile $file): JsonResponse
    {
        try {
            // Check if user can delete this file
            if ($file->uploaded_by !== Auth::id() && !Auth::user()->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to delete this file'
                ], 403);
            }

            // Delete file from storage
            if (Storage::disk('public')->exists($file->file_path)) {
                Storage::disk('public')->delete($file->file_path);
            }

            $file->delete();

            return response()->json([
                'success' => true,
                'message' => 'File deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting teleconsultation file: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'file_id' => $file->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete file. Please try again.'
            ], 500);
        }
    }

    /**
     * Get file statistics for teleconsultation.
     */
    public function statistics(Teleconsultation $teleconsultation): JsonResponse
    {
        $files = $teleconsultation->sharedFiles();
        
        $stats = [
            'total_files' => $files->count(),
            'by_category' => [
                'prescription' => $files->clone()->byCategory('prescription')->count(),
                'lab_result' => $files->clone()->byCategory('lab_result')->count(),
                'scan' => $files->clone()->byCategory('scan')->count(),
                'document' => $files->clone()->byCategory('document')->count(),
                'image' => $files->clone()->byCategory('image')->count(),
                'other' => $files->clone()->byCategory('other')->count(),
            ],
            'shared_with_patient' => $files->clone()->shared()->count(),
            'requiring_consent' => $files->clone()->requiringConsent()->count(),
            'total_size' => $files->sum('file_size'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Check if user can access file.
     */
    private function canAccessFile(TeleconsultationFile $file): bool
    {
        // Admin can access all files
        if (Auth::user()->hasRole('admin')) {
            return true;
        }

        // Uploader can access their files
        if ($file->uploaded_by === Auth::id()) {
            return true;
        }

        // Doctor can access files from their teleconsultations
        if (Auth::user()->hasRole('doctor') && $file->teleconsultation->doctor_id === Auth::id()) {
            return true;
        }

        // Patient can access files shared with them
        if (Auth::user()->hasRole('patient') && 
            $file->is_shared_with_patient && 
            $file->teleconsultation->patient_id === Auth::id()) {
            return true;
        }

        return false;
    }
}
