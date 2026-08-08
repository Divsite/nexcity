<?php

use App\Http\Controllers\API\Auth\AuthController;
use App\Http\Controllers\API\Charities\CharitySummaryController;
use App\Http\Controllers\API\Charities\QuickCharityController;
use App\Http\Controllers\API\Distributions\DistributionController;
use App\Http\Controllers\API\Distributions\ScanController;
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

        // Phase 3 — field scanning.
        // A scan only reads; marking is a separate, deliberate act, so a
        // mis-scan cannot silently record a distribution.
        Route::post('scan', [ScanController::class, 'scan'])
            ->middleware('capability:scan-resident-qr|scan-qurban-coupon|scan-zakat-coupon');

        Route::get('distributions', [DistributionController::class, 'index'])
            ->middleware('capability:browse-mosque-charity-distributions|browse-rt-residents');

        Route::get('distributions/{distribution}/recipients', [DistributionController::class, 'recipients'])
            ->middleware('capability:browse-mosque-charity-distributions|browse-rt-residents');

        Route::patch('distribution-recipients/{recipient}', [ScanController::class, 'markRecipient'])
            ->middleware('capability:edit-mosque-charity-distributions|edit-rt-residents');

        Route::post('distribution-recipients/{recipient}/photos', [ScanController::class, 'attachPhoto'])
            ->middleware('capability:edit-mosque-charity-distributions|edit-rt-residents');

        // Phase 3.5 — recording charity at the counter.
        Route::get('charity-types', [QuickCharityController::class, 'types'])
            ->middleware('capability:browse-mosque-charity-transactions');

        Route::post('charity-transactions', [QuickCharityController::class, 'store'])
            ->middleware('capability:add-mosque-charity-transactions');
    });
});
