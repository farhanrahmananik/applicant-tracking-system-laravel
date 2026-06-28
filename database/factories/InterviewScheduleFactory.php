<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\InterviewSchedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InterviewSchedule>
 */
class InterviewScheduleFactory extends Factory
{
    protected $model = InterviewSchedule::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(InterviewSchedule::TYPES);

        return [
            'application_id' => Application::factory(),
            'interviewer_id' => User::factory(),
            'type' => $type,
            'status' => 'scheduled',
            'scheduled_at' => fake()->dateTimeBetween('+1 day', '+2 months'),
            'duration_minutes' => fake()->randomElement([30, 45, 60, 90]),
            'location' => $type === 'onsite' ? fake()->address() : null,
            'meeting_link' => $type === 'video' ? 'https://meet.example.test/'.fake()->uuid() : null,
            'notes' => fake()->optional()->sentence(),
            'created_by_id' => User::factory(),
            'updated_by_id' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'completed',
            'scheduled_at' => fake()->dateTimeBetween('-2 months', '-1 day'),
        ]);
    }
}
