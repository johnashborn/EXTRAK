<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    /** Stub; the shell (Task 7) and the real dashboard (Task 9) replace it. */
    public function index(): View
    {
        return view('dashboard');
    }
}
