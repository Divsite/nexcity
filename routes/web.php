<?php

use App\Http\Controllers\ActivityLogs\ActivityLogController;
use App\Http\Controllers\AuthenticationLogs\AuthenticationLogController;
use App\Http\Controllers\Charities\CharityTransactionController;
use App\Http\Controllers\Dashboards\DashboardController;
use App\Http\Controllers\Forms\BuilderController;
use App\Http\Controllers\Forms\FormController;
use App\Http\Controllers\Forms\FillFormController;
use App\Http\Controllers\FormSubmissionFiles\FormSubmissionFileController;
use App\Http\Controllers\FormSubmissionFiles\PreviewController;
use App\Http\Controllers\FormSubmissionFiles\TemporaryFileController;
use App\Http\Controllers\FormSubmissions\FormSubmissionController;
use App\Http\Controllers\FormTypes\FormTypeController;
use App\Http\Controllers\Groups\GroupController;
use App\Http\Controllers\Languages\LanguageController;
use App\Http\Controllers\MySubmissions\MySubmissionController;
use App\Http\Controllers\Notifications\NotificationController;
use App\Http\Controllers\Menus\UserMenuController;
use App\Http\Controllers\Masters\MasterDataController;
use App\Http\Controllers\Locations\LocationLookupController;
use App\Http\Controllers\Organizations\OrganizationController;
use App\Http\Controllers\Pages\ModulePlaceholderController;
use App\Http\Controllers\Permissions\PermissionController;
use App\Http\Controllers\Profiles\ProfileController;
use App\Http\Controllers\Roles\InternalRoleController;
use App\Http\Controllers\Roles\RoleController;
use App\Http\Controllers\Residents\ResidentController;
use App\Http\Controllers\Settings\SystemController;
use App\Http\Controllers\Settings\PartnerUserController;
use App\Http\Controllers\SubmissionLists\SubmissionListController;
use App\Http\Controllers\Submissions\SubmissionController;
use App\Http\Controllers\Tasks\TaskController;
use App\Http\Controllers\Themes\ThemeController;
use App\Http\Controllers\Users\UserController;
use App\Http\Controllers\FormProcesses\FormProcessController;
use App\Http\Controllers\Memberships\MembershipController;
use App\Http\Controllers\Pages\PrivacyPolicyController;
use App\Http\Controllers\Pages\HomeController;
use App\Http\Controllers\Pages\PrivacyController;
use App\Http\Controllers\Pages\TermsController;
use App\Http\Middleware\EnsureProfileIsCompleted;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', HomeController::class);
Route::get('privacy-policy', PrivacyPolicyController::class)->name('privacy-policy');

Route::get('terms', TermsController::class)->name('terms');
Route::get('privacy', PrivacyController::class)->name('privacy');

Route::get('/login', function () {
    return redirect()->route('login');
});

Auth::routes(['register' => config('core.register_enabled'), 'verify' => config('core.verify_enabled')]);

// Change theme
    Route::post('update-theme', ThemeController::class)->name('update-theme');

    Route::middleware(common_middleware())->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'index'])
            ->withoutMiddleware(EnsureProfileIsCompleted::class)
            ->name('index');
        Route::put('/update', [ProfileController::class, 'update'])
            ->withoutMiddleware(EnsureProfileIsCompleted::class)
            ->name('update');
        Route::get('/change-password', [ProfileController::class, 'showChangePasswordForm'])
            ->withoutMiddleware(EnsureProfileIsCompleted::class)
            ->name('change-password-form');
        Route::post('/change-password', [ProfileController::class, 'changePassword'])
            ->withoutMiddleware(EnsureProfileIsCompleted::class)
            ->name('change-password');
        Route::get('/activities', [ProfileController::class, 'activities'])->name('activities');
        Route::get('/recent-sessions', [ProfileController::class, 'recentSessions'])->name('recent-sessions');
        Route::get('/change-email', [ProfileController::class, 'showChangeEmailForm'])->name('change-email-form');
        Route::post('/change-email', [ProfileController::class, 'changeEmail'])->name('change-email');
        Route::get('/change-username', [ProfileController::class, 'showChangeUsernameForm'])->name('change-username-form');
        Route::post('/change-username', [ProfileController::class, 'changeUsername'])->name('change-username');
    });

    Route::resource('/users', UserController::class);
    Route::get('/internal-roles', [InternalRoleController::class, 'index'])->name('internal-roles.index');
    Route::post('/internal-roles/{context}/{slug}', [InternalRoleController::class, 'update'])
        ->name('internal-roles.update');
    Route::post('/internal-roles/{context}/levels', [InternalRoleController::class, 'storeLevel'])
        ->name('internal-roles.levels.store');
    Route::put('/internal-roles/{context}/levels/{slug}', [InternalRoleController::class, 'updateLevel'])
        ->name('internal-roles.levels.update');
    Route::delete('/internal-roles/{context}/levels/{slug}', [InternalRoleController::class, 'destroyLevel'])
        ->name('internal-roles.levels.destroy');
    Route::resource('/roles', RoleController::class);
    Route::resource('/permissions', PermissionController::class)->only('index', 'show');
    Route::post('/menus/flush-cache', [UserMenuController::class, 'flushCache'])->name('menus.flush');
    Route::resource('/menus', UserMenuController::class)->except('show');
    Route::resource('/forms', FormController::class);
    Route::resource('forms.submissions', FormSubmissionController::class)->shallow();
    Route::get('fill/forms', [FillFormController::class, 'index'])->name('fill.forms');

    Route::get('my-submissions', MySubmissionController::class)->name('my-submissions.index');
    Route::get('submission-list', SubmissionListController::class)->name('submission.list');
    Route::prefix('tasks')->name('tasks.')->group(function () {
        Route::get('current', [TaskController::class, 'current'])->name('current');
        Route::get('completed', [TaskController::class, 'completed'])->name('completed');
    });

    Route::post('submission/process/store/{id}', [SubmissionController::class, 'updateProcess'])
        ->name('submission.process.store');
    Route::get('submission/data/{id}', [SubmissionController::class, 'getData'])->name('submission.data');
    Route::get('submission/{id}/edit', [SubmissionController::class, 'edit'])->name('submission.edit');
    Route::put('submission/{submission}/update', [SubmissionController::class, 'update'])
        ->name('submission.update');
    Route::get('submission/{id}/{type?}', [SubmissionController::class, 'show'])->name('submission.show');

    // Workflow Processes
    Route::get('forms/{id}/processes', [FormProcessController::class, 'index'])
        ->name('forms.processes.index');

    // Statuses
    Route::post('forms/{id}/default-status', [FormProcessController::class, 'updateDefaultStatus'])
        ->name('forms.update-default-status');
    Route::get('forms/{id}/process/statuses', [FormProcessController::class, 'getStatutes'])
        ->name('forms.process.statuses');
    Route::post('forms/{id}/process/statuses/store', [FormProcessController::class, 'storeStatus'])
        ->name('forms.process.statuses.store');
    Route::put('forms/process/status/update/{id}', [FormProcessController::class, 'updateStatus'])
        ->name('forms.process.statuses.update');
    Route::delete('forms/process/status/delete/{id}', [FormProcessController::class, 'destroyStatus'])
        ->name('forms.process.statuses.destroy');

    // Processes
    Route::get('forms/{id}/processes/list', [FormProcessController::class, 'getProcesses'])
        ->name('forms.processes.list');
    Route::post('forms/{id}/processes/store', [FormProcessController::class, 'storeProcess'])
        ->name('forms.processes.store');
    Route::put('forms/processes/update/{id}', [FormProcessController::class, 'updateProcess'])
        ->name('forms.processes.update');
    Route::delete('forms/processes/delete/{id}', [FormProcessController::class, 'destroyProcess'])
        ->name('forms.processes.destroy');
    Route::post('forms/{id}/processes/sort', [FormProcessController::class, 'updateProcessSort'])
        ->name('forms.processes.sort');
    Route::get('forms/processes/processor-users', [FormProcessController::class, 'getProcessorUsers'])
        ->name('forms.processes.processor-users');
    Route::post('forms/processes/managers', [FormProcessController::class, 'getManagers'])
        ->name('forms.processes.managers');

    // AJAX Locations
    Route::prefix('ajax/locations')->name('ajax.locations.')->group(function () {
        Route::get('provinces', [LocationLookupController::class, 'provinces'])->name('provinces');
        Route::get('cities', [LocationLookupController::class, 'cities'])->name('cities');
        Route::get('districts', [LocationLookupController::class, 'districts'])->name('districts');
        Route::get('villages', [LocationLookupController::class, 'villages'])->name('villages');
        Route::get('citizens', [LocationLookupController::class, 'citizensAssociations'])->name('citizens');
        Route::get('neighborhoods', [LocationLookupController::class, 'neighborhoodAssociations'])->name('neighborhoods');
    });

    // Form Builder
    Route::prefix('form-builder')->name('form-builder.')->group(function () {
        Route::post('/add-field', [BuilderController::class, 'addField'])->name('add-field');
        Route::post('/edit-field', [BuilderController::class, 'editField'])->name('edit-field');
    });

    // Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::resource('/system', SystemController::class)->only(['index', 'store']);
        Route::get('/all-user-activities', [ActivityLogController::class, 'index'])->name('all-user-activities');
        Route::get('/all-user-sessions', [AuthenticationLogController::class, 'index'])->name('all-user-sessions');
        Route::get('/user-management', [\App\Http\Controllers\Settings\UserManagementController::class, 'index'])
            ->name('user-management.index');
        Route::post('/user-management/{level}', [\App\Http\Controllers\Settings\UserManagementController::class, 'update'])
            ->name('user-management.update');
        Route::get('/users', [PartnerUserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [PartnerUserController::class, 'create'])->name('users.create');
        Route::post('/users', [PartnerUserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [PartnerUserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [PartnerUserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [PartnerUserController::class, 'destroy'])->name('users.destroy');
        Route::get('/summary', ModulePlaceholderController::class)
            ->name('summary')
            ->defaults('slug', 'settings-summary');
        Route::get('/organization-profile', ModulePlaceholderController::class)
            ->name('organization-profile')
            ->defaults('slug', 'settings-organization-profile');
        Route::get('/security', ModulePlaceholderController::class)
            ->name('security')
            ->defaults('slug', 'settings-security');
        Route::get('/billing', ModulePlaceholderController::class)
            ->name('billing')
            ->defaults('slug', 'settings-billing');
    });

    Route::resource('/groups', GroupController::class);
    Route::resource('/form-types', FormTypeController::class);

    Route::resource('organizations', OrganizationController::class);
    Route::resource('residents', ResidentController::class)->parameters(['residents' => 'resident']);

    // master data modules
    Route::get('master-data', [MasterDataController::class, 'index'])->name('master-data.index');

    Route::prefix('mosque')->name('mosque.')->group(function () {
        // charity modules
        Route::get('charity-transactions/recap/daily/print', [CharityTransactionController::class, 'dailyRecap'])
            ->name('charity-transactions.daily-recap.print');
        Route::resource('charity-transactions', CharityTransactionController::class);

        Route::get('qurban', ModulePlaceholderController::class)->name('qurban')->defaults('slug', 'mosque-qurban');
        Route::get('distributions', ModulePlaceholderController::class)->name('distribution')->defaults('slug', 'mosque-distribution');
        Route::get('scan', ModulePlaceholderController::class)->name('scan')->defaults('slug', 'mosque-scan');
        Route::get('inventory', ModulePlaceholderController::class)->name('inventory')->defaults('slug', 'mosque-inventory');
        Route::get('crm', ModulePlaceholderController::class)->name('crm')->defaults('slug', 'mosque-crm');
        Route::get('reports/charity', ModulePlaceholderController::class)->name('report.charity')->defaults('slug', 'mosque-report-charity');
        Route::get('reports/distribution', ModulePlaceholderController::class)->name('report.distribution')->defaults('slug', 'mosque-report-distribution');
    });

    Route::prefix('rt')->name('rt.')->group(function () {
        Route::get('citizens', function () {
            return redirect()->route('residents.index');
        })->name('citizen.data');
        Route::get('inventory', ModulePlaceholderController::class)->name('inventory')->defaults('slug', 'rt-inventory');
        Route::get('events', ModulePlaceholderController::class)->name('events')->defaults('slug', 'rt-events');
        Route::get('dues', ModulePlaceholderController::class)->name('dues')->defaults('slug', 'rt-dues');
        Route::get('news', ModulePlaceholderController::class)->name('news')->defaults('slug', 'rt-news');
        Route::get('feedback', ModulePlaceholderController::class)->name('feedback')->defaults('slug', 'rt-feedback');
        Route::get('membership', [MembershipController::class, 'index'])
            ->name('membership');
        Route::get('reports', ModulePlaceholderController::class)->name('reports')->defaults('slug', 'rt-reports');
    });

    Route::prefix('resident')->name('resident.')->group(function () {
        Route::get('dues', ModulePlaceholderController::class)->name('dues')->defaults('slug', 'resident-dues');
        Route::get('information', ModulePlaceholderController::class)->name('information')->defaults('slug', 'resident-information');
        Route::get('complaints', ModulePlaceholderController::class)->name('complaints')->defaults('slug', 'resident-complaints');
        Route::get('events', ModulePlaceholderController::class)->name('events')->defaults('slug', 'resident-events');
    });

    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('/show/{id}', [NotificationController::class, 'show'])->name('show');
        Route::get('/has-read/{id}', [NotificationController::class, 'setAsHasRead'])->name('set-as-has-read');
        Route::delete('/destroy/{id}', [NotificationController::class, 'destroy'])->name('destroy');
        Route::get('/mark-as-read', [NotificationController::class, 'markAsRead'])->name('mark-as-read');
        Route::delete('/delete-all', [NotificationController::class, 'deleteAll'])->name('destroy-all');
    });

    Route::prefix('form-submission-files')->name('form-submission-files.')->group(function (){
        Route::post('/preview', [PreviewController::class, 'store'])->name('preview.store');
        Route::delete('/preview/delete', [PreviewController::class, 'destroy'])->name('preview.destroy');
        Route::post('/temp/{id}/{uuid}', [TemporaryFileController::class, 'store'])->name('temp.store');
        Route::delete('/temp/delete', [TemporaryFileController::class, 'destroy'])->name('temp.destroy');
        Route::get('/show/{id}', [FormSubmissionFileController::class, 'show'])->name('show');
    });
});

Route::get('language/{locale}', LanguageController::class)->name('change-locale');
