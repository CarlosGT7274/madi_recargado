<?php

namespace Database\Factories;

use App\Models\Levantamiento;
use App\Models\Planta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Levantamiento>
 */
class LevantamientoFactory extends Factory
{
    protected $model = Levantamiento::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'planta_id' => Planta::factory(),
            'folio' => 'LEV-'.fake()->unique()->numerify('####'),
            'nombre' => fake()->sentence(3),
            'cliente' => fake()->company(),
            'obra' => fake()->optional()->sentence(2),
            'solicitante' => fake()->name(),
            'prioridad' => fake()->randomElement(['urgente', 'normal', 'grande_compleja']),
            'estatus_admin' => 'recibida',
        ];
    }

    public function urgente(): static
    {
        return $this->state(fn (array $attributes) => [
            'prioridad' => 'urgente',
        ]);
    }

    public function grandeCompleja(): static
    {
        return $this->state(fn (array $attributes) => [
            'prioridad' => 'grande_compleja',
        ]);
    }
}
