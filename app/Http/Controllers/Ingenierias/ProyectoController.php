<?php

namespace App\Http\Controllers\Ingenierias;

use App\Domain\Ingenierias\Proyectos\PlantaService;
use App\Http\Controllers\Controller;
use App\Models\Planta;
use Inertia\Inertia;
use Inertia\Response;

class ProyectoController extends Controller
{
    public function index(PlantaService $plantaService): Response
    {
        return Inertia::render('proyectos/Index', [
            'plantas' => $plantaService->listAll(),
        ]);
    }

    public function show(Planta $planta, PlantaService $plantaService): Response
    {
        return Inertia::render('proyectos/Show', [
            'planta' => $plantaService->detail($planta),
        ]);
    }
}
