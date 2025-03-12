<?php

// database/factories/ScenarioFactory.php
namespace Database\Factories;

use App\app\app\Models\Organization;
use App\app\app\Models\Scenario;
use App\app\app\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScenarioFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Scenario::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'organization_id' => Organization::factory(),
            'user_id' => User::factory(),
            'name' => $this->faker->words(3, true) . ' Scenario',
            'description' => $this->faker->paragraph(),
            'is_current' => false,
            'is_base' => false,
        ];
    }

    /**
     * Indicate that the scenario is current.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function current()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_current' => true,
            ];
        });
    }

    /**
     * Indicate that the scenario is the base scenario.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function base()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_base' => true,
            ];
        });
    }
}
