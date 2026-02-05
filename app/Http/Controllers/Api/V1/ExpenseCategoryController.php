<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ExpenseCategoryResource;
use App\Models\ExpenseCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ExpenseCategoryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return ExpenseCategoryResource::collection(ExpenseCategory::withCount('expenses')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(['name' => 'required|string|max:100']);

        $category = ExpenseCategory::create($validated);

        return response()->json([
            'message' => 'Expense category created',
            'expense_category' => new ExpenseCategoryResource($category),
        ], 201);
    }

    public function update(Request $request, ExpenseCategory $expenseCategory): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'is_active' => 'boolean',
        ]);

        $expenseCategory->update($validated);

        return response()->json([
            'message' => 'Expense category updated',
            'expense_category' => new ExpenseCategoryResource($expenseCategory),
        ]);
    }

    public function destroy(ExpenseCategory $expenseCategory): JsonResponse
    {
        if ($expenseCategory->expenses()->exists()) {
            return response()->json(['message' => 'Cannot delete category with expenses'], 422);
        }

        $expenseCategory->delete();

        return response()->json(['message' => 'Expense category deleted']);
    }
}
