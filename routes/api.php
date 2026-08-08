<?php

use App\Http\Controllers\API\Auth\AuthController;
use App\Http\Controllers\API\Charities\CharitySummaryController;
use App\Http\Controllers\API\Home\HomeController;
use App\Http\Controllers\API\Organizations\OrganizationController;
use App\Http\Controllers\API\Organizations\OrganizationDetailController;
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

Route::post('auth/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum', 'organization.context'])->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::post('auth/active-organization', [AuthController::class, 'setActiveOrganization']);
});

Route::middleware(['openclaw.key'])->group(function () {
    Route::prefix('v1')->group(function () {
        Route::prefix('charity-transactions')->group(function () {
            Route::get('summary/daily', [CharitySummaryController::class, 'daily']);
        });
    });
});

Route::middleware(['auth:sanctum', 'organization.context'])->group(function () {
    Route::prefix('v1')->group(function () {
        Route::get('home', [HomeController::class, 'index']);
        Route::get('organizations', [OrganizationController::class, 'index']);
        Route::get('organizations/{slug}', [OrganizationController::class, 'show']);

        // Phase 3 — Organization detail + Qurban programs
        Route::get('organizations/{slug}/detail', [OrganizationDetailController::class, 'detail']);
        Route::get('organizations/{slug}/qurban-programs', [OrganizationDetailController::class, 'qurbanPrograms']);
        Route::get('organizations/{slug}/qurban-programs/{programId}', [OrganizationDetailController::class, 'qurbanProgramDetail']);
    });
});
