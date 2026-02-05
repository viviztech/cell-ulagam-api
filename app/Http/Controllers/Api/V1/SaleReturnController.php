<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\SaleReturnResource;
use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\Product;
use App\Models\ProductImei;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class SaleReturnController extends Controller
{
    public function __construct(protected StockService $stockService) {}

    public function index(): AnonymousResourceCollection
    {
        return SaleReturnResource::collection(
            SaleReturn::with(['sale', 'user', 'items'])->latest()->paginate(20)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'return_date' => 'nullable|date',
            'refund_method' => 'required|in:cash,upi,card,credit_note',
            'reason' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.sale_item_id' => 'required|exists:sale_items,id',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.product_imei_id' => 'nullable|exists:product_imeis,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.refund_amount' => 'required|numeric|min:0',
            'items.*.restock' => 'boolean',
        ]);

        $shopId = $request->user()->shop_id ?? app('current_shop_id');
        $sale = Sale::findOrFail($validated['sale_id']);

        $saleReturn = DB::transaction(function () use ($validated, $request, $shopId, $sale) {
            $totalRefund = collect($validated['items'])->sum('refund_amount');

            $lastReturn = SaleReturn::withoutGlobalScopes()
                ->where('shop_id', $shopId)
                ->orderByDesc('id')
                ->first();
            $nextNum = $lastReturn ? ((int) substr($lastReturn->return_number, -6)) + 1 : 1;
            $returnNumber = 'RET-' . str_pad($nextNum, 6, '0', STR_PAD_LEFT);

            $saleReturn = SaleReturn::create([
                'shop_id' => $shopId,
                'sale_id' => $sale->id,
                'user_id' => $request->user()->id,
                'return_number' => $returnNumber,
                'return_date' => $validated['return_date'] ?? now()->toDateString(),
                'total_refund' => $totalRefund,
                'refund_method' => $validated['refund_method'],
                'reason' => $validated['reason'] ?? null,
            ]);

            foreach ($validated['items'] as $itemData) {
                SaleReturnItem::create([
                    'shop_id' => $shopId,
                    'sale_return_id' => $saleReturn->id,
                    'sale_item_id' => $itemData['sale_item_id'],
                    'product_id' => $itemData['product_id'],
                    'product_imei_id' => $itemData['product_imei_id'] ?? null,
                    'quantity' => $itemData['quantity'],
                    'refund_amount' => $itemData['refund_amount'],
                    'restock' => $itemData['restock'] ?? true,
                ]);

                if ($itemData['restock'] ?? true) {
                    $product = Product::findOrFail($itemData['product_id']);
                    $this->stockService->adjustStock(
                        product: $product,
                        quantity: $itemData['quantity'],
                        type: 'sale_return',
                        userId: $request->user()->id,
                        referenceType: 'sale_return_items',
                        referenceId: $saleReturn->id,
                    );

                    if (!empty($itemData['product_imei_id'])) {
                        ProductImei::where('id', $itemData['product_imei_id'])->update([
                            'status' => 'returned',
                            'sold_date' => null,
                            'sale_item_id' => null,
                        ]);
                    }
                }
            }

            if ($validated['refund_method'] === 'cash') {
                $currentSession = CashSession::where('user_id', $request->user()->id)
                    ->where('status', 'open')
                    ->first();

                if ($currentSession) {
                    CashMovement::create([
                        'shop_id' => $shopId,
                        'cash_session_id' => $currentSession->id,
                        'user_id' => $request->user()->id,
                        'type' => 'cash_out',
                        'amount' => $totalRefund,
                        'reason' => "Return #{$returnNumber}",
                        'reference_type' => 'sale_returns',
                        'reference_id' => $saleReturn->id,
                    ]);
                    $currentSession->increment('cash_out_total', $totalRefund);
                }
            }

            $sale->update(['status' => 'returned']);

            return $saleReturn;
        });

        return response()->json([
            'message' => 'Return processed successfully',
            'sale_return' => new SaleReturnResource($saleReturn->load(['sale', 'items'])),
        ], 201);
    }

    public function show(SaleReturn $saleReturn): SaleReturnResource
    {
        return new SaleReturnResource($saleReturn->load(['sale', 'user', 'items']));
    }
}
