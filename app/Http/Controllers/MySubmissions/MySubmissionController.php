<?php

namespace App\Http\Controllers\MySubmissions;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MySubmissionController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('my-submissions.index');
    }
}
