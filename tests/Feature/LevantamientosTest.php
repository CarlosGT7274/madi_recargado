<?php

use App\Models\Levantamiento;
use App\Models\Permiso;
use App\Models\Planta;
use App\Models\Role;
use App\Models\User;
use App\Support\Accion;

beforeEach(function () {
    $role = Role::factory()->create();
    $permiso = Permiso::create([
        'nombre' => 'Levantamientos',
        'endpoint' => 'ingenierias.levantamientos',
        'activo' => true,
    ]);
    $role->otorgar($permiso, Accion::ALL);

    $this->user = User::factory()->create([
        'rol_id' => $role->id,
    ]);
});

test('users with permission can view levantamientos list', function () {
    $planta = Planta::factory()->create();
    $levantamientos = Levantamiento::factory()->count(3)->create(['planta_id' => $planta->id]);

    $this->actingAs($this->user)
        ->get("/ingenierias/plantas/{$planta->id}/levantamientos")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('ingenierias/plantas/levantamientos/Index')
            ->where('planta.id', $planta->id)
            ->has('levantamientos', 3)
        );
});

test('users with permission can view levantamiento details', function () {
    $levantamiento = Levantamiento::factory()->create();

    $this->actingAs($this->user)
        ->get("/ingenierias/plantas/{$levantamiento->planta_id}/levantamientos/{$levantamiento->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('ingenierias/plantas/levantamientos/Show')
            ->where('levantamiento.id', $levantamiento->id)
            ->where('planta.id', $levantamiento->planta_id)
        );
});

test('users with permission can create levantamientos', function () {
    $planta = Planta::factory()->create();
    $data = [
        'folio' => 'LEV-TEST-001',
        'nombre' => 'Levantamiento de prueba',
        'cliente' => 'Cliente de prueba',
        'prioridad' => 'normal',
    ];

    $this->actingAs($this->user)
        ->post("/ingenierias/plantas/{$planta->id}/levantamientos", $data)
        ->assertRedirect();

    $this->assertDatabaseHas('levantamientos', [
        'folio' => 'LEV-TEST-001',
        'planta_id' => $planta->id,
        'nombre' => 'Levantamiento de prueba',
    ]);
});

test('users with permission can update levantamientos', function () {
    $levantamiento = Levantamiento::factory()->create([
        'nombre' => 'Nombre original',
    ]);

    $data = [
        'folio' => $levantamiento->folio, // Keep the same folio
        'nombre' => 'Nombre actualizado',
        'cliente' => 'Cliente actualizado',
        'prioridad' => 'urgente',
    ];

    $this->actingAs($this->user)
        ->put("/ingenierias/plantas/{$levantamiento->planta_id}/levantamientos/{$levantamiento->id}", $data)
        ->assertRedirect();

    $this->assertDatabaseHas('levantamientos', [
        'id' => $levantamiento->id,
        'nombre' => 'Nombre actualizado',
        'prioridad' => 'urgente',
    ]);
});

test('users with permission can delete levantamientos', function () {
    $levantamiento = Levantamiento::factory()->create();

    $this->actingAs($this->user)
        ->delete("/ingenierias/plantas/{$levantamiento->planta_id}/levantamientos/{$levantamiento->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('levantamientos', [
        'id' => $levantamiento->id,
    ]);
});
