<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\LabTestCategory;
use App\Models\LabTestType;
use App\Models\LabTestTypeItem;
use App\Models\LabReagent;
use App\Models\LabConsumable;
use App\Models\LabTest;
use App\Models\LabTestTemplate;
use App\Models\LabTestParameter;
use App\Models\LabReferenceRange;
use App\Models\LabRequest;
use App\Models\LabTestResult;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LabManagementController extends Controller
{
    // ========================================
    // TEST CATEGORIES MANAGEMENT
    // ========================================
    
    public function categories()
    {
        $categories = LabTestCategory::with(['createdBy', 'updatedBy'])
            ->orderBy('sort_order', 'asc')
            ->latest('id')
            ->paginate(20);
        
        return view('lab.categories.index', compact('categories'));
    }
    
    public function createCategory()
    {
        return view('lab.categories.create');
    }
    
    public function storeCategory(Request $request)
    {
        \Log::info('Category creation started', [
            'user_id' => auth()->id(),
            'request_data' => $request->all()
        ]);
        
        try {
            $validated = $request->validate([
                'code' => 'required|string|unique:lab_test_categories,code',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'sort_order' => 'nullable|integer|min:0'
            ]);
            
            $validated['created_by'] = auth()->id();
            $validated['is_active'] = $request->has('is_active');
            
            \Log::info('Validated data', ['validated' => $validated]);
            
            $category = LabTestCategory::create($validated);
            
            \Log::info('Category created successfully', ['category_id' => $category->id]);
            
            return redirect()->route('lab.categories')
                ->with('success', 'Test category created successfully!');
                
        } catch (\Exception $e) {
            \Log::error('Category creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create category: ' . $e->getMessage());
        }
    }
    
    public function editCategory(LabTestCategory $category)
    {
        return view('lab.categories.edit', compact('category'));
    }
    
    public function updateCategory(Request $request, LabTestCategory $category)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:lab_test_categories,code,' . $category->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0'
        ]);
        
        $validated['updated_by'] = auth()->id();
        $validated['is_active'] = $request->has('is_active');
        
        $category->update($validated);
        
        return redirect()->route('lab.categories')
            ->with('success', 'Test category updated successfully!');
    }
    
    public function destroyCategory(LabTestCategory $category)
    {
        // Check if category has associated tests
        if ($category->tests()->count() > 0) {
            return redirect()->route('lab.categories')
                ->with('error', 'Cannot delete category with associated tests!');
        }
        
        $category->delete();
        
        return redirect()->route('lab.categories')
            ->with('success', 'Test category deleted successfully!');
    }
    
    // ========================================
    // INDIVIDUAL TESTS MANAGEMENT
    // ========================================
    
    public function tests()
    {
        $tests = LabTest::with(['category', 'testType', 'template', 'createdBy'])
            ->orderBy('sort_order', 'asc')
            ->latest('id')
            ->paginate(20);
        
        return view('lab.tests.index', compact('tests'));
    }
    
    public function createTest()
    {
        $categories = LabTestCategory::active()->ordered()->get();
        $testTypes = LabTestType::active()->get();
        $templates = LabTestTemplate::active()->get();
        
        return view('lab.tests.create', compact('categories', 'testTypes', 'templates'));
    }
    
    public function storeTest(Request $request)
    {
        $validated = $request->validate([
            'test_code' => 'required|string|unique:lab_tests,test_code',
            'test_name' => 'required|string|max:255',
            'category_id' => 'required|exists:lab_test_categories,id',
            'test_type_id' => 'nullable|exists:lab_test_types,id',
            'template_id' => 'nullable|exists:lab_test_templates,id',
            'description' => 'nullable|string',
            'specimen_type' => 'nullable|string',
            'cost' => 'nullable|numeric|min:0',
            'nhis_cost' => 'nullable|numeric|min:0',
            'nhis_covered' => 'nullable|boolean',
            'turnaround_hours' => 'nullable|integer|min:1',
            'sort_order' => 'nullable|integer|min:0',
        ]);
        
        $validated['created_by'] = auth()->id();
        $validated['nhis_covered'] = $request->has('nhis_covered');
        $validated['is_active'] = $request->has('is_active');
        
        LabTest::create($validated);
        
        return redirect()->route('lab.tests')
            ->with('success', 'Test created successfully!');
    }
    
    public function editTest(LabTest $test)
    {
        $categories = LabTestCategory::active()->ordered()->get();
        $testTypes = LabTestType::active()->get();
        $templates = LabTestTemplate::active()->get();
        
        return view('lab.tests.edit', compact('test', 'categories', 'testTypes', 'templates'));
    }
    
    public function updateTest(Request $request, LabTest $test)
    {
        $validated = $request->validate([
            'test_code' => 'required|string|unique:lab_tests,test_code,' . $test->id,
            'test_name' => 'required|string|max:255',
            'category_id' => 'required|exists:lab_test_categories,id',
            'test_type_id' => 'nullable|exists:lab_test_types,id',
            'template_id' => 'nullable|exists:lab_test_templates,id',
            'description' => 'nullable|string',
            'specimen_type' => 'nullable|string',
            'cost' => 'nullable|numeric|min:0',
            'nhis_cost' => 'nullable|numeric|min:0',
            'nhis_covered' => 'nullable|boolean',
            'turnaround_hours' => 'nullable|integer|min:1',
            'sort_order' => 'nullable|integer|min:0',
        ]);
        
        $validated['updated_by'] = auth()->id();
        $validated['nhis_covered'] = $request->has('nhis_covered');
        $validated['is_active'] = $request->has('is_active');
        
        $test->update($validated);
        
        return redirect()->route('lab.tests')
            ->with('success', 'Test updated successfully!');
    }
    
    public function destroyTest(LabTest $test)
    {
        $test->delete();
        
        return redirect()->route('lab.tests')
            ->with('success', 'Test deleted successfully!');
    }
    
    // ========================================
    // TEMPLATES MANAGEMENT
    // ========================================
    
    public function templates()
    {
        $templates = LabTestTemplate::with(['category', 'createdBy', 'parameters'])
            ->latest('id')
            ->paginate(20);
        
        return view('lab.templates.index', compact('templates'));
    }
    
    public function createTemplate()
    {
        $categories = LabTestCategory::active()->ordered()->get();
        // Test types grouped by category_id for subcategory (test type) dropdown
        $testTypesByCategory = LabTestType::where('is_active', true)
            ->orderBy('test_name')
            ->get()
            ->groupBy('category_id');
        
        return view('lab.templates.create', compact('categories', 'testTypesByCategory'));
    }
    
    public function storeTemplate(Request $request)
    {
        $validated = $request->validate([
            'template_code' => 'required|string|unique:lab_test_templates,template_code',
            'template_name' => 'required|string|max:255',
            'category_id' => 'required|exists:lab_test_categories,id',
            'subcategory' => 'nullable|string',
            'description' => 'nullable|string',
            'template_content' => 'nullable|string',
            'template_type' => 'required|in:quantitative,qualitative,narrative,combined',
            'specimen_type' => 'required|string',
            'methodology' => 'nullable|string',
            'equipment_required' => 'nullable|string',
            'routine_tat_hours' => 'nullable|integer|min:1',
            'urgent_tat_hours' => 'nullable|integer|min:1',
            'stat_tat_hours' => 'nullable|integer|min:1',
            'cost' => 'nullable|numeric|min:0',
            'nhis_cost' => 'nullable|numeric|min:0',
            'quantitative_parameters' => 'nullable|array',
            'quantitative_parameters.*.parameter_name' => 'required_with:quantitative_parameters|string|max:255',
            'quantitative_parameters.*.parameter_code' => 'nullable|string|max:50',
            'quantitative_parameters.*.unit' => 'nullable|string|max:20',
            'quantitative_parameters.*.decimal_places' => 'nullable|integer|min:0|max:4',
            'quantitative_parameters.*.sort_order' => 'nullable|integer|min:1',
            'quantitative_parameters.*.reference_ranges' => 'nullable|array',
            'quantitative_parameters.*.reference_ranges.*.age_group' => 'required_with:quantitative_parameters.*.reference_ranges|string|in:neonate,infant,child,adolescent,adult,elderly,all_ages',
            'quantitative_parameters.*.reference_ranges.*.gender' => 'required_with:quantitative_parameters.*.reference_ranges|string|in:male,female,both',
            'quantitative_parameters.*.reference_ranges.*.pregnancy_status' => 'nullable|string|in:not_pregnant,pregnant,both',
            'quantitative_parameters.*.reference_ranges.*.trimester' => 'nullable|string|in:first,second,third,all',
            'quantitative_parameters.*.reference_ranges.*.min_value' => 'nullable|numeric',
            'quantitative_parameters.*.reference_ranges.*.max_value' => 'nullable|numeric',
            'quantitative_parameters.*.reference_ranges.*.unit' => 'nullable|string|max:20',
            'quantitative_parameters.*.critical_values' => 'nullable|array',
            'quantitative_parameters.*.critical_values.*.age_group' => 'nullable|string|in:neonate,infant,child,adolescent,adult,elderly,all_ages',
            'quantitative_parameters.*.critical_values.*.gender' => 'nullable|string|in:male,female,both',
            'quantitative_parameters.*.critical_values.*.pregnancy_status' => 'nullable|string|in:not_pregnant,pregnant,both',
            'quantitative_parameters.*.critical_values.*.trimester' => 'nullable|string|in:first,second,third,all',
            'quantitative_parameters.*.critical_values.*.critical_low' => 'nullable|numeric',
            'quantitative_parameters.*.critical_values.*.critical_high' => 'nullable|numeric',
            'quantitative_parameters.*.critical_values.*.panic_low' => 'nullable|numeric',
            'quantitative_parameters.*.critical_values.*.panic_high' => 'nullable|numeric',
            'quantitative_parameters.*.is_required' => 'boolean',
            'quantitative_parameters.*.is_critical' => 'boolean',
            'quantitative_parameters.*.allows_delta_check' => 'boolean',
            'qualitative_parameters' => 'nullable|array',
            'qualitative_parameters.*.parameter_name' => 'required_with:qualitative_parameters|string|max:255',
            'qualitative_parameters.*.parameter_code' => 'nullable|string|max:50',
            'qualitative_parameters.*.input_type' => 'required_with:qualitative_parameters|in:select,radio,checkbox',
            'qualitative_parameters.*.sort_order' => 'nullable|integer|min:1',
            'qualitative_parameters.*.options' => 'required_with:qualitative_parameters|array|min:1',
            'qualitative_parameters.*.options.*' => 'required|string|max:100',
            'qualitative_parameters.*.is_required' => 'boolean',
            'qualitative_parameters.*.is_critical' => 'boolean'
        ]);
        
        // Get the category name from category_id
        $category = LabTestCategory::findOrFail($validated['category_id']);
        $validated['category'] = $category->name;
        
        // Handle checkboxes (they send 'on' when checked, nothing when unchecked)
        $validated['created_by'] = auth()->id();
        $validated['nhis_covered'] = $request->has('nhis_covered') ? 1 : 0;
        $validated['is_active'] = $request->has('is_active') ? 1 : 0;
        
        // Remove parameter arrays from main template data
        $quantitativeParams = $validated['quantitative_parameters'] ?? [];
        $qualitativeParams = $validated['qualitative_parameters'] ?? [];
        unset($validated['quantitative_parameters'], $validated['qualitative_parameters']);
        
        DB::beginTransaction();
        try {
            $template = LabTestTemplate::create($validated);

            // Create quantitative parameters
            foreach ($quantitativeParams as $paramData) {
                // Auto-generate parameter_code if not provided
                $parameterCode = $paramData['parameter_code'];
                if (empty($parameterCode)) {
                    // Generate unique code from parameter name
                    $baseCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $paramData['parameter_name']), 0, 10));
                    $parameterCode = $baseCode . '_' . time() . '_' . rand(100, 999);
                }
                
                $parameter = new LabTestParameter([
                    'template_id' => $template->id,
                    'parameter_name' => $paramData['parameter_name'],
                    'parameter_code' => $parameterCode,
                    'data_type' => 'numeric',
                    'input_type' => 'number',
                    'unit' => $paramData['unit'] ?? null,
                    'decimal_places' => $paramData['decimal_places'] ?? 2,
                    'sort_order' => $paramData['sort_order'] ?? 1,
                    'is_required' => $paramData['is_required'] ?? false,
                    'is_critical' => $paramData['is_critical'] ?? false,
                    'allows_delta_check' => $paramData['allows_delta_check'] ?? false,
                    'reference_ranges' => $paramData['reference_ranges'] ?? [],
                    'critical_values' => $paramData['critical_values'] ?? [],
                    'is_active' => true,
                    'updated_by' => auth()->id()
                ]);
                $parameter->save();
            }
            
            // Create qualitative parameters
            foreach ($qualitativeParams as $paramData) {
                // Auto-generate parameter_code if not provided
                $parameterCode = $paramData['parameter_code'];
                if (empty($parameterCode)) {
                    // Generate unique code from parameter name
                    $baseCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $paramData['parameter_name']), 0, 10));
                    $parameterCode = $baseCode . '_' . time() . '_' . rand(100, 999);
                }
                
                $parameter = new LabTestParameter([
                    'template_id' => $template->id,
                    'parameter_name' => $paramData['parameter_name'],
                    'parameter_code' => $parameterCode,
                    'data_type' => 'text',
                    'input_type' => $paramData['input_type'],
                    'input_options' => $paramData['options'],
                    'sort_order' => $paramData['sort_order'] ?? 1,
                    'is_required' => $paramData['is_required'] ?? false,
                    'is_critical' => $paramData['is_critical'] ?? false,
                    'is_active' => true,
                    'updated_by' => auth()->id()
                ]);
                $parameter->save();
            }
            
            DB::commit();
            
            return redirect()->route('lab.templates.show', $template)
                ->with('success', 'Template created successfully with all parameters!');
                
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()->with('error', 'Error creating template: ' . $e->getMessage());
        }
    }
    
    public function editTemplate(LabTestTemplate $template)
    {
        $template->load('parameters.referenceRanges');
        $categories = LabTestCategory::active()->ordered()->get();
        $testTypesByCategory = LabTestType::where('is_active', true)
            ->orderBy('test_name')
            ->get()
            ->groupBy('category_id');
        
        return view('lab.templates.edit', compact('template', 'categories', 'testTypesByCategory'));
    }
    
    public function updateTemplate(Request $request, LabTestTemplate $template)
    {
        $validated = $request->validate([
            'template_code' => 'required|string|unique:lab_test_templates,template_code,' . $template->id,
            'template_name' => 'required|string|max:255',
            'category_id' => 'required|exists:lab_test_categories,id',
            'subcategory' => 'nullable|string',
            'description' => 'nullable|string',
            'template_content' => 'nullable|string',
            'template_type' => 'required|in:quantitative,qualitative,narrative,combined',
            'specimen_type' => 'required|string',
            'methodology' => 'nullable|string',
            'equipment_required' => 'nullable|string',
            'routine_tat_hours' => 'nullable|integer|min:1',
            'urgent_tat_hours' => 'nullable|integer|min:1',
            'stat_tat_hours' => 'nullable|integer|min:1',
            'cost' => 'nullable|numeric|min:0',
            'nhis_cost' => 'nullable|numeric|min:0',
            'quantitative_parameters' => 'nullable|array',
            'quantitative_parameters.*.parameter_name' => 'required_with:quantitative_parameters|string|max:255',
            'quantitative_parameters.*.parameter_code' => 'nullable|string|max:50',
            'quantitative_parameters.*.unit' => 'nullable|string|max:20',
            'quantitative_parameters.*.decimal_places' => 'nullable|integer|min:0|max:4',
            'quantitative_parameters.*.sort_order' => 'nullable|integer|min:1',
            'quantitative_parameters.*.reference_ranges' => 'nullable|array',
            'quantitative_parameters.*.reference_ranges.*.age_group' => 'required_with:quantitative_parameters.*.reference_ranges|string|in:neonate,infant,child,adolescent,adult,elderly,all_ages',
            'quantitative_parameters.*.reference_ranges.*.gender' => 'required_with:quantitative_parameters.*.reference_ranges|string|in:male,female,both',
            'quantitative_parameters.*.reference_ranges.*.pregnancy_status' => 'nullable|string|in:not_pregnant,pregnant,both',
            'quantitative_parameters.*.reference_ranges.*.trimester' => 'nullable|string|in:first,second,third,all',
            'quantitative_parameters.*.reference_ranges.*.min_value' => 'nullable|numeric',
            'quantitative_parameters.*.reference_ranges.*.max_value' => 'nullable|numeric',
            'quantitative_parameters.*.reference_ranges.*.unit' => 'nullable|string|max:20',
            'quantitative_parameters.*.critical_values' => 'nullable|array',
            'quantitative_parameters.*.critical_values.*.age_group' => 'nullable|string|in:neonate,infant,child,adolescent,adult,elderly,all_ages',
            'quantitative_parameters.*.critical_values.*.gender' => 'nullable|string|in:male,female,both',
            'quantitative_parameters.*.critical_values.*.pregnancy_status' => 'nullable|string|in:not_pregnant,pregnant,both',
            'quantitative_parameters.*.critical_values.*.trimester' => 'nullable|string|in:first,second,third,all',
            'quantitative_parameters.*.critical_values.*.critical_low' => 'nullable|numeric',
            'quantitative_parameters.*.critical_values.*.critical_high' => 'nullable|numeric',
            'quantitative_parameters.*.critical_values.*.panic_low' => 'nullable|numeric',
            'quantitative_parameters.*.critical_values.*.panic_high' => 'nullable|numeric',
            'quantitative_parameters.*.is_required' => 'boolean',
            'quantitative_parameters.*.is_critical' => 'boolean',
            'quantitative_parameters.*.allows_delta_check' => 'boolean',
            'qualitative_parameters' => 'nullable|array',
            'qualitative_parameters.*.parameter_name' => 'required_with:qualitative_parameters|string|max:255',
            'qualitative_parameters.*.parameter_code' => 'nullable|string|max:50',
            'qualitative_parameters.*.input_type' => 'required_with:qualitative_parameters|in:select,radio,checkbox',
            'qualitative_parameters.*.sort_order' => 'nullable|integer|min:1',
            'qualitative_parameters.*.options' => 'required_with:qualitative_parameters|array|min:1',
            'qualitative_parameters.*.options.*' => 'required|string|max:100',
            'qualitative_parameters.*.is_required' => 'boolean',
            'qualitative_parameters.*.is_critical' => 'boolean'
        ]);
        
        // Get the category name from category_id
        $category = LabTestCategory::findOrFail($validated['category_id']);
        $validated['category'] = $category->name;
        
        // Handle checkboxes (they send 'on' when checked, nothing when unchecked)
        $validated['updated_by'] = auth()->id();
        $validated['nhis_covered'] = $request->has('nhis_covered') ? 1 : 0;
        $validated['is_active'] = $request->has('is_active') ? 1 : 0;
        
        // Remove parameter arrays from main template data
        $quantitativeParams = $validated['quantitative_parameters'] ?? [];
        $qualitativeParams = $validated['qualitative_parameters'] ?? [];
        unset($validated['quantitative_parameters'], $validated['qualitative_parameters']);
        
        DB::beginTransaction();
        try {
            $template->update($validated);

            // Delete existing parameters
            $template->parameters()->delete();
            
            // Create quantitative parameters
            foreach ($quantitativeParams as $paramData) {
                // Auto-generate parameter_code if not provided
                $parameterCode = $paramData['parameter_code'];
                if (empty($parameterCode)) {
                    // Generate unique code from parameter name
                    $baseCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $paramData['parameter_name']), 0, 10));
                    $parameterCode = $baseCode . '_' . time() . '_' . rand(100, 999);
                }
                
                $parameter = new LabTestParameter([
                    'template_id' => $template->id,
                    'parameter_name' => $paramData['parameter_name'],
                    'parameter_code' => $parameterCode,
                    'data_type' => 'numeric',
                    'input_type' => 'number',
                    'unit' => $paramData['unit'] ?? null,
                    'decimal_places' => $paramData['decimal_places'] ?? 2,
                    'sort_order' => $paramData['sort_order'] ?? 1,
                    'is_required' => $paramData['is_required'] ?? false,
                    'is_critical' => $paramData['is_critical'] ?? false,
                    'allows_delta_check' => $paramData['allows_delta_check'] ?? false,
                    'reference_ranges' => $paramData['reference_ranges'] ?? [],
                    'critical_values' => $paramData['critical_values'] ?? [],
                    'is_active' => true,
                    'updated_by' => auth()->id()
                ]);
                $parameter->save();
            }
            
            // Create qualitative parameters
            foreach ($qualitativeParams as $paramData) {
                // Auto-generate parameter_code if not provided
                $parameterCode = $paramData['parameter_code'];
                if (empty($parameterCode)) {
                    // Generate unique code from parameter name
                    $baseCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $paramData['parameter_name']), 0, 10));
                    $parameterCode = $baseCode . '_' . time() . '_' . rand(100, 999);
                }
                
                $parameter = new LabTestParameter([
                    'template_id' => $template->id,
                    'parameter_name' => $paramData['parameter_name'],
                    'parameter_code' => $parameterCode,
                    'data_type' => 'text',
                    'input_type' => $paramData['input_type'],
                    'input_options' => $paramData['options'],
                    'sort_order' => $paramData['sort_order'] ?? 1,
                    'is_required' => $paramData['is_required'] ?? false,
                    'is_critical' => $paramData['is_critical'] ?? false,
                    'is_active' => true,
                    'updated_by' => auth()->id()
                ]);
                $parameter->save();
            }
            
            DB::commit();
            
            return redirect()->route('lab.templates.show', $template)
                ->with('success', 'Template updated successfully with all parameters!');
                
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()->with('error', 'Error updating template: ' . $e->getMessage());
        }
    }
    
    public function destroyTemplate(LabTestTemplate $template)
    {
        // Check if template has associated tests
        if ($template->tests()->count() > 0) {
            return redirect()->route('lab.templates')
                ->with('error', 'Cannot delete template with associated tests!');
        }
        
        $template->delete();
        
        return redirect()->route('lab.templates')
            ->with('success', 'Template deleted successfully!');
    }
    
    public function showTemplate(LabTestTemplate $template)
    {
        $template->load(['category', 'parameters.referenceRanges', 'createdBy']);
        
        return view('lab.templates.show', compact('template'));
    }
    
    // ========================================
    // PARAMETERS MANAGEMENT
    // ========================================
    
    public function createParameter($templateId)
    {
        $template = LabTestTemplate::findOrFail($templateId);
        
        return view('lab.parameters.create', compact('template'));
    }
    
    public function storeParameter(Request $request, $templateId)
    {
        $template = LabTestTemplate::findOrFail($templateId);
        
        $validated = $request->validate([
            'parameter_code' => 'required|string|unique:lab_test_parameters,parameter_code',
            'parameter_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'data_type' => 'required|in:numeric,text,boolean,date,time,datetime',
            'input_type' => 'required|in:text,number,select,radio,checkbox,textarea,rich_text',
            'input_options' => 'nullable|json',
            'unit' => 'nullable|string',
            'decimal_places' => 'nullable|integer|min:0|max:10',
            'is_required' => 'nullable|boolean',
            'is_critical' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);
        
        $validated['template_id'] = $template->id;
        $validated['is_required'] = $request->has('is_required');
        $validated['is_critical'] = $request->has('is_critical');
        $validated['is_active'] = $request->has('is_active');
        
        LabTestParameter::create($validated);
        
        return redirect()->route('lab.templates.show', $template)
            ->with('success', 'Parameter created successfully!');
    }
    
    public function editParameter(LabTestParameter $parameter)
    {
        $parameter->load('template');
        
        return view('lab.parameters.edit', compact('parameter'));
    }
    
    public function updateParameter(Request $request, LabTestParameter $parameter)
    {
        $validated = $request->validate([
            'parameter_code' => 'required|string|unique:lab_test_parameters,parameter_code,' . $parameter->id,
            'parameter_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'data_type' => 'required|in:numeric,text,boolean,date,time,datetime',
            'input_type' => 'required|in:text,number,select,radio,checkbox,textarea,rich_text',
            'input_options' => 'nullable|json',
            'unit' => 'nullable|string',
            'decimal_places' => 'nullable|integer|min:0|max:10',
            'is_required' => 'nullable|boolean',
            'is_critical' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);
        
        $validated['is_required'] = $request->has('is_required');
        $validated['is_critical'] = $request->has('is_critical');
        $validated['is_active'] = $request->has('is_active');
        
        $parameter->update($validated);
        
        return redirect()->route('lab.templates.show', $parameter->template)
            ->with('success', 'Parameter updated successfully!');
    }
    
    public function destroyParameter(LabTestParameter $parameter)
    {
        $templateId = $parameter->template_id;
        $parameter->delete();
        
        return redirect()->route('lab.templates.show', $templateId)
            ->with('success', 'Parameter deleted successfully!');
    }
    
    // ========================================
    // REFERENCE RANGES MANAGEMENT
    // ========================================
    
    public function createReferenceRange($parameterId)
    {
        $parameter = LabTestParameter::with('template')->findOrFail($parameterId);
        
        return view('lab.reference-ranges.create', compact('parameter'));
    }
    
    public function storeReferenceRange(Request $request, $parameterId)
    {
        $parameter = LabTestParameter::findOrFail($parameterId);
        
        $validated = $request->validate([
            'age_group' => 'required|string',
            'gender' => 'required|in:Male,Female,Both',
            'is_pregnant' => 'nullable|boolean',
            'pregnancy_trimester' => 'nullable|in:First,Second,Third',
            'min_value' => 'nullable|numeric',
            'max_value' => 'nullable|numeric',
            'min_operator' => 'nullable|in:>=,>',
            'max_operator' => 'nullable|in:<=,<',
            'unit' => 'nullable|string',
            'notes' => 'nullable|string',
            'source' => 'nullable|string',
            'reference' => 'nullable|string',
        ]);
        
        $validated['parameter_id'] = $parameter->id;
        $validated['is_pregnant'] = $request->has('is_pregnant');
        $validated['is_active'] = $request->has('is_active');
        
        LabReferenceRange::create($validated);
        
        return redirect()->route('lab.templates.show', $parameter->template_id)
            ->with('success', 'Reference range created successfully!');
    }
    
    public function editReferenceRange(LabReferenceRange $referenceRange)
    {
        $referenceRange->load('parameter.template');
        
        return view('lab.reference-ranges.edit', compact('referenceRange'));
    }
    
    public function updateReferenceRange(Request $request, LabReferenceRange $referenceRange)
    {
        $validated = $request->validate([
            'age_group' => 'required|string',
            'gender' => 'required|in:Male,Female,Both',
            'is_pregnant' => 'nullable|boolean',
            'pregnancy_trimester' => 'nullable|in:First,Second,Third',
            'min_value' => 'nullable|numeric',
            'max_value' => 'nullable|numeric',
            'min_operator' => 'nullable|in:>=,>',
            'max_operator' => 'nullable|in:<=,<',
            'unit' => 'nullable|string',
            'notes' => 'nullable|string',
            'source' => 'nullable|string',
            'reference' => 'nullable|string',
        ]);
        
        $validated['is_pregnant'] = $request->has('is_pregnant');
        $validated['is_active'] = $request->has('is_active');
        
        $referenceRange->update($validated);
        
        return redirect()->route('lab.templates.show', $referenceRange->parameter->template_id)
            ->with('success', 'Reference range updated successfully!');
    }
    
    public function destroyReferenceRange(LabReferenceRange $referenceRange)
    {
        $templateId = $referenceRange->parameter->template_id;
        $referenceRange->delete();
        
        return redirect()->route('lab.templates.show', $templateId)
            ->with('success', 'Reference range deleted successfully!');
    }
    
    // Test Types Management
    public function testTypes()
    {
        $testTypes = LabTestType::with(['createdBy', 'tests', 'category', 'template'])
            ->orderBy('category_id')
            ->orderBy('test_name')
            ->paginate(20);
            
        $categories = LabTestCategory::active()->ordered()->get();
            
        return view('lab.test-types.index', compact('testTypes', 'categories'));
    }
    
    public function createTestType()
    {
        $categories = LabTestCategory::active()->ordered()->get();
        $templates = LabTestTemplate::active()->orderBy('template_name')->get();
        return view('lab.test-types.create', compact('categories', 'templates'));
    }
    
    public function storeTestType(Request $request)
    {
        $validated = $request->validate([
            'test_code' => 'required|string|unique:lab_test_types,test_code',
            'test_name' => 'required|string|max:255',
            'category_id' => 'required|exists:lab_test_categories,id',
            'template_id' => 'nullable|exists:lab_test_templates,id',
            'subcategory' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'specimen_type' => 'required|string|max:100',
            'collection_method' => 'nullable|string|max:100',
            'routine_tat_hours' => 'nullable|integer|min:1',
            'urgent_tat_hours' => 'nullable|integer|min:1',
            'stat_tat_hours' => 'nullable|integer|min:1',
            'cost' => 'nullable|numeric|min:0',
            'nhis_cost' => 'nullable|numeric|min:0',
            'methodology' => 'nullable|string',
            'equipment_required' => 'nullable|string',
            'ghs_code' => 'nullable|string|max:50'
        ]);
        
        // Get the category name from category_id
        $category = LabTestCategory::findOrFail($validated['category_id']);
        $validated['category'] = $category->name;
        
        $validated['created_by'] = auth()->id();
        $validated['nhis_covered'] = $request->has('nhis_covered');
        $validated['requires_doctor_approval'] = $request->has('requires_doctor_approval');
        $validated['requires_consultant_review'] = $request->has('requires_consultant_review');
        $validated['requires_qc'] = $request->has('requires_qc');
        $validated['requires_verification'] = $request->has('requires_verification');
        $validated['ghs_mandatory'] = $request->has('ghs_mandatory');
        $validated['is_active'] = $request->has('is_active');
        
        $testType = LabTestType::create($validated);
        
        return redirect()->route('lab.test-types.show', $testType)
            ->with('success', 'Test type created successfully!');
    }
    
    public function showTestType(LabTestType $testType)
    {
        $testType->load(['createdBy', 'tests', 'labResults', 'template', 'consumableItems']);
        return view('lab.test-types.show', compact('testType'));
    }
    
    public function editTestType(LabTestType $testType)
    {
        $categories = LabTestCategory::active()->ordered()->get();
        $templates = LabTestTemplate::active()->orderBy('template_name')->get();
        $testType->load('consumableItems');
        $reagents = LabReagent::active()->orderBy('name')->get();
        $consumables = LabConsumable::active()->orderBy('name')->get();

        return view('lab.test-types.edit', compact('testType', 'categories', 'templates', 'reagents', 'consumables'));
    }

    public function syncTestTypeConsumables(Request $request, LabTestType $testType)
    {
        $validated = $request->validate([
            'items' => 'nullable|array',
            'items.*.item_type' => 'required_with:items|in:reagent,consumable',
            'items.*.item_id' => 'required_with:items|integer|min:1',
            'items.*.quantity_per_test' => 'required_with:items|numeric|min:0.01',
            'items.*.is_optional' => 'nullable|boolean',
            'items.*.notes' => 'nullable|string|max:500',
        ]);

        $items = $validated['items'] ?? [];

        foreach ($items as $index => $item) {
            $table = $item['item_type'] === 'reagent' ? 'lab_reagents' : 'lab_consumables';
            if (!DB::table($table)->where('id', $item['item_id'])->exists()) {
                return back()->with('error', 'Invalid inventory item on row ' . ($index + 1));
            }
        }

        $testType->consumableItems()->delete();

        foreach ($items as $item) {
            LabTestTypeItem::create([
                'lab_test_type_id' => $testType->id,
                'item_type' => $item['item_type'],
                'item_id' => $item['item_id'],
                'quantity_per_test' => $item['quantity_per_test'],
                'is_optional' => !empty($item['is_optional']),
                'notes' => $item['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);
        }

        return back()->with('success', 'Test consumables mapping saved.');
    }
    
    public function updateTestType(Request $request, LabTestType $testType)
    {
        $validated = $request->validate([
            'test_code' => 'required|string|unique:lab_test_types,test_code,' . $testType->id,
            'test_name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'template_id' => 'nullable|exists:lab_test_templates,id',
            'subcategory' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'specimen_type' => 'required|string|max:100',
            'collection_method' => 'nullable|string|max:100',
            'routine_tat_hours' => 'nullable|integer|min:1',
            'urgent_tat_hours' => 'nullable|integer|min:1',
            'stat_tat_hours' => 'nullable|integer|min:1',
            'cost' => 'nullable|numeric|min:0',
            'nhis_cost' => 'nullable|numeric|min:0',
            'methodology' => 'nullable|string',
            'equipment_required' => 'nullable|string',
            'ghs_code' => 'nullable|string|max:50'
        ]);
        
        $validated['updated_by'] = auth()->id();
        $validated['nhis_covered'] = $request->has('nhis_covered');
        $validated['requires_doctor_approval'] = $request->has('requires_doctor_approval');
        $validated['requires_consultant_review'] = $request->has('requires_consultant_review');
        $validated['requires_qc'] = $request->has('requires_qc');
        $validated['requires_verification'] = $request->has('requires_verification');
        $validated['ghs_mandatory'] = $request->has('ghs_mandatory');
        $validated['is_active'] = $request->has('is_active');
        
        $testType->update($validated);
        
        return redirect()->route('lab.test-types.show', $testType)
            ->with('success', 'Test type updated successfully!');
    }
    
    public function destroyTestType(LabTestType $testType)
    {
        // Check if test type is being used by any tests
        if ($testType->tests()->count() > 0) {
            return redirect()->route('lab.test-types')
                ->with('error', 'Cannot delete test type. It is being used by ' . $testType->tests()->count() . ' test(s).');
        }
        
        $testType->delete();
        
        return redirect()->route('lab.test-types')
            ->with('success', 'Test type deleted successfully!');
    }
    
    // ========================================
    // DYNAMIC PARAMETER MANAGEMENT
    // ========================================
    
    /**
     * Update parameter unit via AJAX
     */
    public function updateParameterUnit(LabTestParameter $parameter, Request $request)
    {
        try {
            $request->validate([
                'unit' => 'required|string|max:20'
            ]);
            
            $parameter->update([
                'unit' => $request->unit,
                'updated_by' => auth()->id()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Unit updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating unit: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Toggle parameter status via AJAX
     */
    public function toggleParameterStatus(LabTestParameter $parameter, Request $request)
    {
        try {
            $request->validate([
                'is_active' => 'required|boolean'
            ]);
            
            $parameter->update([
                'is_active' => $request->is_active,
                'updated_by' => auth()->id()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Parameter status updated successfully',
                'is_active' => $parameter->is_active
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating parameter status: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Bulk parameter actions
     */
    public function bulkParameterAction(Request $request)
    {
        try {
            $request->validate([
                'action' => 'required|in:activate,deactivate,delete',
                'parameter_ids' => 'required|array',
                'parameter_ids.*' => 'exists:lab_test_parameters,id'
            ]);
            
            $parameters = LabTestParameter::whereIn('id', $request->parameter_ids);
            $count = $parameters->count();
            
            switch ($request->action) {
                case 'activate':
                    $parameters->update(['is_active' => true, 'updated_by' => auth()->id()]);
                    $message = "Successfully activated {$count} parameter(s)";
                    break;
                    
                case 'deactivate':
                    $parameters->update(['is_active' => false, 'updated_by' => auth()->id()]);
                    $message = "Successfully deactivated {$count} parameter(s)";
                    break;
                    
                case 'delete':
                    $parameters->delete();
                    $message = "Successfully deleted {$count} parameter(s)";
                    break;
            }
            
            return response()->json([
                'success' => true,
                'message' => $message
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error performing bulk action: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Toggle reference range status via AJAX
     */
    public function toggleReferenceRangeStatus(LabReferenceRange $referenceRange, Request $request)
    {
        try {
            $request->validate([
                'is_active' => 'required|boolean'
            ]);
            
            $referenceRange->update([
                'is_active' => $request->is_active
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Reference range status updated successfully',
                'is_active' => $referenceRange->is_active
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating reference range status: ' . $e->getMessage()
            ], 500);
        }
    }
}

