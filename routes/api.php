<?php

use App\Http\Controllers\API\Auth\AuthController;
use App\Http\Controllers\API\Charities\CharitySummaryController;
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

Route::post('login', [AuthController::class, 'login']);

Route::middleware(['openclaw.key'])->group(function () {
    Route::prefix('v1')->group(function () {
        Route::prefix('charity-transactions')->group(function () {
            Route::get('summary/daily', [CharitySummaryController::class, 'daily']);
        });
    });
});

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::prefix('v1')->group(function () {
        // here prefixs
    });
});
