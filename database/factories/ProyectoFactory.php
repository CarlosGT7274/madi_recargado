<?php

namespace Database\Factories;

use App\Models\Planta;
use App\Models\Proyecto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Proyecto>
 */
class ProyectoFactory extends Factory
{
    protected $model = Proyecto::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'planta_id' => Planta::factory(),
            'folio' => 'PRY-'.fake()->unique()->numerify('####'),
            'tipo' => 'grande',
            'nombre' => fake()->sentence(3),
            'descripcion' => fake()->paragraph(),
            'estado' => 'activo',
        ];
    }

    public function chico(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo' => 'chico',
        ]);
    }
}
