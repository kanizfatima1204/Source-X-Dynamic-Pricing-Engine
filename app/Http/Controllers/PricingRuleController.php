<?php
namespace App\Http\Controllers;
use App\Models\PricingRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
class PricingRuleController extends Controller {
 public function index(): Response { return Inertia::render('Admin/PricingRules',['rules'=>PricingRule::orderByDesc('priority')->orderBy('id')->get()]); }
 private function validateRule(Request $r): array { return $r->validate(['name'=>'required|string|max:120','priority'=>'required|integer|min:0|max:9999','active'=>'boolean','conditions'=>'required|array','action_type'=>'required|in:percent_discount,percent_markup,fixed_unit_price','action_value'=>'required|numeric|min:0']); }
 public function store(Request $r): RedirectResponse { PricingRule::create($this->validateRule($r)); return back(); }
 public function update(Request $r,PricingRule $pricingRule): RedirectResponse { $pricingRule->update($this->validateRule($r)); return back(); }
 public function destroy(PricingRule $pricingRule): RedirectResponse { $pricingRule->delete(); return back(); }
}
