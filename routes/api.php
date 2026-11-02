<?php

use App\Http\Controllers\API\Announcements\AnnouncementController;
use App\Http\Controllers\API\Auth\AuthController;
use App\Http\Controllers\API\Auth\OtpController;
use App\Http\Controllers\API\Charities\AvailableYearsController;
use App\Http\Controllers\API\Charities\CharityListController;
use App\Http\Controllers\API\Charities\CharityReportController;
use App\Http\Controllers\API\Charities\CharitySummaryController;
use App\Http\Controllers\API\Charities\QuickCharityController;
use App\Http\Controllers\API\Distributions\DistributionController;
use App\Http\Controllers\API\Distributions\DistributionSummaryController;
use App\Http\Controllers\API\Distributions\ScanController;
use App\Http\Controllers\API\Dues\MyDuesController;
use App\Http\Controllers\API\Dues\RtDuesController;
use App\Http\Controllers\API\Home\HomeController;
use App\Http\Controllers\API\Home\MosqueSummaryController;
use App\Http\Controllers\API\Home\RtSummaryController;
use App\Http\Controllers\API\Organizations\OrganizationController;
use App\Http\Controllers\API\Organizations\OrganizationDetailController;
use App\Http\Controllers\API\Qurbans\QurbanOverviewController;
use App\Http\Controllers\API\Qurbans\QurbanParticipantController;
use App\Http\Controllers\API\Qurbans\QurbanPaymentController;
use App\Http\Controllers\API\Qurbans\QurbanSlotController;
use App\Http\Controllers\API\Residents\MyProfileController;
use App\Http\Controllers\API\Residents\RtCorrectionController;
use App\Http\Controllers\API\Residents\RtResidentController;
use App\Http\Controllers\API\Stats\UsageSummaryController;
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

// Signing in with a WhatsApp code, for warga the RT already registered.
Route::post('auth/otp/request', [OtpController::class, 'request'])->middleware('throttle:otp-request');
Route::post('auth/otp/verify', [OtpController::class, 'verify'])->middleware('throttle:otp-verify');

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

        Route::get('stats/summary', UsageSummaryController::class);
    });
});

Route::middleware(['auth:sanctum', 'organization.context'])->group(function () {
    Route::prefix('v1')->group(function () {
        Route::get('home', [HomeController::class, 'index']);

        // The mosque admin home. Guarded by any one of the officer
        // capabilities: the screen adapts to what the caller may see.
        Route::get('mosque/summary', [MosqueSummaryController::class, 'index']);

        // The RT admin home. Any RT officer capability opens it; each section
        // inside is gated on its own.
        Route::get('rt/summary', [RtSummaryController::class, 'index'])
            ->middleware('capability:browse-rt-residents|browse-rt-dues|scan-resident-qr');
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

        // "Data saya" — a warga reading their own resident record and asking
        // the RT to correct it. Scoped to the caller; no id to tamper with.
        Route::get('me/profile', [MyProfileController::class, 'show']);
        Route::post('me/profile/corrections', [MyProfileController::class, 'storeCorrection'])
            ->middleware('throttle:10,60');

        // The RT's own warga, read-only, and the corrections they sent.
        Route::get('rt/residents', [RtResidentController::class, 'index'])
            ->middleware('capability:browse-rt-residents');
        Route::get('rt/corrections', [RtCorrectionController::class, 'index'])
            ->middleware('capability:browse-rt-residents');
        Route::post('rt/corrections/{correction}/resolve', [RtCorrectionController::class, 'resolve'])
            ->middleware('capability:edit-rt-residents');

        // Iuran from the treasurer's side: who owes, and recording a payment.
        // Setting up schemes and rates stays on the web.
        Route::get('rt/dues', [RtDuesController::class, 'index'])
            ->middleware('capability:browse-rt-dues');
        Route::get('rt/dues/arrears', [RtDuesController::class, 'arrears'])
            ->middleware('capability:browse-rt-dues');
        Route::get('rt/dues/periods/{period}/bills', [RtDuesController::class, 'bills'])
            ->middleware('capability:browse-rt-dues');
        Route::patch('rt/dues/bills/{bill}', [RtDuesController::class, 'update'])
            ->middleware('capability:edit-rt-dues');

        // Iuran — a resident reading their own dues. Scoped to the caller, so
        // there is no id here to tamper with.
        Route::get('me/dues', [MyDuesController::class, 'index'])
            ->middleware('capability:browse-resident-dues');

        // Phase 3.5 — recording charity at the counter.
        // The Amal tab: the ledger it lists, and the types it filters by.
        Route::get('charity-transactions', [CharityListController::class, 'index'])
            ->middleware('capability:browse-mosque-charity-transactions');

        // Serves both the home screen's trend and the Laporan tab: one
        // aggregate over different windows.
        Route::get('charity-report', [CharityReportController::class, 'index'])
            ->middleware('capability:browse-mosque-charity-transactions');

        // The year selector's options. Offered years only — see the
        // controller for why a generated range is worse than useless.
        Route::get('years', [AvailableYearsController::class, 'index']);

        // A year of distributions: the totals, and who is still owed.
        Route::get('distribution-summary', [DistributionSummaryController::class, 'index']);

        // The mosque's own qurban season: programmes, quota left, livestock.
        Route::get('qurban-overview', [QurbanOverviewController::class, 'index']);

        // The patungan board — Sapi 1, Sapi 2, and who is on each line.
        // No capability middleware: a warga reads this to decide which animal
        // to join. Who may see a phone number, and who may name somebody else,
        // is decided inside the controller.
        Route::get('qurban-slots', [QurbanSlotController::class, 'index']);
        Route::post('qurban-slots', [QurbanSlotController::class, 'store']);

        // Instalments against one line. Reading is open to the person who
        // holds it as well as to pengurus; only a pengurus may record money.
        Route::get('qurban-orders/{order}/payments', [QurbanPaymentController::class, 'index']);
        Route::post('qurban-orders/{order}/payments', [QurbanPaymentController::class, 'store']);

        // Patungan: who holds a share, and taking one more at the counter.
        Route::get('qurban-participants', [QurbanParticipantController::class, 'index']);
        Route::post('qurban-participants', [QurbanParticipantController::class, 'store']);

        Route::get('charity-types', [QuickCharityController::class, 'types'])
            ->middleware('capability:browse-mosque-charity-transactions');

        Route::post('charity-transactions', [QuickCharityController::class, 'store'])
            ->middleware('capability:add-mosque-charity-transactions');

        // Announcements. No capability middleware: reading a notice is not a
        // privilege, it is the point. Who may read which one is decided per
        // row by Announcement::scopeVisibleTo, because the answer differs
        // between an RT's kerja bakti and a mosque's open kajian.
        Route::get('announcements', [AnnouncementController::class, 'index']);
        Route::get('announcements/{announcement}', [AnnouncementController::class, 'show']);
        Route::get('organizations/{slug}/announcements', [AnnouncementController::class, 'forOrganization']);
    });
});
