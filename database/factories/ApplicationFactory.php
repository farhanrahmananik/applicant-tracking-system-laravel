<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\Candidate;
use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
{
    protected $model = Application::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'candidate_id' => Candidate::factory(),
            'job_posting_id' => JobPosting::factory(),
            'source' => fake()->randomElement([
                'career_site',
                'referral',
                'linkedin',
                'agency',
                'job_board',
                'direct',
            ]),
            'applied_date' => fake()->dateTimeBetween('-3 months', 'now'),
            'current_status' => 'applied',
            'notes' => fake()->optional()->paragraph(),
            'created_by_id' => User::factory(),
            'updated_by_id' => null,
        ];
    }

    public function screening(): static
    {
        return $this->state(fn (array $attributes): array => [
            'current_status' => 'screening',
        ]);
    }

    public function terminal(): static
    {
        return $this->state(fn (array $attributes): array => [
            'current_status' => fake()->randomElement(Application::TERMINAL_STATUSES),
        ]);
    }
}
