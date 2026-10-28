<?php

namespace App\Http\Controllers\Memberships;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class MembershipController extends Controller
{
    public function __construct()
    {
        $this->middleware('capability:browse-rt-membership');
    }

    public function index(): View
    {
        return view('memberships.index');
    }
}
