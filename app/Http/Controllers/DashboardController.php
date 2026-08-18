<?php

namespace App\Http\Controllers;

use App\Actions\Dashboard\DashboardAction;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(DashboardAction $dashboard): Response
    {
        return Inertia::render('Dashboard', [
            'metricas' => $dashboard->metricas(),
        ]);
    }
}
