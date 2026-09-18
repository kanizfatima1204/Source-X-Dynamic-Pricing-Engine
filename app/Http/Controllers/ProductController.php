<?php
namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Products', [
            'products' => Product::orderBy('name')->get(),
        ]);
    }

    private function validateProduct(Request $r): array
    {
        return $r->validate([
            'name'                       => 'required|string|max:150',
            'sku'                        => 'required|string|max:60',
            'base_cost'                  => 'required|numeric|min:0',
            'procurement_cost'           => 'nullable|numeric|min:0',
            'delivery_cost'              => 'nullable|numeric|min:0',
            'packaging_cost'             => 'nullable|numeric|min:0',
            'demand_multiplier'          => 'nullable|numeric|min:0.1|max:10',
            'season_multiplier'          => 'nullable|numeric|min:0.1|max:10',
            'operational_margin_percent' => 'nullable|numeric|min:0|max:500',
            'currency'                   => 'nullable|string|max:5',
        ]);
    }

    public function store(Request $r): RedirectResponse
    {
        $data = $this->validateProduct($r);
        $r->validate(['sku' => 'unique:products,sku']);
        Product::create($data);
        return back()->with('success', 'Product created.');
    }

    public function update(Request $r, Product $product): RedirectResponse
    {
        $data = $this->validateProduct($r);
        $product->update($data);
        return back()->with('success', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();
        return back()->with('success', 'Product deleted.');
    }

    /** JSON list for the frontend axios reload */
    public function apiList(): JsonResponse
    {
        return response()->json(Product::orderBy('name')->get());
    }
}
