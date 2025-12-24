<?php

namespace App\Http\Controllers\Forms;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class FillFormController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:add-submissions')->only('index');
    }

    public function index(): View
    {
        return view('forms.fill');
    }
}
