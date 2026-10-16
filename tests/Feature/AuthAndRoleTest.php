<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthAndRoleTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $staff;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::create([
            'name' => 'Pak Raswan',
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
        ]);
    }

    /**
     * TC-17: Kunci akun.
     * Lima PIN salah berturut-turut. Percobaan ke-6 ditolak selama 5 menit walau PIN benar.
     */
    public function test_tc17_account_locks_after_5_failed_attempts(): void
    {
        // 5 consecutive wrong PIN attempts
        for ($i = 1; $i <= 5; $i++) {
            $response = $this->post(route('login.submit'), [
                'login_type' => 'staff',
                'staff_id' => $this->staff->id,
                'pin' => '000000', // wrong
            ]);
            $response->assertSessionHasErrors('login');
        }

        $this->staff->refresh();
        $this->assertEquals(5, $this->staff->failed_attempts);
        $this->assertTrue($this->staff->isLocked());

        // 6th attempt with CORRECT PIN must still be rejected because account is locked
        $response = $this->post(route('login.submit'), [
            'login_type' => 'staff',
            'staff_id' => $this->staff->id,
            'pin' => '123456', // correct
        ]);

        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    /**
     * TC-16: Peran.
     * Pegawai memanggil POST /api/purchases dan GET /api/products.
     * 403 untuk pembelian; respons produk tidak membocorkan last_buy_price.
     */
    public function test_tc16_staff_role_permissions_and_price_masking(): void
    {
        Product::create([
            'name' => 'Beras Pandan 5kg',
            'category_id' => $this->category->id,
            'base_unit' => 'karung',
            'sell_price' => 75000,
            'last_buy_price' => 65000,
            'has_expiry' => false,
        ]);

        // Acting as Staff:
        $this->actingAs($this->staff);

        // 1. Calling POST /api/purchases must return 403
        $purchaseResponse = $this->postJson('/api/purchases', [
            'purchased_on' => '2026-10-16',
            'items' => [],
        ]);
        $purchaseResponse->assertStatus(403);

        // 2. Calling GET /api/products returns products without last_buy_price
        $productResponse = $this->getJson('/api/products');
        $productResponse->assertStatus(200);

        $json = $productResponse->json();
        $this->assertNotEmpty($json['data']);
        $firstItem = $json['data'][0];

        // Must NOT expose last_buy_price or unit_cost
        $this->assertArrayNotHasKey('last_buy_price', $firstItem);
        $this->assertArrayNotHasKey('unit_cost', $firstItem);
        $this->assertEquals(75000, $firstItem['sell_price']);
    }
}
