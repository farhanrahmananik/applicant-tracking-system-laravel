<?php

namespace Database\Factories;

use App\Models\Candidate;
use App\Models\CandidateResume;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CandidateResume>
 */
class CandidateResumeFactory extends Factory
{
    protected $model = CandidateResume::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $filename = Str::uuid().'.pdf';

        return [
            'candidate_id' => Candidate::factory(),
            'uploaded_by_id' => User::factory(),
            'original_name' => 'candidate-resume.pdf',
            'stored_path' => 'resumes/testing/'.$filename,
            'disk' => 'local',
            'mime_type' => 'application/pdf',
            'size_bytes' => fake()->numberBetween(50_000, 500_000),
            'extension' => 'pdf',
            'is_primary' => false,
            'uploaded_at' => now(),
        ];
    }

    public function primary(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_primary' => true,
        ]);
    }
}
