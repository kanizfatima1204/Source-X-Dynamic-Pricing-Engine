<?php
namespace App\Http\Controllers;
use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;
class PricingPageController extends Controller { public function index(): Response { return Inertia::render('PricingDemo',['products'=>Product::orderBy('name')->get()]); } }
