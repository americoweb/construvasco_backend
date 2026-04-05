<?php

namespace Database\Factories;

use App\Models\Candidate\Resume;
use Illuminate\Database\Eloquent\Factories\Factory;

class ResumeFactory extends Factory
{
    protected $model = Resume::class;

    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'tenant_id' => 1,
            'original_filename' => $this->faker->firstName() . '_' . $this->faker->lastName() . '_Resume.pdf',
            'file_path' => 'resumes/' . $this->faker->uuid() . '.pdf',
            'file_size' => $this->faker->numberBetween(100000, 2000000),
            'mime_type' => 'application/pdf',
            'status' => $this->faker->randomElement(['pending', 'processing', 'completed', 'failed']),
            'parsed_data' => [
                'contact_info' => [
                    'email' => $this->faker->email(),
                    'phone' => $this->faker->phoneNumber(),
                ],
                'skills' => $this->faker->randomElements([
                    'JavaScript', 'Python', 'React', 'Laravel', 'AWS', 'Docker', 'MySQL'
                ], $this->faker->numberBetween(3, 7)),
                'experience_years' => $this->faker->numberBetween(1, 15),
            ],
            'confidence_score' => $this->faker->randomFloat(2, 60, 99),
            'parsing_errors' => $this->faker->optional()->randomElements([
                'Could not extract phone number',
                'Education dates unclear',
                'Some skills may be missed'
            ], $this->faker->numberBetween(0, 2)),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'confidence_score' => $this->faker->randomFloat(2, 80, 99),
        ]);
    }
}
