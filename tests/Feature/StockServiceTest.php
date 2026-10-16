<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected Category $category;
    protected StockService $stockService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stockService = new StockService();

        $this->owner = User::create([
            'name' => 'Raswan',
            'role' => 'owner',
            'email' => 'raswan@simbako.test',
            'password_hash' => bcrypt('password123'),
        ]);

        $this->category = Category::create([
            'name' => 'Sembako',
            'lead_time_days' => 2,
            'safety_stock' => 4,
            'expiry_warning_days' => 14,
        ]);
    }

    /**
     * TC-20: Saldo awal.
     * Produk baru qty 12. Lalu coba saldo awal lagi pada produk yang sudah punya pergerakan (harus ditolak).
     */
    public function test_tc20_opening_balance_and_duplicate_rejection(): void
    {
        $product = Product::create([
            'name' => 'Beras Ramos 5kg',
            'category_id' => $this->category->id,
            'base_unit' => 'karung',
            'sell_price' => 70000,
            'has_expiry' => false,
        ]);

        $batch = $this->stockService->recordOpeningBalance(
            $product->id,
            12.0,
            null,
            60000,
            $this->owner->id
        );

        $this->assertEquals(12.0, $this->stockService->getProductStockTotal($product->id));
        $this->assertEquals('opening', $batch->source);

        // Second attempt on product with existing movements must throw exception
        $this->expectException(\Exception::class);
        $this->stockService->recordOpeningBalance(
            $product->id,
            5.0,
            null,
            60000,
            $this->owner->id
        );
    }

    /**
     * TC-03: Pembelian 20 bungkus harga 2.800 dengan exp 1 Des 2026.
     * Hasil: stok total naik 20; satu batch baru; last_buy_price menjadi 2.800.
     */
    public function test_tc03_purchase_creates_batch_and_updates_last_buy_price(): void
    {
        $product = Product::create([
            'name' => 'Indomie Goreng',
            'category_id' => $this->category->id,
            'base_unit' => 'bungkus',
            'sell_price' => 3500,
            'last_buy_price' => 0,
            'has_expiry' => true,
        ]);

        $purchase = $this->stockService->recordPurchase(
            [
                'purchased_on' => '2026-10-16',
                'supplier' => 'Toko Agen Barokah',
                'paid_from_cash' => false,
            ],
            [
                [
                    'product_id' => $product->id,
                    'qty' => 20.0,
                    'unit_price' => 2800,
                    'expiry_date' => '2026-12-01',
                ],
            ],
            $this->owner->id
        );

        $this->assertEquals(20.0, $this->stockService->getProductStockTotal($product->id));
        $this->assertEquals(2800, $product->fresh()->last_buy_price);
        $this->assertEquals(56000, $purchase->total);
    }

    /**
     * TC-04: Void pembelian.
     * (a) Setelah TC-03 tanpa penjualan, void -> berhasil, stok kembali 0, last_buy dihitung ulang.
     * (b) Jika sebagian sudah terjual, void ditolak.
     */
    public function test_tc04_void_purchase_allowed_when_unsold_and_rejected_when_sold(): void
    {
        $product = Product::create([
            'name' => 'Kopi Kapal Api',
            'category_id' => $this->category->id,
            'base_unit' => 'bungkus',
            'sell_price' => 2000,
            'last_buy_price' => 0,
            'has_expiry' => true,
        ]);

        $purchase = $this->stockService->recordPurchase(
            ['purchased_on' => '2026-10-16'],
            [
                [
                    'product_id' => $product->id,
                    'qty' => 10.0,
                    'unit_price' => 1500,
                    'expiry_date' => '2026-12-01',
                ],
            ],
            $this->owner->id
        );

        // (a) Void directly:
        $this->stockService->voidPurchase($purchase->id, 'Salah input jumlah', $this->owner->id);
        $this->assertEquals(0.0, $this->stockService->getProductStockTotal($product->id));
        $this->assertEquals('void', $purchase->fresh()->status);

        // (b) Purchase again, sell 1, then try voiding
        $purchase2 = $this->stockService->recordPurchase(
            ['purchased_on' => '2026-10-16'],
            [
                [
                    'product_id' => $product->id,
                    'qty' => 10.0,
                    'unit_price' => 1600,
                    'expiry_date' => '2026-12-01',
                ],
            ],
            $this->owner->id
        );

        // Sell 1
        $this->stockService->recordSale(
            (string) \Illuminate\Support\Str::uuid(),
            [
                ['product_id' => $product->id, 'qty' => 1.0, 'unit_price' => 2000],
            ],
            $this->owner->id
        );

        // Voiding purchase2 now must fail
        $this->expectException(\Exception::class);
        $this->stockService->voidPurchase($purchase2->id, 'Coba batalkan', $this->owner->id);
    }

    /**
     * TC-05: Idempotensi penjualan.
     * Kirim penjualan dengan ID yang sama dua kali.
     */
    public function test_tc05_sale_idempotency(): void
    {
        $product = Product::create([
            'name' => 'Minyak Goreng 1L',
            'category_id' => $this->category->id,
            'base_unit' => 'liter',
            'sell_price' => 15000,
            'has_expiry' => false,
        ]);

        $this->stockService->recordOpeningBalance($product->id, 10.0, null, 12000, $this->owner->id);

        $clientSaleId = (string) \Illuminate\Support\Str::uuid();

        $sale1 = $this->stockService->recordSale(
            $clientSaleId,
            [['product_id' => $product->id, 'qty' => 2.0, 'unit_price' => 15000]],
            $this->owner->id
        );

        // Second call with same sale ID
        $sale2 = $this->stockService->recordSale(
            $clientSaleId,
            [['product_id' => $product->id, 'qty' => 2.0, 'unit_price' => 15000]],
            $this->owner->id
        );

        $this->assertEquals($sale1->id, $sale2->id);
        // Stock reduced only once: 10 - 2 = 8
        $this->assertEquals(8.0, $this->stockService->getProductStockTotal($product->id));
    }

    /**
     * TC-06: Void penjualan.
     * Jual 3 lalu void. Batch kembali ke semula, status void.
     */
    public function test_tc06_void_sale_restores_stock(): void
    {
        $product = Product::create([
            'name' => 'Gula Pasir 1kg',
            'category_id' => $this->category->id,
            'base_unit' => 'kg',
            'sell_price' => 17000,
            'has_expiry' => false,
        ]);

        $this->stockService->recordOpeningBalance($product->id, 10.0, null, 14000, $this->owner->id);

        $saleId = (string) \Illuminate\Support\Str::uuid();
        $this->stockService->recordSale(
            $saleId,
            [['product_id' => $product->id, 'qty' => 3.0, 'unit_price' => 17000]],
            $this->owner->id
        );

        $this->assertEquals(7.0, $this->stockService->getProductStockTotal($product->id));

        $this->stockService->voidSale($saleId, 'Pembeli batal beli', $this->owner->id, true);

        // Restored back to 10
        $this->assertEquals(10.0, $this->stockService->getProductStockTotal($product->id));
    }
}
