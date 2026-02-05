<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\ExpenseCategory;
use App\Models\Product;
use App\Models\Shop;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create Super Admin (no shop)
        User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@cellulagam.com',
            'phone' => '9999999999',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        // Create Shop 1
        $shop1 = Shop::create([
            'name' => 'Cell Ulagam Main',
            'code' => 'CUM',
            'address' => '123 Main Street',
            'city' => 'Chennai',
            'state' => 'Tamil Nadu',
            'pincode' => '600001',
            'phone' => '9876543210',
            'email' => 'main@cellulagam.com',
            'invoice_prefix' => 'CUM',
            'tax_rate' => 18.00,
            'currency' => 'INR',
            'is_active' => true,
        ]);

        // Create Shop 2
        $shop2 = Shop::create([
            'name' => 'Cell Ulagam Branch',
            'code' => 'CUB',
            'address' => '456 Branch Road',
            'city' => 'Coimbatore',
            'state' => 'Tamil Nadu',
            'pincode' => '641001',
            'phone' => '9876543211',
            'email' => 'branch@cellulagam.com',
            'invoice_prefix' => 'CUB',
            'tax_rate' => 18.00,
            'currency' => 'INR',
            'is_active' => true,
        ]);

        // Create Shop 1 Admin
        User::create([
            'shop_id' => $shop1->id,
            'name' => 'Shop 1 Admin',
            'email' => 'admin1@cellulagam.com',
            'phone' => '9876543001',
            'password' => Hash::make('password'),
            'role' => 'shop_admin',
            'is_active' => true,
        ]);

        // Create Shop 2 Admin
        User::create([
            'shop_id' => $shop2->id,
            'name' => 'Shop 2 Admin',
            'email' => 'admin2@cellulagam.com',
            'phone' => '9876543002',
            'password' => Hash::make('password'),
            'role' => 'shop_admin',
            'is_active' => true,
        ]);

        // Create Manager for Shop 1
        User::create([
            'shop_id' => $shop1->id,
            'name' => 'Manager One',
            'email' => 'manager1@cellulagam.com',
            'phone' => '9876543003',
            'password' => Hash::make('password'),
            'role' => 'manager',
            'is_active' => true,
        ]);

        // Create Salesperson for Shop 1
        User::create([
            'shop_id' => $shop1->id,
            'name' => 'Sales Person',
            'email' => 'sales1@cellulagam.com',
            'phone' => '9876543004',
            'password' => Hash::make('password'),
            'role' => 'salesperson',
            'is_active' => true,
        ]);

        // Create Categories for Shop 1
        $categories1 = [
            ['shop_id' => $shop1->id, 'name' => 'Smartphones', 'slug' => 'smartphones', 'is_active' => true, 'sort_order' => 1],
            ['shop_id' => $shop1->id, 'name' => 'Feature Phones', 'slug' => 'feature-phones', 'is_active' => true, 'sort_order' => 2],
            ['shop_id' => $shop1->id, 'name' => 'Accessories', 'slug' => 'accessories', 'is_active' => true, 'sort_order' => 3],
            ['shop_id' => $shop1->id, 'name' => 'Chargers', 'slug' => 'chargers', 'is_active' => true, 'sort_order' => 4],
            ['shop_id' => $shop1->id, 'name' => 'Earphones', 'slug' => 'earphones', 'is_active' => true, 'sort_order' => 5],
        ];
        foreach ($categories1 as $cat) {
            Category::create($cat);
        }

        // Create Categories for Shop 2
        $categories2 = [
            ['shop_id' => $shop2->id, 'name' => 'Smartphones', 'slug' => 'smartphones', 'is_active' => true, 'sort_order' => 1],
            ['shop_id' => $shop2->id, 'name' => 'Tablets', 'slug' => 'tablets', 'is_active' => true, 'sort_order' => 2],
            ['shop_id' => $shop2->id, 'name' => 'Accessories', 'slug' => 'accessories', 'is_active' => true, 'sort_order' => 3],
        ];
        foreach ($categories2 as $cat) {
            Category::create($cat);
        }

        // Create Expense Categories for Shop 1
        $expenseCategories = [
            ['shop_id' => $shop1->id, 'name' => 'Rent', 'is_active' => true],
            ['shop_id' => $shop1->id, 'name' => 'Electricity', 'is_active' => true],
            ['shop_id' => $shop1->id, 'name' => 'Salary', 'is_active' => true],
            ['shop_id' => $shop1->id, 'name' => 'Miscellaneous', 'is_active' => true],
        ];
        foreach ($expenseCategories as $expCat) {
            ExpenseCategory::create($expCat);
        }

        // Create Suppliers for Shop 1
        $supplier1 = Supplier::create([
            'shop_id' => $shop1->id,
            'name' => 'Mobile Distributors',
            'company_name' => 'MD Pvt Ltd',
            'phone' => '9888877770',
            'email' => 'supplier@md.com',
            'address' => '100 Supplier Street, Chennai',
            'opening_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
        ]);

        // Create Products for Shop 1
        $smartphoneCategory = Category::where('shop_id', $shop1->id)->where('slug', 'smartphones')->first();
        $accessoryCategory = Category::where('shop_id', $shop1->id)->where('slug', 'accessories')->first();

        Product::create([
            'shop_id' => $shop1->id,
            'category_id' => $smartphoneCategory->id,
            'supplier_id' => $supplier1->id,
            'name' => 'Samsung Galaxy A54',
            'brand' => 'Samsung',
            'network_type' => '5G',
            'variant' => '8GB/256GB',
            'sku' => 'SAM-A54-8-256',
            'purchase_price' => 28000,
            'selling_price' => 32999,
            'stock_quantity' => 10,
            'min_stock_alert' => 2,
            'has_imei' => true,
            'is_active' => true,
        ]);

        Product::create([
            'shop_id' => $shop1->id,
            'category_id' => $smartphoneCategory->id,
            'supplier_id' => $supplier1->id,
            'name' => 'iPhone 15',
            'brand' => 'Apple',
            'network_type' => '5G',
            'variant' => '128GB',
            'sku' => 'APL-IP15-128',
            'purchase_price' => 72000,
            'selling_price' => 79900,
            'stock_quantity' => 5,
            'min_stock_alert' => 1,
            'has_imei' => true,
            'is_active' => true,
        ]);

        Product::create([
            'shop_id' => $shop1->id,
            'category_id' => $accessoryCategory->id,
            'name' => 'USB-C Charger 25W',
            'brand' => 'Samsung',
            'sku' => 'SAM-CHG-25W',
            'purchase_price' => 800,
            'selling_price' => 1299,
            'stock_quantity' => 50,
            'min_stock_alert' => 10,
            'has_imei' => false,
            'is_active' => true,
        ]);

        // Create Customers for Shop 1
        Customer::create([
            'shop_id' => $shop1->id,
            'name' => 'Walk-in Customer',
            'phone' => '0000000000',
            'total_purchases' => 0,
        ]);

        Customer::create([
            'shop_id' => $shop1->id,
            'name' => 'Rajesh Kumar',
            'phone' => '9876500001',
            'email' => 'rajesh@email.com',
            'address' => '10 Customer Lane, Chennai',
            'total_purchases' => 0,
        ]);
    }
}
