<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PricingRule extends Model {
    protected $fillable=['name','priority','active','conditions','action_type','action_value'];
    protected $casts=['active'=>'boolean','conditions'=>'array','priority'=>'integer','action_value'=>'decimal:4'];
}
