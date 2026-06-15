<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ConsultationTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ConsultationTemplateController extends Controller
{
    /**
     * Display a listing of consultation templates.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = ConsultationTemplate::with(['creator']);

            // Filter by specialty
            if ($request->filled('specialty')) {
                $query->bySpecialty($request->specialty);
            }

            // Filter by active status
            if ($request->filled('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            } else {
                $query->active(); // Default to active templates
            }

            // Filter system vs custom templates
            if ($request->filled('is_system')) {
                if ($request->boolean('is_system')) {
                    $query->system();
                } else {
                    $query->custom();
                }
            }

            // Search
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('specialty', 'like', "%{$search}%");
                });
            }

            $perPage = $request->get('per_page', 15);
            $templates = $query->orderBy('name')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $templates->items(),
                'meta' => [
                    'current_page' => $templates->currentPage(),
                    'last_page' => $templates->lastPage(),
                    'per_page' => $templates->perPage(),
                    'total' => $templates->total()
                ],
                'message' => 'Consultation templates retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving templates: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created consultation template.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'specialty' => 'nullable|string|max:255',
                'template_data' => 'required|array',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => $validator->errors()
                ], 422);
            }

            $template = ConsultationTemplate::create([
                'name' => $request->name,
                'description' => $request->description,
                'specialty' => $request->specialty,
                'template_data' => $request->template_data,
                'is_active' => $request->is_active ?? true,
                'is_system' => false, // User-created templates are not system templates
                'created_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $template->load('creator'),
                'message' => 'Consultation template created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating template: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified consultation template.
     */
    public function show($id): JsonResponse
    {
        try {
            $template = ConsultationTemplate::with(['creator'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $template,
                'message' => 'Template retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Template not found'
            ], 404);
        }
    }

    /**
     * Update the specified consultation template.
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $template = ConsultationTemplate::findOrFail($id);

            // Prevent editing system templates
            if ($template->is_system && !auth()->user()->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'System templates cannot be modified'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'specialty' => 'nullable|string|max:255',
                'template_data' => 'sometimes|array',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => $validator->errors()
                ], 422);
            }

            $template->update($request->only([
                'name',
                'description',
                'specialty',
                'template_data',
                'is_active'
            ]));

            return response()->json([
                'success' => true,
                'data' => $template->fresh()->load('creator'),
                'message' => 'Template updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating template: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified consultation template.
     */
    public function destroy($id): JsonResponse
    {
        try {
            $template = ConsultationTemplate::findOrFail($id);

            // Prevent deleting system templates
            if ($template->is_system) {
                return response()->json([
                    'success' => false,
                    'message' => 'System templates cannot be deleted'
                ], 403);
            }

            $template->delete();

            return response()->json([
                'success' => true,
                'message' => 'Template deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting template: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available specialties.
     */
    public function getSpecialties(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => ConsultationTemplate::getSpecialties(),
            'message' => 'Specialties retrieved successfully'
        ]);
    }
}
