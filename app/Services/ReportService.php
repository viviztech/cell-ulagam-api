<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function dailySummary(?int $shopId, string $date): array
    {
        $salesQuery = Sale::query()->where('sale_date', $date)->where('status', 'completed');
        $expensesQuery = Expense::query()->where('expense_date', $date);

        if ($shopId) {
            $salesQuery->where('shop_id', $shopId);
            $expensesQuery->where('shop_id', $shopId);
        }

        $totalSales = $salesQuery->sum('total_amount');
        $totalExpenses = $expensesQuery->sum('amount');
        $salesCount = $salesQuery->count();

        $costQuery = SaleItem::query()
            ->whereHas('sale', fn ($q) => $q->where('sale_date', $date)->where('status', 'completed'));
        
        if ($shopId) {
            $costQuery->where('shop_id', $shopId);
        }

        $costOfGoods = $costQuery->sum(DB::raw('purchase_price * quantity'));

        return [
            'date' => $date,
            'total_sales' => (float) $totalSales,
            'sales_count' => $salesCount,
            'total_expenses' => (float) $totalExpenses,
            'cost_of_goods' => (float) $costOfGoods,
            'gross_profit' => (float) ($totalSales - $costOfGoods),
            'net_profit' => (float) ($totalSales - $costOfGoods - $totalExpenses),
        ];
    }

    public function salesSummary(?int $shopId, string $from, string $to): array
    {
        $query = Sale::query()
            ->whereBetween('sale_date', [$from, $to])
            ->where('status', 'completed');

        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        return [
            'from' => $from,
            'to' => $to,
            'total_sales' => (float) $query->sum('total_amount'),
            'sales_count' => $query->count(),
            'total_discount' => (float) $query->sum('discount_amount'),
            'total_tax' => (float) $query->sum('tax_amount'),
            'average_sale' => (float) ($query->count() > 0 ? $query->avg('total_amount') : 0),
        ];
    }

    public function stockReport(?int $shopId): array
    {
        $query = Product::query()->where('is_active', true);

        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        $products = $query->get();

        return [
            'total_products' => $products->count(),
            'total_stock_value' => (float) $products->sum(fn ($p) => $p->stock_quantity * $p->purchase_price),
            'total_selling_value' => (float) $products->sum(fn ($p) => $p->stock_quantity * $p->selling_price),
            'low_stock_count' => $products->filter(fn ($p) => $p->isLowStock())->count(),
            'out_of_stock_count' => $products->where('stock_quantity', 0)->count(),
        ];
    }

    public function crossShopOverview(): array
    {
        $today = now()->toDateString();

        return DB::table('shops')
            ->where('is_active', true)
            ->get()
            ->map(function ($shop) use ($today) {
                $todaySales = Sale::withoutGlobalScopes()
                    ->where('shop_id', $shop->id)
                    ->where('sale_date', $today)
                    ->where('status', 'completed')
                    ->sum('total_amount');

                $stockValue = Product::withoutGlobalScopes()
                    ->where('shop_id', $shop->id)
                    ->where('is_active', true)
                    ->sum(DB::raw('stock_quantity * purchase_price'));

                return [
                    'shop_id' => $shop->id,
                    'shop_name' => $shop->name,
                    'shop_code' => $shop->code,
                    'today_sales' => (float) $todaySales,
                    'stock_value' => (float) $stockValue,
                ];
            })
            ->toArray();
    }
}
