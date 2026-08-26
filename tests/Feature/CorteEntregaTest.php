<?php
use App\Models\Permiso;
use App\Models\Planta;
use App\Models\Role;
use App\Models\User;
use App\Support\Accion;

beforeEach(function () {
    $this->planta = Planta::factory()->create();

    // Use existing permission if migrations created it, otherwise create
    $permisoParent = Permiso::firstOrCreate(
        ['endpoint' => 'ingenierias', 'padre_id' => null],
        ['nombre' => 'Ingenierías', 'activo' => true]
    );

    $permiso = Permiso::firstOrCreate(
        ['endpoint' => 'planeacion', 'padre_id' => $permisoParent->id],
        ['nombre' => 'Planeación', 'activo' => true]
    );

    $rolSupervisor = Role::factory()->create();
    $rolSupervisor->otorgar($permiso, [Accion::ALL, 'aprobar', 'supervisar']);

    $this->supervisor = User::factory()->create();
    $this->supervisor->roles()->attach($rolSupervisor->id);
    $this->supervisor->plantasAsignadas()->attach($this->planta->id);

    $rolResidente = Role::factory()->create();
    $rolResidente->otorgar($permiso, [Accion::CREATE, Accion::READ, Accion::UPDATE]);

    $this->residente = User::factory()->create();
    $this->residente->roles()->attach($rolResidente->id);
});

test('supervisor can list usuarios with corte permissions', function () {
    $this->actingAs($this->supervisor)
        ->getJson('/planeacion/usuarios/corte')
        ->assertOk()
        ->assertJsonFragment(['id' => $this->residente->id, 'nombre' => $this->residente->name]);
});

test('usuarios corte list excludes users without create permission', function () {
    $sinPermiso = User::factory()->create();

    $this->actingAs($this->supervisor)
        ->getJson('/planeacion/usuarios/corte')
        ->assertOk()
        ->assertJsonMissing(['id' => $sinPermiso->id]);
});

test('residente cannot access usuarios corte list', function () {
    $this->actingAs($this->residente)
        ->getJson('/planeacion/usuarios/corte')
        ->assertForbidden();
});

test('supervisor can update corte for a user', function () {
    $this->actingAs($this->supervisor)
        ->put("/planeacion/usuarios/{$this->residente->id}/corte", [
            'corte_dia_semana' => 'viernes',
            'corte_hora' => '18:00',
            'corte_semana_relativa' => 'actual',
        ])
        ->assertRedirect();

    $this->residente->refresh();

    expect($this->residente->corte_dia_semana)->toBe('viernes')
        ->and($this->residente->corte_hora->format('H:i'))->toBe('18:00')
        ->and($this->residente->corte_semana_relativa)->toBe('actual');
});

test('corteEntregaPara returns correct deadline', function () {
    $this->residente->update([
        'corte_dia_semana' => 'viernes',
        'corte_hora' => '18:00:00',
        'corte_semana_relativa' => 'actual',
    ]);

    $corte = $this->residente->refresh()->corteEntregaPara(2026, 35);

    expect($corte)->not->toBeNull()
        ->and($corte->format('Y-m-d'))->toBe('2026-08-28')
        ->and($corte->format('H:i'))->toBe('18:00');
});

test('corteEntregaPara returns null when no corte configured', function () {
    expect($this->residente->corteEntregaPara(2026, 35))->toBeNull();
});
