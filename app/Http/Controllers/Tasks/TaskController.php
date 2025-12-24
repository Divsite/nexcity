<?php

namespace App\Http\Controllers\Tasks;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:process-submissions');
    }

    public function current(): View
    {
        return view('tasks.current');
    }

    public function completed(): View
    {
        return view('tasks.completed');
    }
}
