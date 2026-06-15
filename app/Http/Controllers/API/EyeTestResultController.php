<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\EyeTestResult;
use App\Models\EyeTestRequest;
use App\Models\EyeTestParameter;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class EyeTestResultController extends Controller
{
    /**
     * Display a listing of eye test results.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = EyeTestResult::with([
                'testRequest.patient',
                'testRequest.service',
                'parameter',
                'performedBy',
                'verifiedBy'
            ]);

            // Filter by test request
            if ($request->has('test_request_id')) {
                $query->byTestRequest($request->test_request_id);
            }

            // Filter by parameter
            if ($request->has('parameter_id')) {
                $query->byParameter($request->parameter_id);
            }

            // Filter by result status
            if ($request->has('result_status')) {
                $query->where('result_status', $request->result_status);
            }

            // Filter by abnormal flag
            if ($request->has('abnormal_flag')) {
                $query->where('abnormal_flag', $request->abnormal_flag);
            }

            // Filter by date range
            if ($request->has('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Search
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('parameter_name', 'like', "%{$search}%")
                      ->orWhere('result_value', 'like', "%{$search}%")
                      ->orWhereHas('testRequest.patient', function ($patientQuery) use ($search) {
                          $patientQuery->where('first_name', 'like', "%{$search}%")
                                     ->orWhere('last_name', 'like', "%{$search}%");
                      });
                });
            }

            // Sort
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $results = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $results,
                'message' => 'Eye test results retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve eye test results',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created eye test result.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'test_request_id' => 'required|exists:eye_test_requests,id',
                'parameter_id' => 'required|exists:eye_test_parameters,id',
                'result_value' => 'required',
                'formatted_value' => 'nullable|string',
                'unit' => 'nullable|string|max:20',
                'reference_range' => 'nullable|string|max:100',
                'age_group' => 'nullable|string|max:50',
                'gender' => 'nullable|string|max:10',
                'clinical_interpretation' => 'nullable|string',
                'technical_notes' => 'nullable|string',
                'equipment_used' => 'nullable|array',
                'test_conditions' => 'nullable|array',
                'methodology_used' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Get parameter details
            $parameter = EyeTestParameter::findOrFail($request->parameter_id);
            $testRequest = EyeTestRequest::findOrFail($request->test_request_id);

            // Validate result value based on parameter type
            $validationErrors = $parameter->validateValue($request->result_value);
            if (!empty($validationErrors)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => ['result_value' => $validationErrors]
                ], 422);
            }

            // Determine result status and abnormal flag
            $resultStatus = 'normal';
            $abnormalFlag = null;

            // Check against reference ranges and abnormal criteria
            $referenceRanges = $parameter->getReferenceRanges();
            $abnormalCriteria = $parameter->getAbnormalCriteria();

            if ($parameter->isNumeric() && is_numeric($request->result_value)) {
                $value = (float) $request->result_value;
                
                // Check against reference ranges
                if (!empty($referenceRanges)) {
                    foreach ($referenceRanges as $range) {
                        if ($this->isValueInRange($value, $range)) {
                            $resultStatus = 'normal';
                            break;
                        } else {
                            $resultStatus = 'abnormal';
                            $abnormalFlag = $this->getAbnormalFlag($value, $range);
                        }
                    }
                }

                // Check against abnormal criteria
                if (!empty($abnormalCriteria)) {
                    foreach ($abnormalCriteria as $criteria) {
                        if ($this->meetsCriteria($value, $criteria)) {
                            $resultStatus = 'critical';
                            $abnormalFlag = 'CRITICAL';
                            break;
                        }
                    }
                }
            }

            $result = EyeTestResult::create([
                'test_request_id' => $request->test_request_id,
                'template_id' => $testRequest->template_id,
                'parameter_id' => $request->parameter_id,
                'parameter_code' => $parameter->parameter_code,
                'parameter_name' => $parameter->parameter_name,
                'result_value' => $request->result_value,
                'formatted_value' => $request->formatted_value ?: $request->result_value,
                'unit' => $request->unit ?: $parameter->unit,
                'reference_range' => $request->reference_range,
                'age_group' => $request->age_group,
                'gender' => $request->gender,
                'result_status' => $resultStatus,
                'abnormal_flag' => $abnormalFlag,
                'clinical_interpretation' => $request->clinical_interpretation,
                'technical_notes' => $request->technical_notes,
                'equipment_used' => $request->equipment_used,
                'test_conditions' => $request->test_conditions,
                'methodology_used' => $request->methodology_used,
                'test_performed_at' => now(),
                'performed_by' => auth()->id(),
            ]);

            // Mark test request as having results
            $testRequest->markResultsEntered(auth()->id());

            $result->load(['testRequest.patient', 'parameter', 'performedBy']);

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Eye test result created successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create eye test result',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified eye test result.
     */
    public function show(EyeTestResult $eyeTestResult): JsonResponse
    {
        try {
            $eyeTestResult->load([
                'testRequest.patient',
                'testRequest.service',
                'parameter',
                'performedBy',
                'verifiedBy'
            ]);

            return response()->json([
                'success' => true,
                'data' => $eyeTestResult,
                'message' => 'Eye test result retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve eye test result',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified eye test result.
     */
    public function update(Request $request, EyeTestResult $eyeTestResult): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'result_value' => 'required',
                'formatted_value' => 'nullable|string',
                'unit' => 'nullable|string|max:20',
                'reference_range' => 'nullable|string|max:100',
                'age_group' => 'nullable|string|max:50',
                'gender' => 'nullable|string|max:10',
                'result_status' => 'in:normal,abnormal,critical,pending,cancelled',
                'abnormal_flag' => 'nullable|in:H,L,HH,LL,CRITICAL,ABNORMAL',
                'clinical_interpretation' => 'nullable|string',
                'technical_notes' => 'nullable|string',
                'equipment_used' => 'nullable|array',
                'test_conditions' => 'nullable|array',
                'methodology_used' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $eyeTestResult->update($request->validated());

            $eyeTestResult->load([
                'testRequest.patient',
                'parameter',
                'performedBy',
                'verifiedBy'
            ]);

            return response()->json([
                'success' => true,
                'data' => $eyeTestResult,
                'message' => 'Eye test result updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update eye test result',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark result as verified.
     */
    public function verify(EyeTestResult $eyeTestResult): JsonResponse
    {
        try {
            $eyeTestResult->markAsVerified(auth()->id());

            return response()->json([
                'success' => true,
                'data' => $eyeTestResult->fresh(),
                'message' => 'Result marked as verified successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify result',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark result for repeat.
     */
    public function markForRepeat(Request $request, EyeTestResult $eyeTestResult): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'reason' => 'required|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $eyeTestResult->markAsRepeat($request->reason, auth()->id());

            return response()->json([
                'success' => true,
                'data' => $eyeTestResult->fresh(),
                'message' => 'Result marked for repeat successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark result for repeat',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get results by test request.
     */
    public function getByTestRequest(EyeTestRequest $eyeTestRequest): JsonResponse
    {
        try {
            $results = $eyeTestRequest->testResults()
                ->with(['parameter', 'performedBy', 'verifiedBy'])
                ->orderBy('parameter.sort_order')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $results,
                'message' => 'Test results retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve test results',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get abnormal results.
     */
    public function getAbnormalResults(Request $request): JsonResponse
    {
        try {
            $query = EyeTestResult::with([
                'testRequest.patient',
                'parameter',
                'performedBy'
            ])->abnormal();

            // Filter by date range
            if ($request->has('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            $results = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $results,
                'message' => 'Abnormal results retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve abnormal results',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get critical results.
     */
    public function getCriticalResults(Request $request): JsonResponse
    {
        try {
            $query = EyeTestResult::with([
                'testRequest.patient',
                'parameter',
                'performedBy'
            ])->critical();

            // Filter by date range
            if ($request->has('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            $results = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $results,
                'message' => 'Critical results retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve critical results',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get result statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $query = EyeTestResult::query();

            // Filter by date range
            if ($request->has('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            $stats = [
                'total_results' => $query->count(),
                'normal' => $query->clone()->normal()->count(),
                'abnormal' => $query->clone()->abnormal()->count(),
                'critical' => $query->clone()->critical()->count(),
                'pending' => $query->clone()->pending()->count(),
                'verified' => $query->clone()->whereNotNull('result_verified_at')->count(),
                'requires_repeat' => $query->clone()->requiresRepeat()->count(),
                'by_status' => $query->clone()
                    ->select('result_status', DB::raw('count(*) as count'))
                    ->groupBy('result_status')
                    ->get(),
                'by_abnormal_flag' => $query->clone()
                    ->select('abnormal_flag', DB::raw('count(*) as count'))
                    ->whereNotNull('abnormal_flag')
                    ->groupBy('abnormal_flag')
                    ->get(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Result statistics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve result statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper method to check if value is in range.
     */
    private function isValueInRange(float $value, array $range): bool
    {
        $min = $range['min'] ?? null;
        $max = $range['max'] ?? null;
        $minOperator = $range['min_operator'] ?? '>=';
        $maxOperator = $range['max_operator'] ?? '<=';

        if ($min !== null) {
            if ($minOperator === '>=' && $value < $min) return false;
            if ($minOperator === '>' && $value <= $min) return false;
        }

        if ($max !== null) {
            if ($maxOperator === '<=' && $value > $max) return false;
            if ($maxOperator === '<' && $value >= $max) return false;
        }

        return true;
    }

    /**
     * Helper method to get abnormal flag.
     */
    private function getAbnormalFlag(float $value, array $range): string
    {
        $min = $range['min'] ?? null;
        $max = $range['max'] ?? null;

        if ($min !== null && $value < $min) {
            return $value < ($min * 0.5) ? 'LL' : 'L';
        }

        if ($max !== null && $value > $max) {
            return $value > ($max * 1.5) ? 'HH' : 'H';
        }

        return 'ABNORMAL';
    }

    /**
     * Helper method to check if value meets criteria.
     */
    private function meetsCriteria(float $value, array $criteria): bool
    {
        $operator = $criteria['operator'] ?? '>';
        $threshold = $criteria['threshold'] ?? 0;

        switch ($operator) {
            case '>':
                return $value > $threshold;
            case '>=':
                return $value >= $threshold;
            case '<':
                return $value < $threshold;
            case '<=':
                return $value <= $threshold;
            case '=':
            case '==':
                return $value == $threshold;
            default:
                return false;
        }
    }

    /**
     * Remove the specified eye test result.
     */
    public function destroy(EyeTestResult $eyeTestResult): JsonResponse
    {
        try {
            // Check if result can be deleted
            if ($eyeTestResult->is_verified) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete verified test result'
                ], 422);
            }

            $eyeTestResult->delete();

            return response()->json([
                'success' => true,
                'message' => 'Eye test result deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting eye test result: ' . $e->getMessage()
            ], 500);
        }
    }
}
