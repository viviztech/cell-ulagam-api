<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ExpenseResource;
use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ExpenseController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Expense::with(['expenseCategory', 'user']);

        if ($request->has('from') && $request->has('to')) {
            $query->whereBetween('expense_date', [$request->from, $request->to]);
        }
        if ($request->has('expense_category_id')) {
            $query->where('expense_category_id', $request->expense_category_id);
        }

        return ExpenseResource::collection($query->latest()->paginate($request->get('per_page', 20)));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'amount' => 'required|numeric|min:0.01',
            'expense_date' => 'required|date',
            'description' => 'nullable|string',
            'payment_method' => 'required|in:cash,upi,card,bank_transfer',
            'reference_number' => 'nullable|string|max:100',
        ]);

        $shopId = $request->user()->shop_id ?? app('current_shop_id');

        $cashSessionId = null;
        if ($validated['payment_method'] === 'cash') {
            $session = CashSession::where('user_id', $request->user()->id)
                ->where('status', 'open')
                ->first();

            if ($session) {
                $cashSessionId = $session->id;

                CashMovement::create([
                    'shop_id' => $shopId,
                    'cash_session_id' => $session->id,
                    'user_id' => $request->user()->id,
                    'type' => 'cash_out',
                    'amount' => $validated['amount'],
                    'reason' => 'Expense: ' . ($validated['description'] ?? 'N/A'),
                    'reference_type' => 'expenses',
                ]);

                $session->increment('cash_out_total', $validated['amount']);
            }
        }

        $expense = Expense::create([
            'shop_id' => $shopId,
            'expense_category_id' => $validated['expense_category_id'],
            'user_id' => $request->user()->id,
            'cash_session_id' => $cashSessionId,
            'amount' => $validated['amount'],
            'expense_date' => $validated['expense_date'],
            'description' => $validated['description'] ?? null,
            'payment_method' => $validated['payment_method'],
            'reference_number' => $validated['reference_number'] ?? null,
        ]);

        return response()->json([
            'message' => 'Expense recorded',
            'expense' => new ExpenseResource($expense->load('expenseCategory')),
        ], 201);
    }

    public function show(Expense $expense): ExpenseResource
    {
        return new ExpenseResource($expense->load(['expenseCategory', 'user']));
    }

    public function update(Request $request, Expense $expense): JsonResponse
    {
        $validated = $request->validate([
            'expense_category_id' => 'sometimes|exists:expense_categories,id',
            'amount' => 'sometimes|numeric|min:0.01',
            'expense_date' => 'sometimes|date',
            'description' => 'nullable|string',
            'payment_method' => 'sometimes|in:cash,upi,card,bank_transfer',
            'reference_number' => 'nullable|string|max:100',
        ]);

        $expense->update($validated);

        return response()->json([
            'message' => 'Expense updated',
            'expense' => new ExpenseResource($expense->load('expenseCategory')),
        ]);
    }

    public function destroy(Expense $expense): JsonResponse
    {
        $expense->delete();
        return response()->json(['message' => 'Expense deleted']);
    }
}
