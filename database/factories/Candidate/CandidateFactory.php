<?php

namespace Database\Factories\Candidate;

use App\Models\Candidate\Candidate;
use App\Enums\Candidate\CandidateStatus;
use App\Enums\Candidate\ExperienceLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

class CandidateFactory extends Factory
{
    protected $model = Candidate::class;

    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'tenant_id' => 1, // You'll need to adjust this based on your tenant setup
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'status' => $this->faker->randomElement(CandidateStatus::cases())->value,
            'source' => $this->faker->randomElement(['website', 'referral', 'linkedin', 'indeed', 'recruiter']),
            'notes' => $this->faker->optional()->paragraph(),
            'experience_level' => $this->faker->randomElement(ExperienceLevel::cases())->value,
            'location' => $this->faker->city() . ', ' . $this->faker->stateAbbr(),
            'availability' => $this->faker->optional()->dateTimeBetween('now', '+6 months'),
            'salary_expectation' => $this->faker->optional()->numberBetween(40000, 200000),
            'preferred_work_type' => $this->faker->randomElement(['remote', 'onsite', 'hybrid', 'flexible']),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CandidateStatus::ACTIVE->value,
        ]);
    }

    public function senior(): static
    {
        return $this->state(fn (array $attributes) => [
            'experience_level' => ExperienceLevel::SENIOR->value,
            'salary_expectation' => $this->faker->numberBetween(100000, 200000),
        ]);
    }
} 