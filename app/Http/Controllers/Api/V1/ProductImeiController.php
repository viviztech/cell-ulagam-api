<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ProductImeiResource;
use App\Models\Product;
use App\Models\ProductImei;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductImeiController extends Controller
{
    public function __construct(protected StockService $stockService) {}

    public function index(Product $product): AnonymousResourceCollection
    {
        return ProductImeiResource::collection($product->imeis()->latest()->get());
    }

    public function store(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'imeis' => 'required|array|min:1',
            'imeis.*.imei_1' => 'required|string|max:20',
            'imeis.*.imei_2' => 'nullable|string|max:20',
            'imeis.*.purchase_date' => 'nullable|date',
            'imeis.*.notes' => 'nullable|string',
        ]);

        $created = [];
        foreach ($validated['imeis'] as $imeiData) {
            $created[] = ProductImei::create([
                'shop_id' => $product->shop_id,
                'product_id' => $product->id,
                'imei_1' => $imeiData['imei_1'],
                'imei_2' => $imeiData['imei_2'] ?? null,
                'status' => 'in_stock',
                'purchase_date' => $imeiData['purchase_date'] ?? now()->toDateString(),
                'notes' => $imeiData['notes'] ?? null,
            ]);
        }

        $count = count($created);
        $this->stockService->adjustStock(
            product: $product,
            quantity: $count,
            type: 'purchase',
            userId: $request->user()->id,
            notes: "Added {$count} IMEI(s)",
        );

        return response()->json([
            'message' => "{$count} IMEI(s) added successfully",
            'imeis' => ProductImeiResource::collection(collect($created)),
        ], 201);
    }

    public function show(ProductImei $imei): ProductImeiResource
    {
        return new ProductImeiResource($imei->load('product'));
    }

    public function update(Request $request, ProductImei $imei): JsonResponse
    {
        $validated = $request->validate([
            'imei_1' => 'sometimes|string|max:20',
            'imei_2' => 'nullable|string|max:20',
            'status' => 'sometimes|in:in_stock,sold,returned,defective',
            'notes' => 'nullable|string',
        ]);

        $imei->update($validated);

        return response()->json([
            'message' => 'IMEI updated successfully',
            'imei' => new ProductImeiResource($imei),
        ]);
    }

    public function destroy(ProductImei $imei): JsonResponse
    {
        if ($imei->status === 'sold') {
            return response()->json(['message' => 'Cannot delete sold IMEI'], 422);
        }
        $imei->delete();
        return response()->json(['message' => 'IMEI deleted successfully']);
    }

    public function search(Request $request): AnonymousResourceCollection
    {
        $request->validate(['q' => 'required|string|min:1']);

        $imeis = ProductImei::with('product')
            ->where(function ($q) use ($request) {
                $q->where('imei_1', 'like', "%{$request->q}%")
                  ->orWhere('imei_2', 'like', "%{$request->q}%");
            })
            ->limit(20)
            ->get();

        return ProductImeiResource::collection($imeis);
    }

    public function available(Request $request): AnonymousResourceCollection
    {
        $query = ProductImei::where('status', 'in_stock');

        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        return ProductImeiResource::collection($query->get());
    }
}
