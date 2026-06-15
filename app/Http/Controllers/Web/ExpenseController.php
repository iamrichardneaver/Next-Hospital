<?php

namespace App\Http\Controllers\Web;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AssertsExpenseDepartment;
use App\Http\Controllers\Concerns\ExportsListData;
use App\Http\Controllers\Concerns\ResolvesUserBranch;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Services\AccountingExportService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ExpenseController extends Controller
{
    use AssertsExpenseDepartment;
    use ExportsListData;
    use ResolvesUserBranch;

    public function __construct(
        protected AccountingExportService $exportService
    ) {}

    public function index(Request $request)
    {
        $branchId = $this->resolveBranchFilter($request);
        $query = $this->buildFilteredExpenseQuery($request, $branchId);

        $stats = [
            'total' => (clone $query)->sum('amount'),
            'approved' => (clone $query)->whereIn('status', ['approved', 'paid'])->sum('amount'),
            'pending' => (clone $query)->where('status', 'pending')->count(),
        ];

        $export = $this->exportService->resolveExport($request->get('export'));
        if ($export === 'csv') {
            return $this->exportExpensesCsv($request, $query, $branchId);
        }
        if ($export === 'pdf') {
            return $this->exportExpensesPdf($request, $query, $stats, $branchId);
        }

        $expenses = $query->latest('expense_date')->paginate(20)->withQueryString();

        $categories = ExpenseCategory::active()->orderBy('name')->get();
        $branches = $this->getBranches();
        $departments = Expense::DEPARTMENTS;

        return view('accounting.expenses.index', compact(
            'expenses',
            'stats',
            'categories',
            'branches',
            'branchId',
            'departments'
        ));
    }

    public function myExpenses(Request $request)
    {
        $branchId = $this->resolveUserBranchId(['create_expenses', 'view_own_expenses']);

        $query = Expense::with(['category', 'branch', 'approver'])
            ->ownedBy(auth()->id())
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId));

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $stats = [
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'approved' => (clone $query)->whereIn('status', ['approved', 'paid'])->count(),
            'rejected' => (clone $query)->where('status', 'rejected')->count(),
        ];

        $expenses = $query->latest('expense_date')->paginate(15)->withQueryString();

        return view('expenses.my-expenses', compact('expenses', 'stats', 'branchId'));
    }

    public function create()
    {
        $categories = ExpenseCategory::active()->orderBy('name')->get();
        $branches = $this->getBranches();
        $defaultBranchId = auth()->user()->hasRole('super_admin')
            ? null
            : $this->resolveUserBranchId(['manage_expenses', 'view_expenses']);

        return view('accounting.expenses.create', [
            'categories' => $categories,
            'branches' => $branches,
            'defaultBranchId' => $defaultBranchId,
            'paymentMethods' => PaymentMethod::staffMethods(),
            'departments' => Expense::DEPARTMENTS,
            'isStaffSubmit' => false,
        ]);
    }

    public function submitCreate(?string $department = null)
    {
        $department = $this->resolveDepartment($department);
        $this->assertUserCanSubmitForDepartment($department);
        $categories = $this->getOperationalCategories();
        $defaultBranchId = $this->resolveUserBranchId(['create_expenses']);

        return view('expenses.submit', [
            'categories' => $categories,
            'department' => $department,
            'departmentLabel' => Expense::DEPARTMENTS[$department] ?? ucfirst($department),
            'defaultBranchId' => $defaultBranchId,
            'paymentMethods' => PaymentMethod::staffMethods(),
            'departments' => Expense::DEPARTMENTS,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateExpense($request, staffSubmit: false);

        $branchId = auth()->user()->hasRole('super_admin')
            ? $validated['branch_id']
            : $this->resolveUserBranchId(['manage_expenses']);

        $validated['branch_id'] = $branchId;
        $validated['created_by'] = auth()->id();
        $status = $request->input('submit_action') === 'draft' ? 'draft' : 'pending';

        $expense = Expense::create($validated);
        $expense->status = $status;
        $expense->save();

        return redirect()
            ->route('accounting.expenses.index')
            ->with('success', 'Expense recorded successfully.');
    }

    public function submitStore(Request $request)
    {
        $validated = $this->validateExpense($request, staffSubmit: true);

        $validated['branch_id'] = $this->resolveUserBranchId(['create_expenses']);
        $validated['created_by'] = auth()->id();
        $validated['department'] = $this->resolveDepartment($validated['department'] ?? $request->input('department'));
        $this->assertUserCanSubmitForDepartment($validated['department']);

        $expense = Expense::create($validated);
        $expense->status = 'pending';
        $expense->save();

        return redirect()
            ->route('expenses.my')
            ->with('success', 'Expense submitted for accountant approval.');
    }

    public function show(Expense $expense)
    {
        $this->assertExpenseAccess($expense);
        $expense->load(['category', 'branch', 'creator', 'approver']);

        $backRoute = auth()->user()->can('view_expenses') || auth()->user()->can('manage_expenses')
            ? route('accounting.expenses.index')
            : route('expenses.my');

        return view('accounting.expenses.show', compact('expense', 'backRoute'));
    }

    public function edit(Expense $expense)
    {
        $this->assertExpenseAccess($expense);

        if (!$expense->isEditable()) {
            return redirect()
                ->route('accounting.expenses.show', $expense)
                ->with('error', 'This expense cannot be edited in its current status.');
        }

        $categories = ExpenseCategory::active()->orderBy('name')->get();
        $branches = $this->getBranches();

        return view('accounting.expenses.edit', [
            'expense' => $expense,
            'categories' => $categories,
            'branches' => $branches,
            'paymentMethods' => PaymentMethod::staffMethods(),
            'departments' => Expense::DEPARTMENTS,
        ]);
    }

    public function update(Request $request, Expense $expense)
    {
        $this->assertExpenseAccess($expense);

        if (!$expense->isEditable()) {
            return redirect()
                ->route('accounting.expenses.show', $expense)
                ->with('error', 'This expense cannot be edited in its current status.');
        }

        $validated = $this->validateExpense($request, $expense, staffSubmit: false);

        if (!auth()->user()->hasRole('super_admin')) {
            $validated['branch_id'] = $expense->branch_id;
        }

        $status = null;
        if ($request->input('submit_action') === 'draft') {
            $status = 'draft';
        } elseif ($expense->status === 'draft') {
            $status = 'pending';
        }

        $expense->update($validated);

        if ($status !== null) {
            $expense->status = $status;
            $expense->save();
        }

        return redirect()
            ->route('accounting.expenses.show', $expense)
            ->with('success', 'Expense updated successfully.');
    }

    public function destroy(Expense $expense)
    {
        $this->assertExpenseAccess($expense);

        if (!auth()->user()->can('manage_expenses') && (int) $expense->created_by !== (int) auth()->id()) {
            abort(403, 'You can only delete your own expense submissions.');
        }

        if (!in_array($expense->status, ['draft', 'pending', 'rejected'], true)) {
            return redirect()
                ->route('accounting.expenses.show', $expense)
                ->with('error', 'Only draft, pending, or rejected expenses can be deleted.');
        }

        $expense->delete();

        $redirect = auth()->user()->can('manage_expenses')
            ? route('accounting.expenses.index')
            : route('expenses.my');

        return redirect()
            ->to($redirect)
            ->with('success', 'Expense deleted successfully.');
    }

    public function approve(Expense $expense)
    {
        $this->assertExpenseAccess($expense);
        $this->assertCanApprove($expense);

        if ($expense->status !== 'pending') {
            return back()->with('error', 'Only pending expenses can be approved.');
        }

        $expense->update([
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);
        $expense->status = 'approved';
        $expense->save();

        return back()->with('success', 'Expense approved successfully.');
    }

    public function reject(Request $request, Expense $expense)
    {
        $this->assertExpenseAccess($expense);
        $this->assertCanApprove($expense);

        if ($expense->status !== 'pending') {
            return back()->with('error', 'Only pending expenses can be rejected.');
        }

        $request->validate(['rejection_reason' => 'required|string|max:500']);

        $expense->update([
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'rejection_reason' => $request->rejection_reason,
        ]);
        $expense->status = 'rejected';
        $expense->save();

        return back()->with('success', 'Expense rejected.');
    }

    public function markPaid(Expense $expense)
    {
        $this->assertExpenseAccess($expense);

        if (!auth()->user()->can('manage_expenses')) {
            abort(403, 'Only accountants can mark expenses as paid.');
        }

        if ($expense->status !== 'approved') {
            return back()->with('error', 'Only approved expenses can be marked as paid.');
        }

        $expense->status = 'paid';
        $expense->save();

        return back()->with('success', 'Expense marked as paid.');
    }

    protected function validateExpense(Request $request, ?Expense $expense = null, bool $staffSubmit = false): array
    {
        $branchRule = auth()->user()->hasRole('super_admin') && !$staffSubmit
            ? 'required|exists:branches,id'
            : 'nullable';

        $departmentRule = $staffSubmit
            ? 'required|in:' . implode(',', array_keys(Expense::DEPARTMENTS))
            : 'nullable|in:' . implode(',', array_keys(Expense::DEPARTMENTS));

        $validated = $request->validate([
            'category_id' => 'required|exists:expense_categories,id',
            'branch_id' => $branchRule,
            'department' => $departmentRule,
            'amount' => 'required|numeric|min:0.01',
            'expense_date' => 'required|date|before_or_equal:today',
            'description' => 'required|string|max:500',
            'reference' => 'nullable|string|max:100',
            'payment_method' => 'nullable|string|max:50',
            'vendor' => 'nullable|string|max:150',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($staffSubmit) {
            $category = ExpenseCategory::findOrFail($validated['category_id']);
            if (in_array($category->code, Expense::INVENTORY_CATEGORY_CODES, true)) {
                throw ValidationException::withMessages([
                    'category_id' => 'Inventory purchase categories are auto-recorded on PO receive.',
                ]);
            }
        }

        return $validated;
    }

    protected function buildFilteredExpenseQuery(Request $request, ?int $branchId)
    {
        $query = Expense::with(['category', 'branch', 'creator', 'approver'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId));

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('department')) {
            $query->forDepartment($request->department);
        }

        if ($request->filled('source')) {
            if ($request->source === 'inventory') {
                $inventoryCategoryIds = ExpenseCategory::whereIn('code', Expense::INVENTORY_CATEGORY_CODES)->pluck('id');
                $query->whereIn('category_id', $inventoryCategoryIds);
            } elseif ($request->source === 'manual') {
                $query->manualOperational();
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->byDateRange($request->start_date, $request->end_date);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhere('vendor', 'like', "%{$search}%")
                    ->orWhere('expense_reference', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    protected function exportExpensesCsv(Request $request, $query, ?int $branchId)
    {
        return $this->exportFromQuery(
            $request,
            (clone $query)->latest('expense_date'),
            [
                'Reference' => fn ($e) => $e->expense_reference ?? (string) $e->id,
                'Date' => fn ($e) => $this->formatExportDate($e->expense_date),
                'Category' => fn ($e) => $e->category?->name ?? '',
                'Department' => fn ($e) => $e->getDepartmentLabel(),
                'Source' => fn ($e) => $e->isInventoryAuto() ? 'Inventory PO' : 'Manual',
                'Description' => 'description',
                'Vendor' => 'vendor',
                'Branch' => fn ($e) => $e->branch?->name ?? '',
                'Amount (GHS)' => fn ($e) => number_format((float) $e->amount, 2),
                'Status' => 'status',
            ],
            'expenses',
            null
        );
    }

    protected function exportExpensesPdf(Request $request, $query, array $stats, ?int $branchId)
    {
        $expenses = (clone $query)->latest('expense_date')->get();

        return $this->exportService->downloadPdf('accounting.pdf.report', [
            'documentTitle' => 'Expenses Report',
            'filterSummary' => $this->exportService->buildFilterSummary(
                $branchId,
                $request->get('start_date'),
                $request->get('end_date')
            ),
            'branch' => $branchId ? Branch::find($branchId) : null,
            'summaryLines' => [
                ['label' => 'Filtered Total', 'value' => 'GH₵' . number_format($stats['total'], 2)],
                ['label' => 'Approved/Paid', 'value' => 'GH₵' . number_format($stats['approved'], 2)],
                ['label' => 'Pending Approval', 'value' => number_format($stats['pending'])],
            ],
            'sections' => [
                [
                    'title' => 'Expense Line Items',
                    'headers' => ['Ref', 'Date', 'Category', 'Department', 'Description', 'Amount (GH₵)', 'Status'],
                    'align' => ['', '', '', '', '', 'right', ''],
                    'rows' => $expenses->map(fn ($e) => [
                        $e->expense_reference ?? $e->id,
                        $this->formatExportDate($e->expense_date),
                        $e->category?->name ?? '—',
                        $e->getDepartmentLabel(),
                        \Illuminate\Support\Str::limit($e->description, 60),
                        number_format((float) $e->amount, 2),
                        ucfirst($e->status),
                    ])->all(),
                    'footer' => ['', '', '', '', 'Total', number_format($stats['total'], 2), ''],
                ],
            ],
        ], $this->exportService->pdfFilename(
            'expenses',
            $request->get('start_date'),
            $request->get('end_date')
        ));
    }

    protected function resolveBranchFilter(Request $request): ?int
    {
        if (auth()->user()->hasRole('super_admin')) {
            return $request->filled('branch_id') ? (int) $request->branch_id : null;
        }

        return $this->resolveUserBranchId(['view_expenses', 'manage_expenses', 'approve_expenses']);
    }

    protected function assertExpenseAccess(Expense $expense): void
    {
        $user = auth()->user();

        if ($user->hasRole('super_admin')) {
            return;
        }

        if ($user->can('view_expenses') || $user->can('manage_expenses') || $user->can('approve_expenses')) {
            if ($expense->branch_id) {
                $this->assertResourceInUserBranch((int) $expense->branch_id, ['view_expenses', 'manage_expenses', 'approve_expenses']);
            }

            return;
        }

        if ($user->can('view_own_expenses') && (int) $expense->created_by === (int) $user->id) {
            if ($expense->branch_id) {
                $this->assertResourceInUserBranch((int) $expense->branch_id, ['create_expenses', 'view_own_expenses']);
            }

            return;
        }

        abort(403, 'You do not have access to this expense.');
    }

    protected function assertCanApprove(Expense $expense): void
    {
        $user = auth()->user();

        if (!$user->can('approve_expenses') && !$user->can('manage_expenses')) {
            abort(403, 'You do not have permission to approve expenses.');
        }

        if ((int) $expense->created_by === (int) $user->id && !$user->hasRole(['admin', 'super_admin'])) {
            abort(403, 'You cannot approve your own expense submission.');
        }
    }

    protected function resolveDepartment(?string $department): string
    {
        $department = $department ?: $this->inferDepartmentFromRole();

        if (!array_key_exists($department, Expense::DEPARTMENTS)) {
            abort(404, 'Unknown department for expense submission.');
        }

        return $department;
    }

    protected function getOperationalCategories()
    {
        return ExpenseCategory::active()
            ->whereNotIn('code', Expense::INVENTORY_CATEGORY_CODES)
            ->orderBy('name')
            ->get();
    }

    protected function getBranches()
    {
        return Branch::where('is_active', true)->orderBy('name')->get();
    }
}
