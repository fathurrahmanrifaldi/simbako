<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Default Users (AUTH-01)
        $owner = User::firstOrCreate(
            ['email' => 'user@simbako.test'],
            [
                'name' => 'user@simbako.test (Pemilik)',
                'role' => 'owner',
                'password_hash' => Hash::make('password123'),
                'is_active' => true,
            ]
        );

        $staff = User::firstOrCreate(
            ['name' => 'Budi (Pegawai)'],
            [
                'role' => 'staff',
                'pin_hash' => Hash::make('123456'),
                'is_active' => true,
            ]
        );

        // 2. Create Categories
        $categoriesData = [
            [
                'name' => 'Sembako Pokok',
                'lead_time_days' => 1,
                'safety_stock' => 5,
                'expiry_warning_days' => 14,
                'overstock_days' => 45,
            ],
            [
                'name' => 'Makanan Instan',
                'lead_time_days' => 2,
                'safety_stock' => 10,
                'expiry_warning_days' => 21,
                'overstock_days' => 60,
            ],
            [
                'name' => 'Minuman & Kopi',
                'lead_time_days' => 2,
                'safety_stock' => 8,
                'expiry_warning_days' => 30,
                'overstock_days' => 60,
            ],
            [
                'name' => 'Bumbu Dapur',
                'lead_time_days' => 2,
                'safety_stock' => 10,
                'expiry_warning_days' => 30,
                'overstock_days' => 60,
            ],
            [
                'name' => 'Perawatan & Kebersihan',
                'lead_time_days' => 3,
                'safety_stock' => 6,
                'expiry_warning_days' => 45,
                'overstock_days' => 90,
            ],
            [
                'name' => 'Snack & Lain-lain',
                'lead_time_days' => 2,
                'safety_stock' => 5,
                'expiry_warning_days' => 30,
                'overstock_days' => 60,
            ],
        ];

        $categories = [];
        foreach ($categoriesData as $c) {
            $cat = Category::firstOrCreate(['name' => $c['name']], $c);
            $categories[$c['name']] = $cat;
        }

        // 3. 30 Realistic Indonesian Warung Sembako Products
        $productsData = [
            // Sembako Pokok
            ['name' => 'Beras Ramos Super 5kg', 'cat' => 'Sembako Pokok', 'unit' => 'karung', 'sell' => 72000, 'buy' => 65000, 'min' => 4, 'has_exp' => false, 'fav' => true, 'stock' => 10],
            ['name' => 'Beras Pandan Wangi 5kg', 'cat' => 'Sembako Pokok', 'unit' => 'karung', 'sell' => 78000, 'buy' => 70000, 'min' => 3, 'has_exp' => false, 'fav' => true, 'stock' => 8],
            ['name' => 'Minyak Goreng Bimoli 1L', 'cat' => 'Sembako Pokok', 'unit' => 'pouch', 'sell' => 21000, 'buy' => 18500, 'min' => 12, 'has_exp' => true, 'fav' => true, 'stock' => 24, 'exp' => '2027-02-15'],
            ['name' => 'Minyak Goreng SunCo 2L', 'cat' => 'Sembako Pokok', 'unit' => 'pouch', 'sell' => 39000, 'buy' => 35000, 'min' => 6, 'has_exp' => true, 'fav' => true, 'stock' => 12, 'exp' => '2027-03-20'],
            ['name' => 'Minyak Goreng Curah 1kg', 'cat' => 'Sembako Pokok', 'unit' => 'kg', 'sell' => 16500, 'buy' => 14500, 'min' => 15, 'has_exp' => false, 'fav' => true, 'stock' => 20],
            ['name' => 'Gula Pasir Gulaku 1kg', 'cat' => 'Sembako Pokok', 'unit' => 'bungkus', 'sell' => 18500, 'buy' => 16200, 'min' => 10, 'has_exp' => true, 'fav' => true, 'stock' => 18, 'exp' => '2027-08-10'],
            ['name' => 'Gula Pasir Curah 1kg', 'cat' => 'Sembako Pokok', 'unit' => 'kg', 'sell' => 17000, 'buy' => 15000, 'min' => 15, 'has_exp' => false, 'fav' => true, 'stock' => 25],
            ['name' => 'Telur Ayam Negeri 1kg', 'cat' => 'Sembako Pokok', 'unit' => 'kg', 'sell' => 29000, 'buy' => 26000, 'min' => 10, 'has_exp' => true, 'fav' => true, 'stock' => 15, 'exp' => '2026-11-05'],
            ['name' => 'Tepung Terigu Segitiga Biru 1kg', 'cat' => 'Sembako Pokok', 'unit' => 'bungkus', 'sell' => 13500, 'buy' => 11800, 'min' => 8, 'has_exp' => true, 'fav' => true, 'stock' => 16, 'exp' => '2027-04-12'],
            ['name' => 'Garam Dapur Beryodium 250g', 'cat' => 'Sembako Pokok', 'unit' => 'bungkus', 'sell' => 3000, 'buy' => 2200, 'min' => 12, 'has_exp' => false, 'fav' => true, 'stock' => 30],

            // Makanan Instan
            ['name' => 'Indomie Goreng Original', 'cat' => 'Makanan Instan', 'unit' => 'bungkus', 'sell' => 3500, 'buy' => 2900, 'min' => 40, 'has_exp' => true, 'fav' => true, 'stock' => 80, 'exp' => '2027-01-20'],
            ['name' => 'Indomie Ayam Bawang', 'cat' => 'Makanan Instan', 'unit' => 'bungkus', 'sell' => 3500, 'buy' => 2900, 'min' => 30, 'has_exp' => true, 'fav' => true, 'stock' => 60, 'exp' => '2027-01-25'],
            ['name' => 'Indomie Soto Mie', 'cat' => 'Makanan Instan', 'unit' => 'bungkus', 'sell' => 3500, 'buy' => 2900, 'min' => 30, 'has_exp' => true, 'fav' => true, 'stock' => 50, 'exp' => '2027-02-10'],
            ['name' => 'Sarimi Isi 2 Ayam Kecap', 'cat' => 'Makanan Instan', 'unit' => 'bungkus', 'sell' => 4500, 'buy' => 3800, 'min' => 20, 'has_exp' => true, 'fav' => true, 'stock' => 40, 'exp' => '2027-01-15'],
            ['name' => 'Kerupuk Ikan Mentah 250g', 'cat' => 'Makanan Instan', 'unit' => 'bungkus', 'sell' => 8000, 'buy' => 6000, 'min' => 5, 'has_exp' => true, 'fav' => false, 'stock' => 12, 'exp' => '2027-05-10'],

            // Minuman & Kopi
            ['name' => 'Kopi Kapal Api Spesial Mix', 'cat' => 'Minuman & Kopi', 'unit' => 'renteng', 'sell' => 15000, 'buy' => 13200, 'min' => 10, 'has_exp' => true, 'fav' => true, 'stock' => 20, 'exp' => '2027-06-01'],
            ['name' => 'Teh Celup Sariwangi 25s', 'cat' => 'Minuman & Kopi', 'unit' => 'kotak', 'sell' => 7500, 'buy' => 6200, 'min' => 8, 'has_exp' => true, 'fav' => true, 'stock' => 15, 'exp' => '2027-07-15'],
            ['name' => 'Susu Kental Manis Frisian Flag Cokelat', 'cat' => 'Minuman & Kopi', 'unit' => 'kaleng', 'sell' => 13000, 'buy' => 11200, 'min' => 6, 'has_exp' => true, 'fav' => true, 'stock' => 14, 'exp' => '2027-04-20'],
            ['name' => 'Susu Kental Manis Bendera Putih', 'cat' => 'Minuman & Kopi', 'unit' => 'sachet', 'sell' => 2000, 'buy' => 1500, 'min' => 24, 'has_exp' => true, 'fav' => true, 'stock' => 48, 'exp' => '2027-03-10'],
            ['name' => 'Aqua Botol 600ml', 'cat' => 'Minuman & Kopi', 'unit' => 'botol', 'sell' => 4000, 'buy' => 3000, 'min' => 24, 'has_exp' => true, 'fav' => true, 'stock' => 36, 'exp' => '2027-08-01'],

            // Bumbu Dapur
            ['name' => 'Kecap Manis Bango 520ml', 'cat' => 'Bumbu Dapur', 'unit' => 'pouch', 'sell' => 24000, 'buy' => 21000, 'min' => 5, 'has_exp' => true, 'fav' => true, 'stock' => 10, 'exp' => '2027-05-18'],
            ['name' => 'Saus Sambal ABC 275ml', 'cat' => 'Bumbu Dapur', 'unit' => 'botol', 'sell' => 12500, 'buy' => 10500, 'min' => 6, 'has_exp' => true, 'fav' => true, 'stock' => 12, 'exp' => '2027-03-30'],
            ['name' => 'Masako Ayam 100g', 'cat' => 'Bumbu Dapur', 'unit' => 'renteng', 'sell' => 5000, 'buy' => 4200, 'min' => 12, 'has_exp' => true, 'fav' => true, 'stock' => 25, 'exp' => '2027-06-25'],
            ['name' => 'Royco Rasa Sapi 100g', 'cat' => 'Bumbu Dapur', 'unit' => 'renteng', 'sell' => 5000, 'buy' => 4200, 'min' => 12, 'has_exp' => true, 'fav' => true, 'stock' => 25, 'exp' => '2027-07-01'],
            ['name' => 'Santan Kelapa Kara 65ml', 'cat' => 'Bumbu Dapur', 'unit' => 'kotak', 'sell' => 3500, 'buy' => 2800, 'min' => 15, 'has_exp' => true, 'fav' => true, 'stock' => 30, 'exp' => '2027-02-14'],
            ['name' => 'Margarin Blue Band Serbaguna 200g', 'cat' => 'Bumbu Dapur', 'unit' => 'sachet', 'sell' => 9500, 'buy' => 8100, 'min' => 6, 'has_exp' => true, 'fav' => false, 'stock' => 12, 'exp' => '2027-04-05'],

            // Perawatan & Kebersihan
            ['name' => 'Sabun Mandi Lifebuoy Total 10 110g', 'cat' => 'Perawatan & Kebersihan', 'unit' => 'batang', 'sell' => 4500, 'buy' => 3600, 'min' => 12, 'has_exp' => false, 'fav' => false, 'stock' => 24],
            ['name' => 'Sabun Cuci Piring Sunlight Jeruk Nipis 650ml', 'cat' => 'Perawatan & Kebersihan', 'unit' => 'pouch', 'sell' => 14000, 'buy' => 12000, 'min' => 8, 'has_exp' => false, 'fav' => false, 'stock' => 15],
            ['name' => 'Deterjen Rinso Molto Bubuk 770g', 'cat' => 'Perawatan & Kebersihan', 'unit' => 'bungkus', 'sell' => 22000, 'buy' => 19200, 'min' => 6, 'has_exp' => false, 'fav' => false, 'stock' => 12],
            ['name' => 'Pasta Gigi Pepsodent White 120g', 'cat' => 'Perawatan & Kebersihan', 'unit' => 'tube', 'sell' => 11000, 'buy' => 9300, 'min' => 6, 'has_exp' => false, 'fav' => false, 'stock' => 10],

            // Snack & Lain-lain
            ['name' => 'Biskuit Roma Kelapa 300g', 'cat' => 'Snack & Lain-lain', 'unit' => 'bungkus', 'sell' => 11500, 'buy' => 9800, 'min' => 6, 'has_exp' => true, 'fav' => false, 'stock' => 14, 'exp' => '2027-02-28'],
            ['name' => 'Korek Api Gas Tokai', 'cat' => 'Snack & Lain-lain', 'unit' => 'pcs', 'sell' => 3000, 'buy' => 2200, 'min' => 10, 'has_exp' => false, 'fav' => false, 'stock' => 20],
        ];

        $stockService = new StockService();

        foreach ($productsData as $pData) {
            $cat = $categories[$pData['cat']];
            $product = Product::firstOrCreate(
                ['name' => $pData['name']],
                [
                    'category_id' => $cat->id,
                    'base_unit' => $pData['unit'],
                    'sell_price' => $pData['sell'],
                    'last_buy_price' => $pData['buy'],
                    'min_stock' => $pData['min'],
                    'has_expiry' => $pData['has_exp'],
                    'is_favorite' => $pData['fav'],
                    'is_active' => true,
                ]
            );

            // Record initial opening balance if not recorded yet
            if ($stockService->getProductStockTotal($product->id) == 0 && isset($pData['stock'])) {
                $stockService->recordOpeningBalance(
                    $product->id,
                    (float) $pData['stock'],
                    $pData['exp'] ?? null,
                    $pData['buy'],
                    $owner->id
                );
            }
        }
    }
}
