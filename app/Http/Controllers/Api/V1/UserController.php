<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = User::with('shop');

        if (!$request->user()->isSuperAdmin()) {
            $query->where('shop_id', $request->user()->shop_id);
        }

        if ($request->has('shop_id') && $request->user()->isSuperAdmin()) {
            $query->where('shop_id', $request->shop_id);
        }

        return UserResource::collection($query->latest()->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8',
            'role' => ['required', Rule::in(['shop_admin', 'manager', 'salesperson'])],
            'shop_id' => 'required|exists:shops,id',
        ]);

        if (!$request->user()->isSuperAdmin()) {
            $validated['shop_id'] = $request->user()->shop_id;
            if ($validated['role'] === 'shop_admin' && !$request->user()->isShopAdmin()) {
                return response()->json(['message' => 'Cannot create shop admin'], 403);
            }
        }

        $user = User::create($validated);

        return response()->json([
            'message' => 'User created successfully',
            'user' => new UserResource($user->load('shop')),
        ], 201);
    }

    public function show(User $user, Request $request): UserResource
    {
        if (!$request->user()->isSuperAdmin() && $user->shop_id !== $request->user()->shop_id) {
            abort(403, 'Unauthorized');
        }

        return new UserResource($user->load('shop'));
    }

    public function update(Request $request, User $user): JsonResponse
    {
        if (!$request->user()->isSuperAdmin() && $user->shop_id !== $request->user()->shop_id) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'role' => ['sometimes', Rule::in(['shop_admin', 'manager', 'salesperson'])],
            'password' => 'sometimes|string|min:8',
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'User updated successfully',
            'user' => new UserResource($user->load('shop')),
        ]);
    }

    public function toggleActive(Request $request, User $user): JsonResponse
    {
        if (!$request->user()->isSuperAdmin() && $user->shop_id !== $request->user()->shop_id) {
            abort(403, 'Unauthorized');
        }

        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'Cannot deactivate your own account'], 422);
        }

        $user->update(['is_active' => !$user->is_active]);

        return response()->json([
            'message' => $user->is_active ? 'User activated' : 'User deactivated',
            'user' => new UserResource($user),
        ]);
    }
}
