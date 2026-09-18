<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\PricingRule;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Product::updateOrCreate(
            ['sku' => 'SX-RICE-001'],
            [
                'name' => 'Organic Rice',
                'base_cost' => 120,
                'procurement_cost' => 18,
                'delivery_cost' => 8,
                'packaging_cost' => 4,
                'demand_multiplier' => 1.05,
                'season_multiplier' => 1.15,
                'operational_margin_percent' => 18,
            ]
        );

        Product::updateOrCreate(
            ['sku' => 'SX-LENTIL-001'],
            [
                'name' => 'Organic Lentils',
                'base_cost' => 145,
                'procurement_cost' => 20,
                'delivery_cost' => 8,
                'packaging_cost' => 5,
                'demand_multiplier' => 1.02,
                'season_multiplier' => 1.10,
                'operational_margin_percent' => 17,
            ]
        );

        $rules = [
            [
                'name' => 'Bulk 50+ units',
                'priority' => 100,
                'active' => 1,
                'conditions' => ['quantity_gte' => 50],
                'action_type' => 'percent_discount',
                'action_value' => 10,
            ],
            [
                'name' => 'Bulk 10+ units',
                'priority' => 90,
                'active' => 1,
                'conditions' => ['quantity_gte' => 10],
                'action_type' => 'percent_discount',
                'action_value' => 5,
            ],
            [
                'name' => 'Corporate buyer',
                'priority' => 80,
                'active' => 1,
                'conditions' => ['customer_type' => 'corporate'],
                'action_type' => 'percent_discount',
                'action_value' => 7,
            ],
            [
                'name' => 'Peak season markup',
                'priority' => 20,
                'active' => 1,
                'conditions' => ['season' => 'peak'],
                'action_type' => 'percent_markup',
                'action_value' => 3,
            ],
        ];

        foreach ($rules as $rule) {
            PricingRule::updateOrCreate(
                ['name' => $rule['name']],
                $rule
            );
        }
    }
}
