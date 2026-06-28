<?php

namespace Database\Factories;

use App\Models\Candidate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Candidate>
 */
class CandidateFactory extends Factory
{
    protected $model = Candidate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'location' => fake()->city().', '.fake()->country(),
            'source' => fake()->randomElement([
                'career_site',
                'referral',
                'linkedin',
                'agency',
                'job_board',
                'direct',
            ]),
            'experience_years' => fake()->randomFloat(1, 0, 25),
            'skills' => implode(', ', fake()->randomElements([
                'PHP',
                'Laravel',
                'JavaScript',
                'MySQL',
                'Recruitment',
                'Project Management',
            ], 3)),
            'current_position' => fake()->jobTitle(),
            'expected_salary' => fake()->numberBetween(35_000, 120_000),
            'availability' => fake()->randomElement([
                'immediate',
                'two_weeks',
                'one_month',
                'two_months',
            ]),
            'status' => fake()->randomElement(Candidate::STATUSES),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'active',
        ]);
    }
}
