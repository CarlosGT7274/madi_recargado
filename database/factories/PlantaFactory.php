<?php

namespace Database\Factories;

use App\Models\Planta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Planta>
 */
class PlantaFactory extends Factory
{
    protected $model = Planta::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'folio' => 'PLT-'.fake()->unique()->numerify('####'),
            'nombre' => fake()->company(),
            'direccion' => fake()->address(),
            'descripcion' => fake()->sentence(),
            'activa' => true,
        ];
    }

    public function inactiva(): static
    {
        return $this->state(fn (array $attributes) => [
            'activa' => false,
        ]);
    }
}
