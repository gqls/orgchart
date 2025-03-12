<?php

// database/factories/OrganizationFactory.php
namespace Database\Factories;

use App\app\app\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OrganizationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Organization::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $name = $this->faker->company();
        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $this->faker->paragraph(),
            'logo_path' => null,
            'primary_color' => $this->faker->hexColor(),
            'secondary_color' => $this->faker->hexColor(),
        ];
    }
}
