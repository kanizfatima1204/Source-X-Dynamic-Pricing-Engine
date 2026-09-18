<?php
use App\Http\Controllers\PricingApiController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

// Pricing calculation
Route::post('/pricing/calculate',        [PricingApiController::class, 'calculate'])->name('api.pricing.calculate');
Route::get('/pricing/products/{product}',[PricingApiController::class, 'product'])  ->name('api.pricing.product');
Route::get('/pricing/rules',             [PricingApiController::class, 'rules'])    ->name('api.pricing.rules');

// Product list for frontend reload
Route::get('/pricing/products',          [ProductController::class, 'apiList'])     ->name('api.products.list');
