<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\FileUpload;
use App\Models\Patient;
use App\Models\Consultation;
use App\Models\LabRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class FileUploadController extends Controller
{
    /**
     * Upload file.
     */
    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:10240', // 10MB max
            'category' => 'required|in:patient_photo,medical_image,lab_result,prescription,insurance_document,consultation_note,other',
            'related_type' => 'required|in:patient,consultation,lab_request,prescription,appointment',
            'related_id' => 'required|integer',
            'description' => 'nullable|string|max:500',
            'is_private' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $mimeType = $file->getMimeType();
        $size = $file->getSize();

        // Generate unique filename
        $filename = Str::uuid() . '.' . $extension;
        
        // Determine storage path based on category
        $path = $this->getStoragePath($request->category, $request->related_type, $request->related_id);
        
        // Store file
        $storedPath = $file->storeAs($path, $filename, 'public');

        // Process image if it's an image file
        if (str_starts_with($mimeType, 'image/')) {
            $this->processImage($storedPath, $request->category);
        }

        // Create file upload record
        $fileUpload = FileUpload::create([
            'original_name' => $originalName,
            'filename' => $filename,
            'path' => $storedPath,
            'category' => $request->category,
            'mime_type' => $mimeType,
            'size' => $size,
            'related_type' => $request->related_type,
            'related_id' => $request->related_id,
            'description' => $request->description,
            'is_private' => $request->is_private ?? false,
            'uploaded_by' => auth()->id()
        ]);

        return response()->json([
            'success' => true,
            'data' => $fileUpload,
            'message' => 'File uploaded successfully'
        ], 201);
    }

    /**
     * Upload multiple files.
     */
    public function uploadMultiple(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'files' => 'required|array|min:1|max:10',
            'files.*' => 'required|file|max:10240',
            'category' => 'required|in:patient_photo,medical_image,lab_result,prescription,insurance_document,consultation_note,other',
            'related_type' => 'required|in:patient,consultation,lab_request,prescription,appointment',
            'related_id' => 'required|integer',
            'description' => 'nullable|string|max:500',
            'is_private' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $uploadedFiles = [];
        $errors = [];

        foreach ($request->file('files') as $index => $file) {
            try {
                $originalName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $mimeType = $file->getMimeType();
                $size = $file->getSize();

                // Generate unique filename
                $filename = Str::uuid() . '.' . $extension;
                
                // Determine storage path
                $path = $this->getStoragePath($request->category, $request->related_type, $request->related_id);
                
                // Store file
                $storedPath = $file->storeAs($path, $filename, 'public');

                // Process image if it's an image file
                if (str_starts_with($mimeType, 'image/')) {
                    $this->processImage($storedPath, $request->category);
                }

                // Create file upload record
                $fileUpload = FileUpload::create([
                    'original_name' => $originalName,
                    'filename' => $filename,
                    'path' => $storedPath,
                    'category' => $request->category,
                    'mime_type' => $mimeType,
                    'size' => $size,
                    'related_type' => $request->related_type,
                    'related_id' => $request->related_id,
                    'description' => $request->description,
                    'is_private' => $request->is_private ?? false,
                    'uploaded_by' => auth()->id()
                ]);

                $uploadedFiles[] = $fileUpload;

            } catch (\Exception $e) {
                $errors[] = [
                    'file' => $originalName,
                    'error' => $e->getMessage()
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'uploaded_files' => $uploadedFiles,
                'errors' => $errors,
                'total_uploaded' => count($uploadedFiles),
                'total_errors' => count($errors)
            ],
            'message' => 'Multiple files upload completed'
        ], 201);
    }

    /**
     * Get files for a specific entity.
     */
    public function getFiles(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'related_type' => 'required|in:patient,consultation,lab_request,prescription,appointment',
            'related_id' => 'required|integer',
            'category' => 'nullable|in:patient_photo,medical_image,lab_result,prescription,insurance_document,consultation_note,other'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = FileUpload::where('related_type', $request->related_type)
            ->where('related_id', $request->related_id)
            ->orderBy('created_at', 'desc');

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        $files = $query->get();

        return response()->json([
            'success' => true,
            'data' => $files,
            'message' => 'Files retrieved successfully'
        ]);
    }

    /**
     * Download file.
     */
    public function download($id)
    {
        $fileUpload = FileUpload::findOrFail($id);

        // Check if user has permission to download this file
        if (!$this->canAccessFile($fileUpload)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        $filePath = storage_path('app/public/' . $fileUpload->path);

        if (!file_exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'File not found'
            ], 404);
        }

        return response()->download($filePath, $fileUpload->original_name);
    }

    /**
     * Get file URL.
     */
    public function getFileUrl($id)
    {
        $fileUpload = FileUpload::findOrFail($id);

        // Check if user has permission to access this file
        if (!$this->canAccessFile($fileUpload)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        $url = Storage::url($fileUpload->path);

        return response()->json([
            'success' => true,
            'data' => [
                'url' => $url,
                'filename' => $fileUpload->original_name,
                'mime_type' => $fileUpload->mime_type,
                'size' => $fileUpload->size
            ],
            'message' => 'File URL retrieved successfully'
        ]);
    }

    /**
     * Delete file.
     */
    public function destroy($id)
    {
        $fileUpload = FileUpload::findOrFail($id);

        // Check if user has permission to delete this file
        if (!$this->canAccessFile($fileUpload)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        // Delete file from storage
        Storage::disk('public')->delete($fileUpload->path);

        // Delete database record
        $fileUpload->delete();

        return response()->json([
            'success' => true,
            'message' => 'File deleted successfully'
        ]);
    }

    /**
     * Get file statistics.
     */
    public function getStatistics(Request $request)
    {
        $dateFrom = $request->date_from ?? now()->subDays(30);
        $dateTo = $request->date_to ?? now();

        $stats = [
            'total_files' => FileUpload::whereBetween('created_at', [$dateFrom, $dateTo])->count(),
            'total_size' => FileUpload::whereBetween('created_at', [$dateFrom, $dateTo])->sum('size'),
            'file_categories' => $this->getFileCategoryStats($dateFrom, $dateTo),
            'file_types' => $this->getFileTypeStats($dateFrom, $dateTo),
            'storage_usage' => $this->getStorageUsageStats(),
            'recent_uploads' => FileUpload::with(['uploadedBy'])
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
            'message' => 'File statistics retrieved successfully'
        ]);
    }

    /**
     * Get storage path for file.
     */
    private function getStoragePath($category, $relatedType, $relatedId)
    {
        $basePath = 'uploads/' . $category . '/' . $relatedType . '/' . $relatedId;
        
        // Create directory if it doesn't exist
        $fullPath = storage_path('app/public/' . $basePath);
        if (!file_exists($fullPath)) {
            mkdir($fullPath, 0755, true);
        }

        return $basePath;
    }

    /**
     * Process image file.
     */
    private function processImage($path, $category)
    {
        try {
            $fullPath = storage_path('app/public/' . $path);
            
            // Create different sizes for patient photos
            if ($category === 'patient_photo') {
                // Thumbnail (150x150)
                Image::make($fullPath)
                    ->resize(150, 150, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    })
                    ->save(storage_path('app/public/' . dirname($path) . '/thumb_' . basename($path)));

                // Medium (300x300)
                Image::make($fullPath)
                    ->resize(300, 300, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    })
                    ->save(storage_path('app/public/' . dirname($path) . '/medium_' . basename($path)));
            }

            // Compress large images
            $image = Image::make($fullPath);
            if ($image->width() > 1920 || $image->height() > 1080) {
                $image->resize(1920, 1080, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                })->save($fullPath, 85);
            }

        } catch (\Exception $e) {
            // Log error but don't fail the upload
            \Log::error('Image processing error: ' . $e->getMessage());
        }
    }

    /**
     * Check if user can access file.
     */
    private function canAccessFile($fileUpload)
    {
        // Super admin can access all files
        if (auth()->user()->hasRole('super_admin')) {
            return true;
        }

        // Check based on related type
        switch ($fileUpload->related_type) {
            case 'patient':
                // Check if user has access to this patient
                return $this->canAccessPatient($fileUpload->related_id);
            
            case 'consultation':
                // Check if user has access to this consultation
                return $this->canAccessConsultation($fileUpload->related_id);
            
            case 'lab_request':
                // Check if user has access to this lab request
                return $this->canAccessLabRequest($fileUpload->related_id);
            
            default:
                return true;
        }
    }

    /**
     * Check if user can access patient.
     */
    private function canAccessPatient($patientId)
    {
        // Add your patient access logic here
        return true;
    }

    /**
     * Check if user can access consultation.
     */
    private function canAccessConsultation($consultationId)
    {
        // Add your consultation access logic here
        return true;
    }

    /**
     * Check if user can access lab request.
     */
    private function canAccessLabRequest($labRequestId)
    {
        // Add your lab request access logic here
        return true;
    }

    /**
     * Get file category statistics.
     */
    private function getFileCategoryStats($dateFrom, $dateTo)
    {
        return FileUpload::whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('category, COUNT(*) as count, SUM(size) as total_size')
            ->groupBy('category')
            ->orderBy('count', 'desc')
            ->get();
    }

    /**
     * Get file type statistics.
     */
    private function getFileTypeStats($dateFrom, $dateTo)
    {
        return FileUpload::whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('mime_type, COUNT(*) as count')
            ->groupBy('mime_type')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * Get storage usage statistics.
     */
    private function getStorageUsageStats()
    {
        $totalFiles = FileUpload::count();
        $totalSize = FileUpload::sum('size');
        $availableSpace = disk_free_space(storage_path('app/public'));
        $totalSpace = disk_total_space(storage_path('app/public'));

        return [
            'total_files' => $totalFiles,
            'total_size' => $totalSize,
            'total_size_mb' => round($totalSize / 1024 / 1024, 2),
            'available_space' => $availableSpace,
            'available_space_mb' => round($availableSpace / 1024 / 1024, 2),
            'total_space' => $totalSpace,
            'total_space_mb' => round($totalSpace / 1024 / 1024, 2),
            'usage_percentage' => round((($totalSpace - $availableSpace) / $totalSpace) * 100, 2)
        ];
    }
}
