<?php

namespace Database\Factories;

use App\Models\Offer;
use App\Models\OfferStatusHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OfferStatusHistory>
 */
class OfferStatusHistoryFactory extends Factory
{
    protected $model = OfferStatusHistory::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'offer_id' => Offer::factory(),
            'from_status' => 'draft',
            'to_status' => 'sent',
            'note' => fake()->optional()->sentence(),
            'changed_by_id' => User::factory(),
            'changed_at' => now(),
        ];
    }
}
