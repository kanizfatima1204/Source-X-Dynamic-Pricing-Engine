<?php
namespace App\Services;
use App\Models\Product;
use App\Models\PricingRule;
use Illuminate\Support\Collection;

class PricingEngine {
    public function calculate(Product $product, int $quantity, string $customerType='standard', string $season='normal', ?float $demand=null): array {
        if ($quantity < 1) throw new \InvalidArgumentException('Quantity must be at least 1.');
        $demandMultiplier = $demand ?? (float)$product->demand_multiplier;
        $seasonMultiplier = $this->seasonMultiplier($product, $season);
        $unitCost = (float)$product->base_cost + (float)$product->procurement_cost + (float)$product->delivery_cost + (float)$product->packaging_cost;
        $costBeforeMargin = $unitCost * $demandMultiplier * $seasonMultiplier;
        $margin = 1 + ((float)$product->operational_margin_percent / 100);
        $unitPrice = $costBeforeMargin * $margin;
        $applied=[];
        $rules = PricingRule::query()->where('active',true)->orderByDesc('priority')->orderBy('id')->get();
        foreach ($rules as $rule) {
            if (!$this->matches($rule->conditions ?? [], $quantity, $customerType, $season)) continue;
            $before=$unitPrice;
            if ($rule->action_type==='percent_discount') $unitPrice *= 1 - ((float)$rule->action_value/100);
            elseif ($rule->action_type==='percent_markup') $unitPrice *= 1 + ((float)$rule->action_value/100);
            elseif ($rule->action_type==='fixed_unit_price') $unitPrice=(float)$rule->action_value;
            $applied[]=['id'=>$rule->id,'name'=>$rule->name,'action_type'=>$rule->action_type,'value'=>(float)$rule->action_value,'before'=>round($before,2),'after'=>round($unitPrice,2)];
        }
        $unitPrice=max(0, round($unitPrice,2));
        return ['product_id'=>$product->id,'sku'=>$product->sku,'quantity'=>$quantity,'customer_type'=>$customerType,'season'=>$season,'currency'=>$product->currency,'breakdown'=>['base_cost'=>(float)$product->base_cost,'procurement_cost'=>(float)$product->procurement_cost,'delivery_cost'=>(float)$product->delivery_cost,'packaging_cost'=>(float)$product->packaging_cost,'demand_multiplier'=>$demandMultiplier,'season_multiplier'=>$seasonMultiplier,'operational_margin_percent'=>(float)$product->operational_margin_percent,'unit_cost_before_margin'=>round($costBeforeMargin,2)],'unit_price'=>$unitPrice,'total_price'=>round($unitPrice*$quantity,2),'applied_rules'=>$applied];
    }
    private function seasonMultiplier(Product $product,string $season): float { return match($season){ 'peak'=>max(1.0,(float)$product->season_multiplier), 'off_peak'=>0.95, default=>1.0 }; }
    private function matches(array $c,int $q,string $type,string $season): bool {
        if (isset($c['quantity_gt']) && !($q > (int)$c['quantity_gt'])) return false;
        if (isset($c['quantity_gte']) && !($q >= (int)$c['quantity_gte'])) return false;
        if (isset($c['quantity_lte']) && !($q <= (int)$c['quantity_lte'])) return false;
        if (isset($c['customer_type']) && $c['customer_type'] !== $type) return false;
        if (isset($c['season']) && $c['season'] !== $season) return false;
        return true;
    }
}
