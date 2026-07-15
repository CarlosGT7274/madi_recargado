<?php

use App\Models\Permiso;
use App\Models\Role;
use App\Models\User;
use App\Support\Accion;

function permisoRolesDeTest(): Permiso
{
    return Permiso::firstOrCreate(
        ['nombre' => 'Roles'],
        ['padre_id' => null, 'endpoint' => 'roles.index', 'activo' => true],
    );
}

function usuarioConPermisoDeRoles(int $bitmask): User
{
    $rol = Role::factory()->create();
    $rol->otorgar(permisoRolesDeTest(), $bitmask);

    return User::factory()->create(['rol_id' => $rol->id]);
}

test('un usuario sin permiso no puede ver el listado de roles', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('roles.index'))
        ->assertForbidden();
});

test('un usuario con permiso de lectura puede ver el listado de roles', function () {
    $user = usuarioConPermisoDeRoles(Accion::READ);

    $this->actingAs($user)
        ->get(route('roles.index'))
        ->assertOk();
});

test('un usuario con permiso de creación puede crear un rol', function () {
    $user = usuarioConPermisoDeRoles(Accion::ALL);

    $this->actingAs($user)
        ->post(route('roles.store'), ['nombre' => 'Supervisor de Obra', 'activo' => true])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('roles', ['nombre' => 'Supervisor de Obra']);
});

test('un usuario sin permiso de creación no puede crear un rol', function () {
    $user = usuarioConPermisoDeRoles(Accion::READ);

    $this->actingAs($user)
        ->post(route('roles.store'), ['nombre' => 'Otro Rol'])
        ->assertForbidden();
});

test('no se puede eliminar un rol que tiene usuarios asignados', function () {
    $admin = usuarioConPermisoDeRoles(Accion::ALL);
    $rolConUsuarios = Role::factory()->create();
    User::factory()->create(['rol_id' => $rolConUsuarios->id]);

    $this->actingAs($admin)
        ->delete(route('roles.destroy', $rolConUsuarios))
        ->assertSessionHasErrors('role');

    $this->assertDatabaseHas('roles', ['id' => $rolConUsuarios->id]);
});

test('se pueden actualizar los permisos de un rol', function () {
    $admin = usuarioConPermisoDeRoles(Accion::ALL);
    $rol = Role::factory()->create();
    $permiso = permisoRolesDeTest();

    $this->actingAs($admin)
        ->put(route('roles.permisos.update', $rol), [
            'permisos' => [$permiso->id => Accion::READ | Accion::UPDATE],
        ])
        ->assertSessionHasNoErrors();

    expect($rol->fresh()->tienePermiso($permiso, Accion::READ))->toBeTrue();
    expect($rol->fresh()->tienePermiso($permiso, Accion::DELETE))->toBeFalse();
});
