<?php

// database/factories/PositionFactory.php
namespace Database\Factories;

use App\app\app\Models\Department;
use App\app\app\Models\Organization;
use App\app\app\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

class PositionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Position::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $functions = ['Leadership', 'Finance', 'HR', 'BD', 'Marketing', 'Operations', 'IT', 'Legal'];
        $grades = ['1', '2', '3', '4', '5', 'C10', 'C12', 'C13', 'C45', 'C56'];

        return [
            'organization_id' => Organization::factory(),
            'department_id' => Department::factory(),
            'title' => $this->faker->jobTitle(),
            'employee_id' => '2' . $this->faker->unique()->numberBetween(10000, 99999),
            'grade' => $this->faker->randomElement($grades),
            'function' => $this->faker->randomElement($functions),
            'sub_function' => null,
            'region' => $this->faker->randomElement(['EMEA', 'NAM', 'APAC', 'LATAM']),
            'country' => $this->faker->country(),
            'office' => $this->faker->city(),
            'tenure' => $this->faker->numberBetween(0, 20),
            'fully_loaded_cost' => $this->faker->numberBetween(50000, 250000),
            'cost_center' => 'CC' . $this->faker->numberBetween(100, 999),
            'contract_type' => $this->faker->randomElement(['Permanent', 'Contractor', 'Temporary']),
            'manager_id' => null,
            'name' => $this->faker->name(),
        ];
    }
}