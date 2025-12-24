<?php
namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;

class PrivacyPolicyController extends Controller
{
    public function __invoke()
    {
        return view('pages.privacy-policies.privacy-policy');
    }
}
