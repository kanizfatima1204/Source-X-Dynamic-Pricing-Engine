<?php
namespace App\Http\Controllers;
use App\Models\Product;
use App\Models\PricingRule;
use App\Services\PricingEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class PricingApiController extends Controller {
    public function calculate(Request $request, PricingEngine $engine): JsonResponse {
        $data=$request->validate(['product_id'=>'required|integer|exists:products,id','quantity'=>'required|integer|min:1','customer_type'=>'nullable|string|in:standard,corporate,wholesale','season'=>'nullable|string|in:normal,peak,off_peak','demand'=>'nullable|numeric|min:0.5|max:3']);
        $product=Product::findOrFail($data['product_id']);
        return response()->json($engine->calculate($product,$data['quantity'],$data['customer_type']??'standard',$data['season']??'normal',$data['demand']??null));
    }
    public function product(Product $product): JsonResponse { return response()->json($product); }
    public function rules(): JsonResponse { return response()->json(PricingRule::orderByDesc('priority')->orderBy('id')->get()); }
}
