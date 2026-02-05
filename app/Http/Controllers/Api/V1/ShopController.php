<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ShopResource;
use App\Models\Shop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ShopController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $shops = Shop::latest()->get();
        return ShopResource::collection($shops);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:shops,code',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:10',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'gst_number' => 'nullable|string|max:20',
            'invoice_prefix' => 'required|string|max:10',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'currency' => 'nullable|string|max:3',
        ]);

        $shop = Shop::create($validated);

        return response()->json([
            'message' => 'Shop created successfully',
            'shop' => new ShopResource($shop),
        ], 201);
    }

    public function show(Shop $shop): ShopResource
    {
        return new ShopResource($shop);
    }

    public function update(Request $request, Shop $shop): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:20|unique:shops,code,' . $shop->id,
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:10',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'gst_number' => 'nullable|string|max:20',
            'invoice_prefix' => 'sometimes|string|max:10',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'currency' => 'nullable|string|max:3',
            'logo_url' => 'nullable|string|max:500',
        ]);

        $shop->update($validated);

        return response()->json([
            'message' => 'Shop updated successfully',
            'shop' => new ShopResource($shop),
        ]);
    }

    public function toggleActive(Shop $shop): JsonResponse
    {
        $shop->update(['is_active' => !$shop->is_active]);

        return response()->json([
            'message' => $shop->is_active ? 'Shop activated' : 'Shop deactivated',
            'shop' => new ShopResource($shop),
        ]);
    }

    public function stats(Shop $shop): JsonResponse
    {
        $today = now()->toDateString();

        $todaySales = $shop->hasMany(\App\Models\Sale::class)
            ->where('sale_date', $today)
            ->where('status', 'completed')
            ->sum('total_amount');

        $totalProducts = $shop->hasMany(\App\Models\Product::class)
            ->where('is_active', true)
            ->count();

        $totalUsers = $shop->users()->where('is_active', true)->count();

        return response()->json([
            'shop' => new ShopResource($shop),
            'stats' => [
                'today_sales' => (float) $todaySales,
                'total_products' => $totalProducts,
                'total_users' => $totalUsers,
            ],
        ]);
    }
}
