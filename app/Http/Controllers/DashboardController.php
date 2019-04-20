<?php

namespace App\Http\Controllers;

class DashboardController extends Controller
{
    /**
     * Show the dashboard
     *
     * @return \Illuminate\Http\Response
     */
    public function show()
    {
        return response()->view('dashboard');
    }
}
