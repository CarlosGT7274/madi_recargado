<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(RolesPermisosSeeder::class);

        $usuario = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $usuario->roles()->attach(Role::where('nombre', 'Super Administrador')->value('id'));
    }
}
