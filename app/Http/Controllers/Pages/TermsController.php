<?php
namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;

class TermsController extends Controller
{
    public function __invoke()
    {
        return view('pages.legals.terms');
    }
}
