<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->randomElement([
                'Engineering',
                'Human Resources',
                'Finance',
                'Operations',
                'Marketing',
            ]).' '.fake()->unique()->numberBetween(100, 9999),
            'slug' => fake()->unique()->slug(2),
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'location' => fake()->city(),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
