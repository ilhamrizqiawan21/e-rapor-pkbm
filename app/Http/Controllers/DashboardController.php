<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardService $service): View
    {
        $requestedRole = match (true) {
            $request->routeIs('dashboard.admin') => 'ADMIN',
            $request->routeIs('dashboard.guru') => 'GURU',
            $request->routeIs('dashboard.kepsek') => 'KEPSEK',
            default => null,
        };

        return view('dashboard', $service->for($request->user(), $requestedRole));
    }
}
