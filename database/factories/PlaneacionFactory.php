<?php

namespace Database\Factories;

use App\Models\Planeacion;
use App\Models\Planta;
use App\Models\Proyecto;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Planeacion>
 */
class PlaneacionFactory extends Factory
{
    protected $model = Planeacion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'semana' => fake()->numberBetween(1, 52),
            'anio' => now()->year,
            'planta_id' => Planta::factory(),
            'proyecto_id' => Proyecto::factory(),
            'usuario_id' => User::factory(),
            'estado' => 'borrador',
            'reportada_nomina' => false,
        ];
    }

    public function enviada(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'enviada',
            'fecha_envio' => now(),
        ]);
    }

    public function aprobada(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'aprobada',
            'fecha_envio' => now()->subDay(),
            'fecha_aprobacion' => now(),
            'aprobador_id' => User::factory(),
        ]);
    }

    public function rechazada(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'rechazada',
            'fecha_envio' => now()->subDay(),
            'fecha_rechazo' => now(),
            'aprobador_id' => User::factory(),
            'comentarios_aprobacion' => fake()->sentence(),
        ]);
    }
}
