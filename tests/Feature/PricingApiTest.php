<?php
namespace Tests\Feature;

use App\Models\Product;
use App\Models\PricingRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PricingApiTest extends TestCase
{
    use RefreshDatabase;

    private function createProduct(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'name'                       => 'Test Rice',
            'sku'                        => 'SX-TEST-' . uniqid(),
            'base_cost'                  => 120,
            'procurement_cost'           => 18,
            'delivery_cost'              => 8,
            'packaging_cost'             => 4,
            'demand_multiplier'          => 1.05,
            'season_multiplier'          => 1.15,
            'operational_margin_percent' => 18,
            'currency'                   => 'BDT',
        ], $overrides));
    }

    // ── POST /api/pricing/calculate ─────────────────────────────────────

    #[Test]
    public function calculate_returns_200_with_valid_input(): void
    {
        $product  = $this->createProduct();
        $response = $this->postJson('/api/pricing/calculate', [
            'product_id'    => $product->id,
            'quantity'      => 5,
            'customer_type' => 'standard',
            'season'        => 'normal',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'product_id', 'sku', 'quantity', 'customer_type', 'season',
                     'currency', 'unit_price', 'total_price', 'applied_rules',
                     'breakdown' => [
                         'base_cost', 'procurement_cost', 'delivery_cost', 'packaging_cost',
                         'demand_multiplier', 'season_multiplier', 'operational_margin_percent',
                         'unit_cost_before_margin',
                     ],
                 ]);
    }

    #[Test]
    public function calculate_validates_required_product_id(): void
    {
        $this->postJson('/api/pricing/calculate', ['quantity' => 5])
             ->assertStatus(422)
             ->assertJsonValidationErrors('product_id');
    }

    #[Test]
    public function calculate_validates_minimum_quantity(): void
    {
        $product = $this->createProduct();
        $this->postJson('/api/pricing/calculate', [
            'product_id' => $product->id,
            'quantity'   => 0,
        ])->assertStatus(422)->assertJsonValidationErrors('quantity');
    }

    #[Test]
    public function calculate_returns_correct_total_price(): void
    {
        $product = $this->createProduct([
            'base_cost'                  => 100,
            'procurement_cost'           => 0,
            'delivery_cost'              => 0,
            'packaging_cost'             => 0,
            'demand_multiplier'          => 1.0,
            'season_multiplier'          => 1.0,
            'operational_margin_percent' => 0,
        ]);

        $response = $this->postJson('/api/pricing/calculate', [
            'product_id' => $product->id,
            'quantity'   => 3,
        ]);

        $response->assertStatus(200);
        $this->assertEquals(100.0, $response->json('unit_price'));
        $this->assertEquals(300.0, $response->json('total_price'));
    }

    #[Test]
    public function calculate_applies_percent_discount_rule(): void
    {
        $product = $this->createProduct([
            'base_cost' => 100, 'procurement_cost' => 0, 'delivery_cost' => 0,
            'packaging_cost' => 0, 'demand_multiplier' => 1.0, 'season_multiplier' => 1.0,
            'operational_margin_percent' => 0,
        ]);

        PricingRule::create([
            'name'         => '10% off for 10+',
            'priority'     => 100,
            'active'       => true,
            'conditions'   => ['quantity_gte' => 10],
            'action_type'  => 'percent_discount',
            'action_value' => 10,
        ]);

        // 5 units – rule should NOT apply
        $resp5 = $this->postJson('/api/pricing/calculate', ['product_id' => $product->id, 'quantity' => 5]);
        $resp5->assertStatus(200);
        $this->assertEquals(100.0, $resp5->json('unit_price'));
        $this->assertEmpty($resp5->json('applied_rules'));

        // 10 units – rule SHOULD apply
        $resp10 = $this->postJson('/api/pricing/calculate', ['product_id' => $product->id, 'quantity' => 10]);
        $resp10->assertStatus(200);
        $this->assertEquals(90.0, $resp10->json('unit_price'));
        $this->assertNotEmpty($resp10->json('applied_rules'));
    }

    #[Test]
    public function calculate_applies_corporate_discount(): void
    {
        $product = $this->createProduct([
            'base_cost' => 200, 'procurement_cost' => 0, 'delivery_cost' => 0,
            'packaging_cost' => 0, 'demand_multiplier' => 1.0, 'season_multiplier' => 1.0,
            'operational_margin_percent' => 0,
        ]);

        PricingRule::create([
            'name'         => 'Corporate 7% off',
            'priority'     => 80,
            'active'       => true,
            'conditions'   => ['customer_type' => 'corporate'],
            'action_type'  => 'percent_discount',
            'action_value' => 7,
        ]);

        $standard  = $this->postJson('/api/pricing/calculate', ['product_id' => $product->id, 'quantity' => 1, 'customer_type' => 'standard']);
        $corporate = $this->postJson('/api/pricing/calculate', ['product_id' => $product->id, 'quantity' => 1, 'customer_type' => 'corporate']);

        $this->assertEquals(200.0, $standard->json('unit_price'));
        $this->assertEquals(186.0, $corporate->json('unit_price')); // 200 - 7%
    }

    #[Test]
    public function calculate_applies_peak_season_markup(): void
    {
        $product = $this->createProduct([
            'base_cost' => 100, 'procurement_cost' => 0, 'delivery_cost' => 0,
            'packaging_cost' => 0, 'demand_multiplier' => 1.0, 'season_multiplier' => 1.0,
            'operational_margin_percent' => 0,
        ]);

        PricingRule::create([
            'name'         => 'Peak markup 5%',
            'priority'     => 20,
            'active'       => true,
            'conditions'   => ['season' => 'peak'],
            'action_type'  => 'percent_markup',
            'action_value' => 5,
        ]);

        $normal = $this->postJson('/api/pricing/calculate', ['product_id' => $product->id, 'quantity' => 1, 'season' => 'normal']);
        $peak   = $this->postJson('/api/pricing/calculate', ['product_id' => $product->id, 'quantity' => 1, 'season' => 'peak']);

        $this->assertEquals(100.0, $normal->json('unit_price'));
        $this->assertEquals(105.0, $peak->json('unit_price'));
    }

    #[Test]
    public function inactive_rules_are_not_applied(): void
    {
        $product = $this->createProduct([
            'base_cost' => 100, 'procurement_cost' => 0, 'delivery_cost' => 0,
            'packaging_cost' => 0, 'demand_multiplier' => 1.0, 'season_multiplier' => 1.0,
            'operational_margin_percent' => 0,
        ]);

        PricingRule::create([
            'name'         => 'Inactive big discount',
            'priority'     => 100,
            'active'       => false,
            'conditions'   => ['quantity_gte' => 1],
            'action_type'  => 'percent_discount',
            'action_value' => 50,
        ]);

        $resp = $this->postJson('/api/pricing/calculate', ['product_id' => $product->id, 'quantity' => 10]);
        $resp->assertStatus(200);
        $this->assertEquals(100.0, $resp->json('unit_price'));
        $this->assertEmpty($resp->json('applied_rules'));
    }

    #[Test]
    public function calculate_accepts_demand_override(): void
    {
        $product = $this->createProduct([
            'base_cost' => 100, 'procurement_cost' => 0, 'delivery_cost' => 0,
            'packaging_cost' => 0, 'demand_multiplier' => 1.0, 'season_multiplier' => 1.0,
            'operational_margin_percent' => 0,
        ]);

        $resp = $this->postJson('/api/pricing/calculate', [
            'product_id' => $product->id,
            'quantity'   => 1,
            'demand'     => 1.5,
        ]);

        $resp->assertStatus(200);
        $this->assertEquals(150.0, $resp->json('unit_price'));
    }

    #[Test]
    public function fixed_unit_price_rule_overrides_calculated_price(): void
    {
        $product = $this->createProduct([
            'base_cost' => 500, 'procurement_cost' => 0, 'delivery_cost' => 0,
            'packaging_cost' => 0, 'demand_multiplier' => 1.0, 'season_multiplier' => 1.0,
            'operational_margin_percent' => 0,
        ]);

        PricingRule::create([
            'name'         => 'Special fixed price',
            'priority'     => 999,
            'active'       => true,
            'conditions'   => ['customer_type' => 'corporate'],
            'action_type'  => 'fixed_unit_price',
            'action_value' => 299.99,
        ]);

        $resp = $this->postJson('/api/pricing/calculate', [
            'product_id' => $product->id, 'quantity' => 3, 'customer_type' => 'corporate',
        ]);

        $resp->assertStatus(200);
        $this->assertEquals(299.99, $resp->json('unit_price'));
        $this->assertEquals(899.97, $resp->json('total_price'));
    }

    // ── GET /api/pricing/rules ──────────────────────────────────────────

    #[Test]
    public function rules_endpoint_returns_all_rules(): void
    {
        PricingRule::create(['name'=>'R1','priority'=>10,'active'=>true,'conditions'=>[],'action_type'=>'percent_discount','action_value'=>5]);
        PricingRule::create(['name'=>'R2','priority'=>20,'active'=>true,'conditions'=>[],'action_type'=>'percent_markup','action_value'=>3]);

        $this->getJson('/api/pricing/rules')
             ->assertStatus(200)
             ->assertJsonCount(2);
    }

    // ── Admin rule CRUD ─────────────────────────────────────────────────

    #[Test]
    public function admin_can_create_a_rule(): void
    {
        $this->post('/admin/rules', [
            'name'         => 'Bulk 100+',
            'priority'     => 150,
            'active'       => true,
            'conditions'   => ['quantity_gte' => 100],
            'action_type'  => 'percent_discount',
            'action_value' => 15,
        ])->assertRedirect();

        $this->assertDatabaseHas('pricing_rules', ['name' => 'Bulk 100+']);
    }

    #[Test]
    public function admin_can_update_a_rule(): void
    {
        $rule = PricingRule::create([
            'name' => 'Old', 'priority' => 10, 'active' => true,
            'conditions' => [], 'action_type' => 'percent_discount', 'action_value' => 5,
        ]);

        $this->put("/admin/rules/{$rule->id}", [
            'name'         => 'Updated Rule',
            'priority'     => 50,
            'active'       => false,
            'conditions'   => ['quantity_gte' => 20],
            'action_type'  => 'percent_discount',
            'action_value' => 8,
        ])->assertRedirect();

        $this->assertDatabaseHas('pricing_rules', ['id' => $rule->id, 'name' => 'Updated Rule']);
    }

    #[Test]
    public function admin_can_delete_a_rule(): void
    {
        $rule = PricingRule::create([
            'name' => 'To Delete', 'priority' => 10, 'active' => true,
            'conditions' => [], 'action_type' => 'percent_discount', 'action_value' => 5,
        ]);

        $this->delete("/admin/rules/{$rule->id}")->assertRedirect();
        $this->assertDatabaseMissing('pricing_rules', ['id' => $rule->id]);
    }
}
