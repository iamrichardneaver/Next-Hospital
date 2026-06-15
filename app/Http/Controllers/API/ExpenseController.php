<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AssertsExpenseDepartment;
use App\Http\Controllers\Concerns\ResolvesUserBranch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Services\AccountingExportService;
use App\Services\AppNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ExpenseController extends Controller
{
    use AssertsExpenseDepartment;
    use ResolvesUserBranch;

    public function __construct(
        protected AccountingExportService $exportService,
        protected AppNotificationService $appNotificationService,
    ) {}

    public function categories(Request $request): JsonResponse
    {
        $user = $request->user();
        $forSubmit = $request->boolean('operational_only', false);

        $query = ExpenseCategory::active()->orderBy('name');
        if ($forSubmit || $user->can('create_expenses')) {
            $query->whereNotIn('code', Expense::INVENTORY_CATEGORY_CODES);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'categories' => $query->get(['id', 'name', 'code']),
                'departments' => Expense::DEPARTMENTS,
            ],
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user->can('view_expenses') && !$user->can('manage_expenses') && !$user->can('approve_expenses')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $branchId = $this->resolveBranchFilter($request);
        $query = $this->buildFilteredExpenseQuery($request, $branchId);

        $stats = [
            'total' => (clone $query)->sum('amount'),
            'approved' => (clone $query)->whereIn('status', ['approved', 'paid'])->sum('amount'),
            'pending' => (clone $query)->where('status', 'pending')->count(),
        ];

        $export = $this->exportService->resolveExport($request->get('export'));
        if ($export === 'csv') {
            $rows = (clone $query)->latest('expense_date')->get()->map(fn ($e) => [
                $e->expense_reference ?? (string) $e->id,
                $e->expense_date?->format('Y-m-d'),
                $e->category?->name ?? '',
                $e->getDepartmentLabel(),
                $e->isInventoryAuto() ? 'Inventory PO' : 'Manual',
                $e->description,
                $e->vendor,
                $e->branch?->name ?? '',
                number_format((float) $e->amount, 2),
                $e->status,
            ])->all();

            return $this->exportService->streamCsv(
                ['Reference', 'Date', 'Category', 'Department', 'Source', 'Description', 'Vendor', 'Branch', 'Amount (GHS)', 'Status'],
                $rows,
                'expenses'
            );
        }

        $expenses = $query->latest('expense_date')->paginate(min((int) $request->get('per_page', 20), 50));

        return response()->json([
            'success' => true,
            'data' => $expenses,
            'stats' => $stats,
        ]);
    }

    public function myExpenses(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->can('view_own_expenses')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $branchId = $this->resolveUserBranchId(['create_expenses', 'view_own_expenses']);

        $query = Expense::with(['category:id,name,code', 'branch:id,name', 'approver:id,first_name,last_name'])
            ->ownedBy($user->id)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status));

        $stats = [
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'approved' => (clone $query)->whereIn('status', ['approved', 'paid'])->count(),
            'rejected' => (clone $query)->where('status', 'rejected')->count(),
        ];

        $expenses = $query->latest('expense_date')->paginate(min((int) $request->get('per_page', 15), 50));

        return response()->json([
            'success' => true,
            'data' => $expenses,
            'stats' => $stats,
        ]);
    }

    public function show(Request $request, Expense $expense): JsonResponse
    {
        $this->assertExpenseAccess($expense);
        $expense->load(['category', 'branch', 'creator', 'approver']);

        return response()->json([
            'success' => true,
            'data' => $expense,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->can('create_expenses')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'category_id' => 'required|exists:expense_categories,id',
            'department' => 'required|in:' . implode(',', array_keys(Expense::DEPARTMENTS)),
            'amount' => 'required|numeric|min:0.01',
            'expense_date' => 'required|date|before_or_equal:today',
            'description' => 'required|string|max:500',
            'reference' => 'nullable|string|max:100',
            'payment_method' => 'nullable|string|max:50',
            'vendor' => 'nullable|string|max:150',
            'notes' => 'nullable|string|max:1000',
        ]);

        $category = ExpenseCategory::findOrFail($validated['category_id']);
        if (in_array($category->code, Expense::INVENTORY_CATEGORY_CODES, true)) {
            return response()->json([
                'message' => 'Inventory purchase categories are auto-recorded on PO receive.',
            ], 422);
        }

        $this->assertUserCanSubmitForDepartment($validated['department']);

        $expense = Expense::create([
            ...$validated,
            'branch_id' => $this->resolveUserBranchId(['create_expenses']),
            'created_by' => $user->id,
        ]);
        $expense->status = 'pending';
        $expense->save();

        $expense->load('category:id,name,code');

        return response()->json([
            'success' => true,
            'message' => 'Expense submitted for approval.',
            'data' => $expense,
        ], 201);
    }

    public function update(Request $request, Expense $expense): JsonResponse
    {
        $this->assertExpenseAccess($expense);

        if (!$expense->isEditable()) {
            return response()->json(['message' => 'This expense cannot be edited in its current status.'], 422);
        }

        $validated = $request->validate([
            'category_id' => 'required|exists:expense_categories,id',
            'department' => 'nullable|in:' . implode(',', array_keys(Expense::DEPARTMENTS)),
            'amount' => 'required|numeric|min:0.01',
            'expense_date' => 'required|date|before_or_equal:today',
            'description' => 'required|string|max:500',
            'reference' => 'nullable|string|max:100',
            'payment_method' => 'nullable|string|max:50',
            'vendor' => 'nullable|string|max:150',
            'notes' => 'nullable|string|max:1000',
            'submit_action' => 'nullable|in:draft,pending',
        ]);

        $expense->update($validated);

        if ($request->input('submit_action') === 'draft') {
            $expense->status = 'draft';
            $expense->save();
        } elseif ($expense->status === 'draft' && $request->input('submit_action') === 'pending') {
            $expense->status = 'pending';
            $expense->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Expense updated successfully.',
            'data' => $expense->fresh(['category', 'branch', 'creator', 'approver']),
        ]);
    }

    public function destroy(Request $request, Expense $expense): JsonResponse
    {
        $this->assertExpenseAccess($expense);
        $user = $request->user();

        if (!$user->can('manage_expenses') && (int) $expense->created_by !== (int) $user->id) {
            return response()->json(['message' => 'You can only delete your own expense submissions.'], 403);
        }

        if (!in_array($expense->status, ['draft', 'pending', 'rejected'], true)) {
            return response()->json(['message' => 'Only draft, pending, or rejected expenses can be deleted.'], 422);
        }

        $expense->delete();

        return response()->json([
            'success' => true,
            'message' => 'Expense deleted successfully.',
        ]);
    }

    public function approve(Request $request, Expense $expense): JsonResponse
    {
        $this->assertExpenseAccess($expense);
        $this->assertCanApprove($expense);

        if ($expense->status !== 'pending') {
            return response()->json(['message' => 'Only pending expenses can be approved.'], 422);
        }

        $expense->update([
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);
        $expense->status = 'approved';
        $expense->save();

        try {
            $this->appNotificationService->notifyExpenseDecision(
                $expense->fresh(['creator', 'category']),
                'approved',
                $request->user()->id
            );
        } catch (\Throwable $e) {
            \Log::error('Failed to send expense approval notification: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Expense approved successfully.',
            'data' => $expense->fresh(['category', 'branch', 'creator', 'approver']),
        ]);
    }

    public function reject(Request $request, Expense $expense): JsonResponse
    {
        $this->assertExpenseAccess($expense);
        $this->assertCanApprove($expense);

        if ($expense->status !== 'pending') {
            return response()->json(['message' => 'Only pending expenses can be rejected.'], 422);
        }

        $validated = $request->validate(['rejection_reason' => 'required|string|max:500']);

        $expense->update([
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);
        $expense->status = 'rejected';
        $expense->save();

        try {
            $this->appNotificationService->notifyExpenseDecision(
                $expense->fresh(['creator', 'category']),
                'rejected',
                $request->user()->id
            );
        } catch (\Throwable $e) {
            \Log::error('Failed to send expense rejection notification: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Expense rejected.',
            'data' => $expense->fresh(['category', 'branch', 'creator', 'approver']),
        ]);
    }

    public function markPaid(Request $request, Expense $expense): JsonResponse
    {
        $this->assertExpenseAccess($expense);

        if (!$request->user()->can('manage_expenses')) {
            return response()->json(['message' => 'Only accountants can mark expenses as paid.'], 403);
        }

        if ($expense->status !== 'approved') {
            return response()->json(['message' => 'Only approved expenses can be marked as paid.'], 422);
        }

        $expense->status = 'paid';
        $expense->save();

        return response()->json([
            'success' => true,
            'message' => 'Expense marked as paid.',
            'data' => $expense->fresh(['category', 'branch', 'creator', 'approver']),
        ]);
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

    protected function resolveBranchFilter(Request $request): ?int
    {
        if ($request->user()->hasRole('super_admin')) {
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
}
