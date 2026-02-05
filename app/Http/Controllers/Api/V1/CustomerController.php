<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CustomerController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Customer::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return CustomerResource::collection($query->latest()->paginate($request->get('per_page', 20)));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $customer = Customer::create($validated);

        return response()->json([
            'message' => 'Customer created successfully',
            'customer' => new CustomerResource($customer),
        ], 201);
    }

    public function show(Customer $customer): CustomerResource
    {
        return new CustomerResource($customer->load('sales'));
    }

    public function update(Request $request, Customer $customer): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $customer->update($validated);

        return response()->json([
            'message' => 'Customer updated successfully',
            'customer' => new CustomerResource($customer),
        ]);
    }

    public function destroy(Customer $customer): JsonResponse
    {
        if ($customer->sales()->exists()) {
            return response()->json(['message' => 'Cannot delete customer with sales history'], 422);
        }

        $customer->delete();

        return response()->json(['message' => 'Customer deleted successfully']);
    }

    public function search(Request $request): AnonymousResourceCollection
    {
        $request->validate(['q' => 'required|string|min:1']);

        $customers = Customer::where('name', 'like', "%{$request->q}%")
            ->orWhere('phone', 'like', "%{$request->q}%")
            ->limit(20)
            ->get();

        return CustomerResource::collection($customers);
    }
}
