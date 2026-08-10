<?php

use App\Models\Permiso;
use App\Models\Planeacion;
use App\Models\Planta;
use App\Models\Proyecto;
use App\Models\Role;
use App\Models\User;
use App\Support\Accion;

beforeEach(function () {
    $this->planta = Planta::factory()->create();
    $this->proyecto = Proyecto::factory()->create(['planta_id' => $this->planta->id]);

    // User with full permissions (can approve)
    $roleSupervisor = Role::factory()->create();
    $permiso = Permiso::create([
        'nombre' => 'Planeación',
        'endpoint' => 'ingenierias.planeacion',
        'activo' => true,
    ]);
    $roleSupervisor->otorgar($permiso, Accion::ALL);

    $this->supervisor = User::factory()->create(['rol_id' => $roleSupervisor->id]);
    $this->supervisor->plantasAsignadas()->attach($this->planta->id);

    // User with create/read/update (cannot approve because it lacks DELETE so it does not sum to 15 / ALL)
    $roleResidente = Role::factory()->create();
    $roleResidente->otorgar($permiso, Accion::CREATE | Accion::READ | Accion::UPDATE);

    $this->residente = User::factory()->create(['rol_id' => $roleResidente->id]);
    $this->residente->proyectosAsignados()->attach($this->proyecto->id);
});

test('residente sees MisPlaneaciones view', function () {
    $this->actingAs($this->residente)
        ->get('/planeacion')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('ingenierias/planeacion/MisPlaneaciones')
            ->has('puedeCrear')
            ->has('puedeEliminar')
        );
});

test('supervisor sees Planificador view', function () {
    $this->actingAs($this->supervisor)
        ->get('/planeacion')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('ingenierias/planeacion/Planificador')
        );
});

test('residente can create a planeacion', function () {
    $data = [
        'semana' => 33,
        'anio' => 2026,
    ];

    $this->actingAs($this->residente)
        ->post("/planeacion/plantas/{$this->planta->id}/proyectos/{$this->proyecto->id}/planeaciones", $data)
        ->assertRedirect();

    $this->assertDatabaseHas('planeaciones', [
        'semana' => 33,
        'anio' => 2026,
        'proyecto_id' => $this->proyecto->id,
        'planta_id' => $this->planta->id,
        'estado' => 'borrador',
    ]);
});

test('cannot create duplicate planeacion for same semana/anio/proyecto', function () {
    Planeacion::factory()->create([
        'semana' => 33,
        'anio' => 2026,
        'proyecto_id' => $this->proyecto->id,
        'planta_id' => $this->planta->id,
        'usuario_id' => $this->residente->id,
    ]);

    $this->actingAs($this->residente)
        ->post("/planeacion/plantas/{$this->planta->id}/proyectos/{$this->proyecto->id}/planeaciones", [
            'semana' => 33,
            'anio' => 2026,
        ])
        ->assertSessionHasErrors('semana');
});

test('residente can send borrador to revision', function () {
    $planeacion = Planeacion::factory()->create([
        'proyecto_id' => $this->proyecto->id,
        'planta_id' => $this->planta->id,
        'usuario_id' => $this->residente->id,
        'estado' => 'borrador',
    ]);

    $this->actingAs($this->residente)
        ->post("/planeacion/{$planeacion->id}/enviar")
        ->assertRedirect();

    $this->assertDatabaseHas('planeaciones', [
        'id' => $planeacion->id,
        'estado' => 'enviada',
    ]);
});

test('cannot send non-borrador to revision', function () {
    $planeacion = Planeacion::factory()->enviada()->create([
        'proyecto_id' => $this->proyecto->id,
        'planta_id' => $this->planta->id,
        'usuario_id' => $this->residente->id,
    ]);

    $this->actingAs($this->residente)
        ->post("/planeacion/{$planeacion->id}/enviar")
        ->assertStatus(422);
});

test('supervisor can approve an enviada planeacion', function () {
    $planeacion = Planeacion::factory()->enviada()->create([
        'proyecto_id' => $this->proyecto->id,
        'planta_id' => $this->planta->id,
        'usuario_id' => $this->residente->id,
    ]);

    $this->actingAs($this->supervisor)
        ->post("/planeacion/{$planeacion->id}/aprobar")
        ->assertRedirect();

    $this->assertDatabaseHas('planeaciones', [
        'id' => $planeacion->id,
        'estado' => 'aprobada',
        'aprobador_id' => $this->supervisor->id,
    ]);
});

test('supervisor can reject an enviada planeacion', function () {
    $planeacion = Planeacion::factory()->enviada()->create([
        'proyecto_id' => $this->proyecto->id,
        'planta_id' => $this->planta->id,
        'usuario_id' => $this->residente->id,
    ]);

    $this->actingAs($this->supervisor)
        ->post("/planeacion/{$planeacion->id}/rechazar", ['comentarios' => 'Falta detalle en partidas'])
        ->assertRedirect();

    $this->assertDatabaseHas('planeaciones', [
        'id' => $planeacion->id,
        'estado' => 'rechazada',
        'comentarios_aprobacion' => 'Falta detalle en partidas',
    ]);
});

test('cannot approve a non-enviada planeacion', function () {
    $planeacion = Planeacion::factory()->create([
        'proyecto_id' => $this->proyecto->id,
        'planta_id' => $this->planta->id,
        'usuario_id' => $this->residente->id,
        'estado' => 'borrador',
    ]);

    $this->actingAs($this->supervisor)
        ->post("/planeacion/{$planeacion->id}/aprobar")
        ->assertStatus(422);
});

test('supervisor can report aprobada to nomina', function () {
    $planeacion = Planeacion::factory()->aprobada()->create([
        'proyecto_id' => $this->proyecto->id,
        'planta_id' => $this->planta->id,
        'usuario_id' => $this->residente->id,
    ]);

    $this->actingAs($this->supervisor)
        ->post("/planeacion/{$planeacion->id}/reportar-nomina")
        ->assertRedirect();

    $this->assertDatabaseHas('planeaciones', [
        'id' => $planeacion->id,
        'reportada_nomina' => true,
    ]);
});

test('cannot report non-aprobada to nomina', function () {
    $planeacion = Planeacion::factory()->enviada()->create([
        'proyecto_id' => $this->proyecto->id,
        'planta_id' => $this->planta->id,
        'usuario_id' => $this->residente->id,
    ]);

    $this->actingAs($this->supervisor)
        ->post("/planeacion/{$planeacion->id}/reportar-nomina")
        ->assertStatus(422);
});

test('supervisor can delete borrador planeacion', function () {
    $planeacion = Planeacion::factory()->create([
        'proyecto_id' => $this->proyecto->id,
        'planta_id' => $this->planta->id,
        'usuario_id' => $this->residente->id,
        'estado' => 'borrador',
    ]);

    $this->actingAs($this->supervisor)
        ->delete("/planeacion/{$planeacion->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('planeaciones', [
        'id' => $planeacion->id,
    ]);
});

test('cannot delete non-borrador planeacion', function () {
    $planeacion = Planeacion::factory()->enviada()->create([
        'proyecto_id' => $this->proyecto->id,
        'planta_id' => $this->planta->id,
        'usuario_id' => $this->residente->id,
    ]);

    $this->actingAs($this->supervisor)
        ->delete("/planeacion/{$planeacion->id}")
        ->assertStatus(422);
});

test('show returns planeacion detail', function () {
    $planeacion = Planeacion::factory()->create([
        'proyecto_id' => $this->proyecto->id,
        'planta_id' => $this->planta->id,
        'usuario_id' => $this->residente->id,
    ]);

    $this->actingAs($this->residente)
        ->get("/planeacion/{$planeacion->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('ingenierias/planeacion/Show')
            ->where('planeacion.id', $planeacion->id)
        );
});
