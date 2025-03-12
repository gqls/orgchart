<?php

// database/factories/DepartmentFactory.php
namespace Database\Factories;

use App\app\app\Models\Department;
use App\app\app\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class DepartmentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Department::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => $this->faker->unique()->department(),
            'code' => strtoupper($this->faker->unique()->lexify('???')),
            'description' => $this->faker->sentence(),
            'color' => $this->faker->hexColor(),
        ];
    }
}
