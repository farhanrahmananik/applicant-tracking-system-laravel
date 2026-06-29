<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\ApplicationStageHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApplicationStageHistory>
 */
class ApplicationStageHistoryFactory extends Factory
{
    protected $model = ApplicationStageHistory::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'application_id' => Application::factory(),
            'from_stage' => 'applied',
            'to_stage' => 'screening',
            'changed_by_id' => User::factory(),
            'note' => fake()->optional()->sentence(),
            'changed_at' => now(),
        ];
    }
}
