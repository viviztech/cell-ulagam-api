<?php

namespace App\Services;

use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductImei;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\Shop;
use Illuminate\Support\Facades\DB;

class SaleService
{
    public function __construct(
        protected StockService $stockService,
        protected InvoiceNumberService $invoiceNumberService,
    ) {}

    public function createSale(array $data, int $userId, int $shopId): Sale
    {
        return DB::transaction(function () use ($data, $userId, $shopId) {
            $shop = Shop::findOrFail($shopId);
            $invoiceNumber = $this->invoiceNumberService->generate($shopId, $shop->invoice_prefix);

            $subtotal = 0;
            foreach ($data['items'] as $item) {
                $itemTotal = ($item['unit_price'] * $item['quantity']) - ($item['discount'] ?? 0);
                $subtotal += $itemTotal;
            }

            $discountAmount = $data['discount_amount'] ?? 0;
            $taxAmount = $data['tax_amount'] ?? 0;
            $totalAmount = $subtotal - $discountAmount + $taxAmount;

            $sale = Sale::create([
                'shop_id' => $shopId,
                'user_id' => $userId,
                'cash_session_id' => $data['cash_session_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
                'invoice_number' => $invoiceNumber,
                'sale_date' => $data['sale_date'] ?? now()->toDateString(),
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'discount_type' => $data['discount_type'] ?? 'flat',
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'status' => 'completed',
                'gift' => $data['gift'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $itemData) {
                $product = Product::findOrFail($itemData['product_id']);
                $itemTotal = ($itemData['unit_price'] * $itemData['quantity']) - ($itemData['discount'] ?? 0);

                $saleItem = SaleItem::create([
                    'shop_id' => $shopId,
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'product_imei_id' => $itemData['product_imei_id'] ?? null,
                    'product_name' => $product->name,
                    'product_brand' => $product->brand,
                    'product_variant' => $product->variant,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'purchase_price' => $product->purchase_price,
                    'discount' => $itemData['discount'] ?? 0,
                    'total_price' => $itemTotal,
                ]);

                $this->stockService->adjustStock(
                    product: $product,
                    quantity: -$itemData['quantity'],
                    type: 'sale',
                    userId: $userId,
                    referenceType: 'sale_items',
                    referenceId: $saleItem->id,
                );

                if (!empty($itemData['product_imei_id'])) {
                    ProductImei::where('id', $itemData['product_imei_id'])->update([
                        'status' => 'sold',
                        'sold_date' => now()->toDateString(),
                        'sale_item_id' => $saleItem->id,
                    ]);
                }
            }

            foreach ($data['payments'] as $paymentData) {
                SalePayment::create([
                    'shop_id' => $shopId,
                    'sale_id' => $sale->id,
                    'method' => $paymentData['method'],
                    'amount' => $paymentData['amount'],
                    'reference_number' => $paymentData['reference_number'] ?? null,
                ]);

                if ($paymentData['method'] === 'cash' && !empty($data['cash_session_id'])) {
                    $cashSession = CashSession::find($data['cash_session_id']);
                    if ($cashSession && $cashSession->isOpen()) {
                        CashMovement::create([
                            'shop_id' => $shopId,
                            'cash_session_id' => $cashSession->id,
                            'user_id' => $userId,
                            'type' => 'cash_in',
                            'amount' => $paymentData['amount'],
                            'reason' => "Sale #{$invoiceNumber}",
                            'reference_type' => 'sales',
                            'reference_id' => $sale->id,
                        ]);

                        $cashSession->increment('cash_in_total', $paymentData['amount']);
                    }
                }
            }

            if ($sale->customer_id) {
                Customer::where('id', $sale->customer_id)->increment('total_purchases', $totalAmount);
            }

            return $sale->load(['items', 'payments', 'customer', 'user']);
        });
    }
}
