<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\ExpenseCategory;
use App\Models\Shop;
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

    }
}
