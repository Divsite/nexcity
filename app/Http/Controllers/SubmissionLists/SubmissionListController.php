<?php

namespace App\Http\Controllers\SubmissionLists;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubmissionListController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:process-submissions');
    }

    public function __invoke(Request $request): View
    {
        return view('submission-lists.index');
    }
}
