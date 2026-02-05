<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(protected ReportService $reportService) {}

    public function dailySummary(Request $request): JsonResponse
    {
        $date = $request->get('date', now()->toDateString());
        $shopId = $request->user()->shop_id ?? app('current_shop_id');

        return response()->json($this->reportService->dailySummary($shopId, $date));
    }

    public function salesSummary(Request $request): JsonResponse
    {
        $request->validate([
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
        ]);

        $shopId = $request->user()->shop_id ?? app('current_shop_id');

        return response()->json($this->reportService->salesSummary($shopId, $request->from, $request->to));
    }

    public function stockReport(Request $request): JsonResponse
    {
        $shopId = $request->user()->shop_id ?? app('current_shop_id');

        return response()->json($this->reportService->stockReport($shopId));
    }

    public function crossShopOverview(): JsonResponse
    {
        return response()->json(['shops' => $this->reportService->crossShopOverview()]);
    }
}
