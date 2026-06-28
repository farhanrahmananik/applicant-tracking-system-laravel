<?php

namespace Database\Factories;

use App\Models\InterviewFeedback;
use App\Models\InterviewSchedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InterviewFeedback>
 */
class InterviewFeedbackFactory extends Factory
{
    protected $model = InterviewFeedback::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'interview_schedule_id' => InterviewSchedule::factory(),
            'summary' => fake()->paragraph(),
            'strengths' => fake()->optional()->paragraph(),
            'weaknesses' => fake()->optional()->paragraph(),
            'recommendation' => fake()->randomElement(InterviewFeedback::RECOMMENDATIONS),
            'rating' => fake()->numberBetween(1, 5),
            'submitted_by_id' => User::factory(),
            'submitted_at' => now(),
        ];
    }
}
