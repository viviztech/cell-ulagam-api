<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\StockMovementResource;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StockMovementController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = StockMovement::with(['product', 'user']);

        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        return StockMovementResource::collection($query->latest()->paginate($request->get('per_page', 20)));
    }

    public function show(StockMovement $stockMovement): StockMovementResource
    {
        return new StockMovementResource($stockMovement->load(['product', 'user']));
    }
}
