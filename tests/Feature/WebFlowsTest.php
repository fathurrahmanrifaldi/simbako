<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class WebFlowsTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $staff;
    protected Category $category;
    protected Product $product;
    protected StockService $stockService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stockService = new StockService();

        $this->owner = User::create([
            'name' => 'Raswan',
            'role' => 'owner',
            'email' => 'raswan@simbako.test',
            'password_hash' => Hash::make('password123'),
            'is_active' => true,
        ]);

        $this->staff = User::create([
            'name' => 'Budi',
            'role' => 'staff',
            'pin_hash' => Hash::make('123456'),
            'is_active' => true,
        ]);

        $this->category = Category::create([
            'name' => 'Sembako',
            'lead_time_days' => 1,
            'safety_stock' => 5,
            'expiry_warning_days' => 14,
        ]);

        $this->product = Product::create([
            'name' => 'Beras Ramos 5kg',
            'category_id' => $this->category->id,
            'base_unit' => 'karung',
            'sell_price' => 70000,
            'last_buy_price' => 60000,
            'has_expiry' => false,
            'is_favorite' => true,
            'is_active' => true,
        ]);

        $this->stockService->recordOpeningBalance($this->product->id, 10.0, null, 60000, $this->owner->id);
    }

    public function test_login_and_sales_flow(): void
    {
        // 1. Staff logs in with PIN
        $loginRes = $this->post(route('login.submit'), [
            'login_type' => 'staff',
            'staff_id' => $this->staff->id,
            'pin' => '123456',
        ]);
        $loginRes->assertRedirect(route('sales.index'));
        $this->assertAuthenticatedAs($this->staff);

        // 2. View sales screen
        $salesScreen = $this->get(route('sales.index'));
        $salesScreen->assertStatus(200);
        $salesScreen->assertSee('Beras Ramos 5kg');

        // 3. Complete a sale via API
        $saleId = (string) Str::uuid();
        $saleRes = $this->postJson('/api/sales', [
            'id' => $saleId,
            'items' => [
                ['product_id' => $this->product->id, 'qty' => 2.0, 'unit_price' => 70000],
            ],
        ]);
        $saleRes->assertStatus(200);

        // Verify stock reduced by 2
        $this->assertEquals(8.0, $this->stockService->getProductStockTotal($this->product->id));

        // 4. Check sales history
        $historyRes = $this->get(route('sales.history'));
        $historyRes->assertStatus(200);
        $historyRes->assertSee('140.000');
    }

    public function test_dashboard_and_reorder_alerts(): void
    {
        $this->actingAs($this->owner);

        // Reduce stock to below threshold (0)
        $this->stockService->recordSale(
            (string) Str::uuid(),
            [['product_id' => $this->product->id, 'qty' => 10.0, 'unit_price' => 70000]],
            $this->owner->id
        );

        $dashRes = $this->get(route('dashboard.index'));
        $dashRes->assertStatus(200);
        $dashRes->assertSee('Habis');
        $dashRes->assertSee('Beras Ramos 5kg');
    }

    public function test_csv_export_streams(): void
    {
        $this->actingAs($this->owner);

        $response = $this->get(route('export.download', 'products'));
        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }
}
