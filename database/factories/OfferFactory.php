<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\JobPosting;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Offer>
 */
class OfferFactory extends Factory
{
    protected $model = Offer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'application_id' => Application::factory()->state(['current_status' => 'selected']),
            'offer_title' => fake()->jobTitle(),
            'salary_amount' => fake()->numberBetween(45_000, 125_000),
            'currency' => 'EUR',
            'employment_type' => fake()->randomElement(JobPosting::EMPLOYMENT_TYPES),
            'expected_joining_date' => fake()->dateTimeBetween('+1 month', '+4 months'),
            'expiry_date' => fake()->dateTimeBetween('+1 week', '+1 month'),
            'status' => 'draft',
            'notes' => fake()->optional()->paragraph(),
            'created_by_id' => User::factory(),
            'updated_by_id' => null,
        ];
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => 'sent']);
    }

    public function terminal(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => fake()->randomElement(Offer::TERMINAL_STATUSES),
        ]);
    }
}
