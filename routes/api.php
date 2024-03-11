<?php

use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/*Products*/

Route::get('/products', [ProductController::class, 'index']);
Route::get('/edit-products/{id}', [ProductController::class, 'show']);
Route::post('/add-products', [ProductController::class, 'store']); // Create a new product
Route::post('/update-products/{id}', [ProductController::class, 'update']); // Update a specific product
Route::delete('/delete-products/{id}', [ProductController::class, 'destroy']); // Delete a specific product

/*Order*/

Route::get('/orders', [OrderController::class, 'index']);
Route::post('/order', [OrderController::class, 'store']);
Route::post('/update-order/{id}', [OrderController::class, 'updateStatus']);
Route::delete('/order/{id}', [OrderController::class, 'destroy']);
Route::get('/orders/{id}', [OrderController::class, 'show']);

/*Auth*/
Route::post('/login', [ProductController::class, 'login']);
Route::get('/logout', [ProductController::class, 'logout']);
