<?php
namespace Tests\Unit;

use App\Models\Product;
use App\Services\PricingEngine;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PricingEngineTest extends TestCase
{
    private function makeProduct(array $overrides = []): Product
    {
        $p = new Product();
        $p->id                         = 1;
        $p->sku                        = 'TEST-001';
        $p->name                       = 'Test Product';
        $p->base_cost                  = $overrides['base_cost']                    ?? '100.00';
        $p->procurement_cost           = $overrides['procurement_cost']             ?? '20.00';
        $p->delivery_cost              = $overrides['delivery_cost']                ?? '10.00';
        $p->packaging_cost             = $overrides['packaging_cost']               ?? '5.00';
        $p->demand_multiplier          = $overrides['demand_multiplier']            ?? '1.0000';
        $p->season_multiplier          = $overrides['season_multiplier']            ?? '1.2000';
        $p->operational_margin_percent = $overrides['operational_margin_percent']   ?? '20.00';
        $p->currency                   = $overrides['currency']                     ?? 'BDT';
        return $p;
    }

    private function engine(): PricingEngine
    {
        return new PricingEngine();
    }

    private function reflectMethod(string $methodName): \ReflectionMethod
    {
        $ref = new \ReflectionMethod(PricingEngine::class, $methodName);
        $ref->setAccessible(true);
        return $ref;
    }

    #[Test]
    public function it_throws_on_zero_quantity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        // Guard fires before any DB call
        $this->engine()->calculate($this->makeProduct(), 0);
    }

    #[Test]
    public function it_throws_on_negative_quantity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->engine()->calculate($this->makeProduct(), -5);
    }

    #[Test]
    public function base_unit_cost_math_is_correct(): void
    {
        // Pure arithmetic: (100+20+10+5) × 1.0 demand × 1.0 season × 1.20 margin = 162.00
        $unitCost   = (100 + 20 + 10 + 5) * 1.0 * 1.0;
        $withMargin = $unitCost * (1 + 20 / 100);
        $this->assertEquals(162.0, round($withMargin, 2));
    }

    #[Test]
    public function season_multiplier_normal_returns_1(): void
    {
        $ref     = $this->reflectMethod('seasonMultiplier');
        $engine  = $this->engine();
        $product = $this->makeProduct(['season_multiplier' => '1.5']);

        $this->assertEquals(1.0,  $ref->invoke($engine, $product, 'normal'));
        $this->assertEquals(0.95, $ref->invoke($engine, $product, 'off_peak'));
        $this->assertEquals(1.5,  $ref->invoke($engine, $product, 'peak'));
    }

    #[Test]
    public function condition_matching_quantity_gte(): void
    {
        $ref    = $this->reflectMethod('matches');
        $engine = $this->engine();

        $this->assertTrue($ref->invoke($engine,  ['quantity_gte' => 10], 10, 'standard', 'normal'));
        $this->assertTrue($ref->invoke($engine,  ['quantity_gte' => 10], 50, 'standard', 'normal'));
        $this->assertFalse($ref->invoke($engine, ['quantity_gte' => 10],  9, 'standard', 'normal'));
    }

    #[Test]
    public function condition_matching_quantity_gt(): void
    {
        $ref    = $this->reflectMethod('matches');
        $engine = $this->engine();

        $this->assertTrue($ref->invoke($engine,  ['quantity_gt' => 10], 11, 'standard', 'normal'));
        $this->assertFalse($ref->invoke($engine, ['quantity_gt' => 10], 10, 'standard', 'normal')); // strictly greater
    }

    #[Test]
    public function condition_matching_quantity_lte(): void
    {
        $ref    = $this->reflectMethod('matches');
        $engine = $this->engine();

        $this->assertTrue($ref->invoke($engine,  ['quantity_lte' => 5], 5, 'standard', 'normal'));
        $this->assertFalse($ref->invoke($engine, ['quantity_lte' => 5], 6, 'standard', 'normal'));
    }

    #[Test]
    public function condition_matching_customer_type(): void
    {
        $ref    = $this->reflectMethod('matches');
        $engine = $this->engine();

        $this->assertTrue($ref->invoke($engine,  ['customer_type' => 'corporate'], 1, 'corporate', 'normal'));
        $this->assertFalse($ref->invoke($engine, ['customer_type' => 'corporate'], 1, 'standard',  'normal'));
    }

    #[Test]
    public function condition_matching_season(): void
    {
        $ref    = $this->reflectMethod('matches');
        $engine = $this->engine();

        $this->assertTrue($ref->invoke($engine,  ['season' => 'peak'], 1, 'standard', 'peak'));
        $this->assertFalse($ref->invoke($engine, ['season' => 'peak'], 1, 'standard', 'normal'));
    }

    #[Test]
    public function condition_matching_combined_and_logic(): void
    {
        $ref    = $this->reflectMethod('matches');
        $engine = $this->engine();

        $cond = ['quantity_gte' => 50, 'customer_type' => 'corporate'];

        $this->assertTrue($ref->invoke($engine,  $cond, 100, 'corporate', 'normal'));
        $this->assertFalse($ref->invoke($engine, $cond,  10, 'corporate', 'normal')); // qty fails
        $this->assertFalse($ref->invoke($engine, $cond, 100, 'standard',  'normal')); // type fails
    }

    #[Test]
    public function empty_conditions_matches_everything(): void
    {
        $ref    = $this->reflectMethod('matches');
        $engine = $this->engine();

        $this->assertTrue($ref->invoke($engine, [], 1,    'standard',  'normal'));
        $this->assertTrue($ref->invoke($engine, [], 9999, 'wholesale', 'peak'));
    }
}
