<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\SaleResource;
use App\Models\CashSession;
use App\Models\Product;
use App\Models\Sale;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(protected ReportService $reportService) {}

    public function index(Request $request): JsonResponse
    {
        $today = now()->toDateString();
        $user = $request->user();

        $salesQuery = Sale::whereDate('sale_date', $today)->where('status', 'completed');
        if ($user->isSalesperson()) {
            $salesQuery->where('user_id', $user->id);
        }

        $todaySales = (float) $salesQuery->sum('total_amount');
        $todaySalesCount = $salesQuery->count();

        $lowStockCount = Product::where('is_active', true)
            ->whereColumn('stock_quantity', '<=', 'min_stock_alert')
            ->count();

        $currentSession = CashSession::where('user_id', $user->id)
            ->where('status', 'open')
            ->first();

        $recentSales = Sale::with(['user', 'customer'])
            ->whereDate('sale_date', $today)
            ->latest()
            ->limit(5)
            ->get();

        return response()->json([
            'today_sales' => $todaySales,
            'today_sales_count' => $todaySalesCount,
            'low_stock_count' => $lowStockCount,
            'has_open_session' => (bool) $currentSession,
            'current_session_balance' => $currentSession
                ? (float) $currentSession->opening_balance + (float) $currentSession->cash_in_total - (float) $currentSession->cash_out_total
                : null,
            'recent_sales' => SaleResource::collection($recentSales),
        ]);
    }

    public function superAdmin(): JsonResponse
    {
        return response()->json(['shops' => $this->reportService->crossShopOverview()]);
    }
}
