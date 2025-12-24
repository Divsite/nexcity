<?php
namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;

class PrivacyController extends Controller
{
    public function __invoke()
    {
        return view('pages.legals.privacy');
    }
}
