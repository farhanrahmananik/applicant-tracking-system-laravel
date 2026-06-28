<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Department;
use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobPosting>
 */
class JobPostingFactory extends Factory
{
    protected $model = JobPosting::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $salaryMin = fake()->numberBetween(35_000, 90_000);

        return [
            'company_id' => Company::factory(),
            'department_id' => null,
            'title' => fake()->jobTitle(),
            'slug' => fake()->unique()->slug(3),
            'employment_type' => fake()->randomElement(JobPosting::EMPLOYMENT_TYPES),
            'workplace_type' => fake()->randomElement(JobPosting::WORKPLACE_TYPES),
            'location' => fake()->city(),
            'openings' => fake()->numberBetween(1, 5),
            'salary_min' => $salaryMin,
            'salary_max' => $salaryMin + fake()->numberBetween(5_000, 30_000),
            'currency' => 'EUR',
            'experience_level' => fake()->randomElement(['Entry level', 'Mid level', 'Senior', 'Lead']),
            'description' => fake()->paragraphs(3, true),
            'requirements' => fake()->paragraphs(2, true),
            'responsibilities' => fake()->paragraphs(2, true),
            'benefits' => fake()->paragraph(),
            'status' => 'draft',
            'published_at' => null,
            'closes_at' => fake()->dateTimeBetween('+2 weeks', '+3 months'),
            'created_by_id' => User::factory(),
            'updated_by_id' => null,
        ];
    }

    public function forDepartment(Department $department): static
    {
        return $this->state(fn (array $attributes): array => [
            'company_id' => $department->company_id,
            'department_id' => $department->id,
        ]);
    }

    public function open(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'open',
            'published_at' => now(),
        ]);
    }
}
