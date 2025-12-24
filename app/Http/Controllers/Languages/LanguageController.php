<?php

namespace App\Http\Controllers\Languages;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class LanguageController extends Controller
{
    public function __invoke(Request $request, $locale): RedirectResponse
    {
        if (! in_array($locale, config('app.available_locales'))) {
            flash()->error(__('messages.language_is_not_supported', ['lang' => $locale]));
            return redirect()->back();
        }

        App::setLocale($locale);
        session(['locale' => $locale]);

        return redirect()->back();
    }
}
