<?php

namespace App\Http\Controllers\AuthenticationLogs;

use App\Http\Controllers\Controller;

class AuthenticationLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:all-user-sessions')->only('index');
    }

    public function index()
    {
        return view('authentication-logs.index');
    }
}
