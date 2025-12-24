<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\SystemPreferencesUpdateRequest;
use App\Settings\SystemPreferences;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SystemController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:browse-settings')->only('index');
        $this->middleware('permission:edit-settings')->only('store');
    }

    public function index(SystemPreferences $systemPreferences): View
    {
        $items = $systemPreferences->toCollection();
        $items->put('logo_sm_asset', asset($systemPreferences->logo_sm));
        $items->put('logo_lg_asset', asset($systemPreferences->logo_lg));
        $items->put('favicon_asset', asset($systemPreferences->favicon));

        // Settings
        $settings = $items->all();

        return view('settings.system', [
            'settings' => $settings,
        ]);
    }

    public function store(SystemPreferencesUpdateRequest $request, SystemPreferences $systemPreferences): JsonResponse
    {
        $validated = $request->validated();

        $setEnv = $systemPreferences->name != $validated['name'];

        $systemPreferences->name = $validated['name'];

        // Handle logo upload
        if ($logoSmFile = $request->file('logo_sm')) {
            // Delete if logo uploaded
            if ($systemPreferences->logo_sm != config('logo.main_sm')) {
                Storage::delete(SystemPreferences::IMAGE_PATH.$systemPreferences->logo_sm);
            }

            $ext = $logoSmFile->extension();
            $fileName = 'logo-sm.'.$ext;
            $logoSmFile->storeAs(SystemPreferences::IMAGE_PATH, $fileName);
            $validated['logo_sm'] = SystemPreferences::IMAGE_PATH.$fileName;
            $systemPreferences->logo_sm = $validated['logo_sm'];
        }

        // Handle logo upload
        if ($logoLgFile = $request->file('logo_lg')) {
            // Delete if logo uploaded
            if ($systemPreferences->logo_lg != config('logo.main')) {
                Storage::delete(SystemPreferences::IMAGE_PATH.$systemPreferences->logo_lg);
            }

            $ext = $logoLgFile->extension();
            $fileName = 'logo.'.$ext;
            $logoLgFile->storeAs(SystemPreferences::IMAGE_PATH, $fileName);
            $validated['logo_lg'] = SystemPreferences::IMAGE_PATH.$fileName;
            $systemPreferences->logo_lg = $validated['logo_lg'];
        }

        // Handle logo upload
        if ($favicon = $request->file('favicon')) {
            // Delete if favicon uploaded
            if ($systemPreferences->favicon != config('logo.favicon')) {
                Storage::delete(SystemPreferences::IMAGE_PATH.$systemPreferences->favicon);
            }

            $ext = $favicon->extension();
            $fileName = 'favicon.'.$ext;
            $favicon->storeAs(SystemPreferences::IMAGE_PATH, $fileName);
            $validated['favicon'] = SystemPreferences::IMAGE_PATH.$fileName;
            $systemPreferences->favicon = $validated['favicon'];
        }

        $systemPreferences->save();

        // Update APP_NAME in .env. Only update if different to avoid server reloading
        if ($setEnv) {
            set_env("APP_NAME", $systemPreferences->name, true);
            Artisan::call("config:clear");
        }

        flash()->success(__('messages.settings_saved'));

        return response()->json(['status' => true, 'redirect' => route('settings.system.index')]);
    }
}
