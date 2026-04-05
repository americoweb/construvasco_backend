<?php

namespace Database\Factories\Candidate;

use App\Models\Candidate\Resume;
use App\Models\Candidate\Candidate;
use Illuminate\Database\Eloquent\Factories\Factory;

class ResumeFactory extends Factory
{
    protected $model = Resume::class;

    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'candidate_id' => Candidate::factory(),
            'tenant_id' => 1, // You'll need to adjust this based on your tenant setup
            'original_filename' => $this->faker->randomElement([
                'resume.pdf', 'CV.docx', 'portfolio.pdf', 'experience.pdf'
            ]),
            'file_path' => 'resumes/' . $this->faker->uuid() . '.pdf',
            'file_size' => $this->faker->numberBetween(100000, 2000000), // 100KB to 2MB
            'mime_type' => $this->faker->randomElement([
                'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ]),
            'status' => $this->faker->randomElement(['pending', 'processing', 'completed', 'failed']),
            'parsed_data' => [
                'name' => $this->faker->name(),
                'email' => $this->faker->email(),
                'phone' => $this->faker->phoneNumber(),
                'skills' => $this->faker->randomElements([
                    'PHP', 'Laravel', 'JavaScript', 'React', 'Vue.js', 'Python', 'Java', 'SQL'
                ], $this->faker->numberBetween(3, 8)),
                'experience' => $this->faker->numberBetween(1, 15),
                'education' => $this->faker->randomElement(['Bachelor', 'Master', 'PhD', 'Associate'])
            ],
            'confidence_score' => $this->faker->randomFloat(2, 0.5, 1.0),
            'parsing_errors' => $this->faker->optional()->randomElements([
                'Could not parse education section', 'Skills extraction incomplete', 'Contact information unclear'
            ], $this->faker->numberBetween(0, 2)),
            'reparsed_at' => $this->faker->optional()->dateTimeBetween('-1 month', 'now'),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'confidence_score' => $this->faker->randomFloat(2, 0.8, 1.0),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'confidence_score' => null,
            'parsing_errors' => ['File format not supported', 'Unable to extract text content'],
        ]);
    }
} 