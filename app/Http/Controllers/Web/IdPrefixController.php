<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\IdPrefixSetting;
use App\Services\IdPrefixService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class IdPrefixController extends Controller
{
    protected $idPrefixService;

    public function __construct(IdPrefixService $idPrefixService)
    {
        $this->idPrefixService = $idPrefixService;

        $this->middleware('permission:view_settings')->only(['index', 'test']);
        $this->middleware('permission:manage_system_settings')->only([
            'create', 'store', 'edit', 'update', 'resetSequence', 'lock', 'toggleActive',
        ]);
    }

    /**
     * Display all ID prefix settings
     */
    public function index()
    {
        $settings = $this->idPrefixService->getAllSettings();
        $availableTypes = $this->idPrefixService->getAvailableEntityTypes();
        $patternExamples = $this->idPrefixService->getPatternExamples();

        return view('settings.id-prefixes', compact('settings', 'availableTypes', 'patternExamples'));
    }

    /**
     * Show form to create new ID prefix setting
     */
    public function create()
    {
        $existingTypes = IdPrefixSetting::pluck('entity_type')->all();
        $availableTypes = collect($this->idPrefixService->getAvailableEntityTypes())
            ->reject(fn ($label, $type) => in_array($type, $existingTypes, true))
            ->all();
        $patternExamples = $this->idPrefixService->getPatternExamples();

        return view('settings.id-prefix-create', compact('availableTypes', 'patternExamples'));
    }

    /**
     * Store new ID prefix setting
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'entity_type' => 'required|string|max:50|unique:id_prefix_settings,entity_type',
            'company_prefix' => 'required|string|max:10',
            'module_prefix' => 'required|string|max:10',
            'pattern' => 'required|string|max:200',
            'sequence_length' => 'required|integer|min:1|max:10',
            'include_year' => 'sometimes|boolean',
            'include_month' => 'sometimes|boolean',
            'include_day' => 'sometimes|boolean',
            'separator' => 'required|string|max:5',
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $patternValidation = $this->idPrefixService->validatePattern($request->pattern);
        if (!$patternValidation['valid']) {
            return redirect()->back()
                ->withErrors(['pattern' => $patternValidation['message']])
                ->withInput();
        }

        try {
            $this->idPrefixService->getOrCreateSetting(
                $request->entity_type,
                $this->normalizePrefixData($request, true)
            );

            return redirect()->route('id-prefixes.index')
                ->with('success', 'ID prefix setting created successfully!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Show form to edit ID prefix setting
     */
    public function edit($entityType)
    {
        $setting = $this->idPrefixService->getSetting($entityType);

        if (!$setting) {
            return redirect()->route('id-prefixes.index')
                ->with('error', 'ID prefix setting not found.');
        }

        $patternExamples = $this->idPrefixService->getPatternExamples();

        return view('settings.id-prefix-edit', compact('setting', 'patternExamples'));
    }

    /**
     * Update ID prefix setting
     */
    public function update(Request $request, $entityType)
    {
        $validator = Validator::make($request->all(), [
            'company_prefix' => 'required|string|max:10',
            'module_prefix' => 'required|string|max:10',
            'pattern' => 'required|string|max:200',
            'sequence_length' => 'required|integer|min:1|max:10',
            'include_year' => 'sometimes|boolean',
            'include_month' => 'sometimes|boolean',
            'include_day' => 'sometimes|boolean',
            'separator' => 'required|string|max:5',
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $patternValidation = $this->idPrefixService->validatePattern($request->pattern);
        if (!$patternValidation['valid']) {
            return redirect()->back()
                ->withErrors(['pattern' => $patternValidation['message']])
                ->withInput();
        }

        try {
            $this->idPrefixService->updateSetting($entityType, $this->normalizePrefixData($request));

            return redirect()->route('id-prefixes.index')
                ->with('success', 'ID prefix setting updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Test ID generation for an entity type
     */
    public function test($entityType)
    {
        try {
            $result = $this->idPrefixService->testIdGeneration($entityType);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Reset sequence for an entity type
     */
    public function resetSequence($entityType)
    {
        try {
            $this->idPrefixService->resetSequence($entityType);

            return redirect()->route('id-prefixes.index')
                ->with('success', 'Sequence reset successfully for ' . $entityType);
        } catch (\Exception $e) {
            return redirect()->route('id-prefixes.index')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Lock setting for an entity type
     */
    public function lock($entityType)
    {
        try {
            $this->idPrefixService->lockSetting($entityType);

            return redirect()->route('id-prefixes.index')
                ->with('success', 'Setting locked successfully for ' . $entityType);
        } catch (\Exception $e) {
            return redirect()->route('id-prefixes.index')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Toggle active status
     */
    public function toggleActive($entityType)
    {
        try {
            $setting = $this->idPrefixService->getSetting($entityType);

            if (!$setting) {
                throw new \Exception('Setting not found');
            }

            $setting->update(['is_active' => !$setting->is_active]);

            return redirect()->route('id-prefixes.index')
                ->with('success', 'Status updated successfully for ' . $entityType);
        } catch (\Exception $e) {
            return redirect()->route('id-prefixes.index')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Normalize request data for create/update operations.
     */
    private function normalizePrefixData(Request $request, bool $isCreate = false): array
    {
        $data = [
            'company_prefix' => $request->input('company_prefix'),
            'module_prefix' => $request->input('module_prefix'),
            'pattern' => $request->input('pattern'),
            'sequence_length' => (int) $request->input('sequence_length'),
            'separator' => $request->input('separator'),
            'description' => $request->input('description'),
            'include_year' => $request->boolean('include_year'),
            'include_month' => $request->boolean('include_month'),
            'include_day' => $request->boolean('include_day'),
        ];

        if ($isCreate) {
            $data['entity_type'] = $request->input('entity_type');
            $data['current_sequence'] = 0;
            $data['is_locked'] = false;
            $data['is_active'] = true;
        }

        return $data;
    }
}
