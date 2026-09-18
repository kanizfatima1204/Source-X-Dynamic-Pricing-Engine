<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Product extends Model {
    protected $fillable=['name','sku','base_cost','procurement_cost','delivery_cost','packaging_cost','demand_multiplier','season_multiplier','operational_margin_percent','currency'];
    protected $casts=['base_cost'=>'decimal:2','procurement_cost'=>'decimal:2','delivery_cost'=>'decimal:2','packaging_cost'=>'decimal:2','demand_multiplier'=>'decimal:4','season_multiplier'=>'decimal:4','operational_margin_percent'=>'decimal:2'];
    public function pricingRules(): HasMany { return $this->hasMany(PricingRule::class); }
}
