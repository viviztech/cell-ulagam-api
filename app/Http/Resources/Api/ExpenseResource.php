<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'expense_date' => $this->expense_date,
            'description' => $this->description,
            'payment_method' => $this->payment_method,
            'reference_number' => $this->reference_number,
            'expense_category' => new ExpenseCategoryResource($this->whenLoaded('expenseCategory')),
            'user' => new UserResource($this->whenLoaded('user')),
            'created_at' => $this->created_at,
        ];
    }
}
