<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\SaleResource;
use App\Models\CashSession;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SaleController extends Controller
{
    public function __construct(protected SaleService $saleService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Sale::with(['user', 'customer', 'items', 'payments']);

        if ($request->user()->isSalesperson()) {
            $query->where('user_id', $request->user()->id);
        }

        if ($request->has('from') && $request->has('to')) {
            $query->whereBetween('sale_date', [$request->from, $request->to]);
        }
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return SaleResource::collection($query->latest()->paginate($request->get('per_page', 20)));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'cash_session_id' => 'nullable|exists:cash_sessions,id',
            'sale_date' => 'nullable|date',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|in:flat,percentage',
            'tax_amount' => 'nullable|numeric|min:0',
            'gift' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.product_imei_id' => 'nullable|exists:product_imeis,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'payments' => 'required|array|min:1',
            'payments.*.method' => 'required|in:cash,upi,card,bank_transfer,credit',
            'payments.*.amount' => 'required|numeric|min:0',
            'payments.*.reference_number' => 'nullable|string|max:100',
        ]);

        $shopId = $request->user()->shop_id ?? app('current_shop_id');

        // Auto-detect current open cash session if not provided
        if (empty($validated['cash_session_id'])) {
            $currentSession = CashSession::where('user_id', $request->user()->id)
                ->where('status', 'open')
                ->first();
            if ($currentSession) {
                $validated['cash_session_id'] = $currentSession->id;
            }
        }

        $sale = $this->saleService->createSale($validated, $request->user()->id, $shopId);

        return response()->json([
            'message' => 'Sale created successfully',
            'sale' => new SaleResource($sale),
        ], 201);
    }

    public function show(Request $request, Sale $sale): SaleResource
    {
        if ($request->user()->isSalesperson() && $sale->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        return new SaleResource($sale->load(['user', 'customer', 'items', 'payments']));
    }

    public function today(Request $request): JsonResponse
    {
        $query = Sale::with(['user', 'items', 'payments'])
            ->whereDate('sale_date', now()->toDateString())
            ->where('status', 'completed');

        if ($request->user()->isSalesperson()) {
            $query->where('user_id', $request->user()->id);
        }

        $sales = $query->latest()->get();

        return response()->json([
            'sales' => SaleResource::collection($sales),
            'total' => (float) $sales->sum('total_amount'),
            'count' => $sales->count(),
        ]);
    }

    public function byUser(Request $request): JsonResponse
    {
        $date = $request->get('date', now()->toDateString());

        $salesByUser = Sale::whereDate('sale_date', $date)
            ->where('status', 'completed')
            ->selectRaw('user_id, COUNT(*) as sales_count, SUM(total_amount) as total_amount')
            ->groupBy('user_id')
            ->with('user')
            ->get();

        return response()->json(['data' => $salesByUser]);
    }
}
