<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\EyeService;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class EyeServiceController extends Controller
{
    /**
     * Display a listing of eye services.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = EyeService::with(['creator', 'updater'])
                ->active();

            // Filter by category
            if ($request->has('category')) {
                $query->byCategory($request->category);
            }

            // Filter by service type
            if ($request->has('service_type')) {
                $query->byType($request->service_type);
            }

            // Filter by NHIS coverage
            if ($request->has('nhis_covered')) {
                $query->nhisCovered();
            }

            // Search by name or code
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('service_name', 'like', "%{$search}%")
                      ->orWhere('service_code', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Sort
            $sortBy = $request->get('sort_by', 'service_name');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $services = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $services,
                'message' => 'Eye services retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve eye services',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created eye service.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'service_code' => 'required|string|max:50|unique:eye_services,service_code',
                'service_name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'category' => 'required|string|max:100',
                'subcategory' => 'nullable|string|max:100',
                'service_type' => 'required|in:examination,test,treatment,consultation,procedure',
                'instructions' => 'nullable|string',
                'duration_minutes' => 'required|integer|min:1',
                'requires_doctor' => 'boolean',
                'requires_equipment' => 'boolean',
                'equipment_required' => 'nullable|array',
                'preparation_instructions' => 'nullable|array',
                'post_service_instructions' => 'nullable|array',
                'base_price' => 'required|numeric|min:0',
                'nhis_price' => 'nullable|numeric|min:0',
                'nhis_covered' => 'boolean',
                'currency' => 'required|string|size:3',
                'ghs_code' => 'nullable|string|max:50',
                'ghs_mandatory' => 'boolean',
                'ghs_reporting_requirements' => 'nullable|array',
                'requires_approval' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $service = EyeService::create([
                ...$request->validated(),
                'created_by' => auth()->id(),
            ]);

            $service->load(['creator']);

            return response()->json([
                'success' => true,
                'data' => $service,
                'message' => 'Eye service created successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create eye service',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified eye service.
     */
    public function show(EyeService $eyeService): JsonResponse
    {
        try {
            $eyeService->load(['creator', 'updater', 'templates', 'testRequests']);

            return response()->json([
                'success' => true,
                'data' => $eyeService,
                'message' => 'Eye service retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve eye service',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified eye service.
     */
    public function update(Request $request, EyeService $eyeService): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'service_code' => [
                    'required',
                    'string',
                    'max:50',
                    Rule::unique('eye_services', 'service_code')->ignore($eyeService->id)
                ],
                'service_name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'category' => 'required|string|max:100',
                'subcategory' => 'nullable|string|max:100',
                'service_type' => 'required|in:examination,test,treatment,consultation,procedure',
                'instructions' => 'nullable|string',
                'duration_minutes' => 'required|integer|min:1',
                'requires_doctor' => 'boolean',
                'requires_equipment' => 'boolean',
                'equipment_required' => 'nullable|array',
                'preparation_instructions' => 'nullable|array',
                'post_service_instructions' => 'nullable|array',
                'base_price' => 'required|numeric|min:0',
                'nhis_price' => 'nullable|numeric|min:0',
                'nhis_covered' => 'boolean',
                'currency' => 'required|string|size:3',
                'ghs_code' => 'nullable|string|max:50',
                'ghs_mandatory' => 'boolean',
                'ghs_reporting_requirements' => 'nullable|array',
                'is_active' => 'boolean',
                'requires_approval' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $eyeService->update([
                ...$request->validated(),
                'updated_by' => auth()->id(),
            ]);

            $eyeService->load(['creator', 'updater']);

            return response()->json([
                'success' => true,
                'data' => $eyeService,
                'message' => 'Eye service updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update eye service',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified eye service.
     */
    public function destroy(EyeService $eyeService): JsonResponse
    {
        try {
            // Check if service has associated test requests
            if ($eyeService->testRequests()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete eye service with existing test requests'
                ], 422);
            }

            $eyeService->delete();

            return response()->json([
                'success' => true,
                'message' => 'Eye service deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete eye service',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get service categories.
     */
    public function categories(): JsonResponse
    {
        try {
            $categories = EyeService::active()
                ->select('category')
                ->distinct()
                ->orderBy('category')
                ->pluck('category');

            return response()->json([
                'success' => true,
                'data' => $categories,
                'message' => 'Service categories retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve service categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get service types.
     */
    public function serviceTypes(): JsonResponse
    {
        try {
            $serviceTypes = EyeService::active()
                ->select('service_type')
                ->distinct()
                ->orderBy('service_type')
                ->pluck('service_type');

            return response()->json([
                'success' => true,
                'data' => $serviceTypes,
                'message' => 'Service types retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve service types',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get NHIS covered services.
     */
    public function nhisCovered(): JsonResponse
    {
        try {
            $services = EyeService::active()
                ->nhisCovered()
                ->select('id', 'service_code', 'service_name', 'base_price', 'nhis_price')
                ->orderBy('service_name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $services,
                'message' => 'NHIS covered services retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve NHIS covered services',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle service active status.
     */
    public function toggleStatus(EyeService $eyeService): JsonResponse
    {
        try {
            $eyeService->update([
                'is_active' => !$eyeService->is_active,
                'updated_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $eyeService,
                'message' => 'Service status updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update service status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get service statistics.
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = [
                'total_services' => EyeService::count(),
                'active_services' => EyeService::active()->count(),
                'inactive_services' => EyeService::where('is_active', false)->count(),
                'nhis_covered' => EyeService::active()->nhisCovered()->count(),
                'by_category' => EyeService::active()
                    ->select('category', DB::raw('count(*) as count'))
                    ->groupBy('category')
                    ->get(),
                'by_type' => EyeService::active()
                    ->select('service_type', DB::raw('count(*) as count'))
                    ->groupBy('service_type')
                    ->get(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Service statistics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve service statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
