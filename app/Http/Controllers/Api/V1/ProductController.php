<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ProductResource;
use App\Models\Product;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function __construct(protected StockService $stockService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Product::with(['category', 'supplier'])->withCount('availableImeis');

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->has('brand')) {
            $query->where('brand', $request->brand);
        }
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        return ProductResource::collection($query->latest()->paginate($request->get('per_page', 20)));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'name' => 'required|string|max:255',
            'brand' => 'nullable|string|max:100',
            'network_type' => 'nullable|string|max:10',
            'variant' => 'nullable|string|max:50',
            'sku' => 'nullable|string|max:100',
            'barcode' => 'nullable|string|max:100',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'stock_quantity' => 'integer|min:0',
            'min_stock_alert' => 'integer|min:0',
            'has_imei' => 'boolean',
            'description' => 'nullable|string',
        ]);

        $product = Product::create($validated);

        return response()->json([
            'message' => 'Product created successfully',
            'product' => new ProductResource($product->load(['category', 'supplier'])),
        ], 201);
    }

    public function show(Product $product): ProductResource
    {
        return new ProductResource(
            $product->load(['category', 'supplier', 'imeis'])->loadCount('availableImeis')
        );
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => 'sometimes|exists:categories,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'name' => 'sometimes|string|max:255',
            'brand' => 'nullable|string|max:100',
            'network_type' => 'nullable|string|max:10',
            'variant' => 'nullable|string|max:50',
            'sku' => 'nullable|string|max:100',
            'barcode' => 'nullable|string|max:100',
            'purchase_price' => 'sometimes|numeric|min:0',
            'selling_price' => 'sometimes|numeric|min:0',
            'min_stock_alert' => 'integer|min:0',
            'has_imei' => 'boolean',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $product->update($validated);

        return response()->json([
            'message' => 'Product updated successfully',
            'product' => new ProductResource($product->load(['category', 'supplier'])),
        ]);
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->update(['is_active' => false]);
        return response()->json(['message' => 'Product deactivated successfully']);
    }

    public function search(Request $request): AnonymousResourceCollection
    {
        $request->validate(['q' => 'required|string|min:1']);

        $query = $request->q;
        $products = Product::with('category')
            ->where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('brand', 'like', "%{$query}%")
                  ->orWhere('sku', $query)
                  ->orWhere('barcode', $query);
            })
            ->limit(20)
            ->get();

        return ProductResource::collection($products);
    }

    public function lowStock(): AnonymousResourceCollection
    {
        $products = Product::with('category')
            ->where('is_active', true)
            ->whereColumn('stock_quantity', '<=', 'min_stock_alert')
            ->get();

        return ProductResource::collection($products);
    }

    public function options(): JsonResponse
    {
        $brands = Product::where('is_active', true)
            ->whereNotNull('brand')
            ->distinct()
            ->pluck('brand');

        $networkTypes = Product::where('is_active', true)
            ->whereNotNull('network_type')
            ->distinct()
            ->pluck('network_type');

        return response()->json([
            'brands' => $brands,
            'network_types' => $networkTypes,
        ]);
    }

    public function adjustStock(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|integer',
            'type' => 'required|in:purchase,adjustment,damage',
            'notes' => 'nullable|string',
        ]);

        $movement = $this->stockService->adjustStock(
            product: $product,
            quantity: $validated['quantity'],
            type: $validated['type'],
            userId: $request->user()->id,
            notes: $validated['notes'] ?? null,
        );

        return response()->json([
            'message' => 'Stock adjusted successfully',
            'product' => new ProductResource($product->fresh()),
            'movement' => $movement,
        ]);
    }
}
