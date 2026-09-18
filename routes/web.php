<?php
use App\Http\Controllers\PricingPageController;
use App\Http\Controllers\PricingRuleController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

// Demo page
Route::get('/', [PricingPageController::class, 'index'])->name('pricing.demo');

// Admin – pricing rules
Route::get('/admin/rules',                [PricingRuleController::class, 'index'])  ->name('pricing.rules.index');
Route::post('/admin/rules',              [PricingRuleController::class, 'store'])  ->name('pricing.rules.store');
Route::put('/admin/rules/{pricingRule}', [PricingRuleController::class, 'update']) ->name('pricing.rules.update');
Route::delete('/admin/rules/{pricingRule}', [PricingRuleController::class, 'destroy'])->name('pricing.rules.destroy');

// Admin – products
Route::get('/admin/products',              [ProductController::class, 'index'])  ->name('admin.products.index');
Route::post('/admin/products',            [ProductController::class, 'store'])  ->name('admin.products.store');
Route::put('/admin/products/{product}',   [ProductController::class, 'update']) ->name('admin.products.update');
Route::delete('/admin/products/{product}', [ProductController::class, 'destroy'])->name('admin.products.destroy');
