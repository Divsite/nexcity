<?php

namespace App\Http\Controllers\ActivityLogs;

use App\Http\Controllers\Controller;

class ActivityLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:all-user-activities')->only('index');
    }

    public function index()
    {
        return view('activity-logs.index');
    }
}
