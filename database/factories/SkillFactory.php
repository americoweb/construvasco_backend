<?php

namespace Database\Factories;

use App\Models\Skill;
use Illuminate\Database\Eloquent\Factories\Factory;

class SkillFactory extends Factory
{
    protected $model = Skill::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1, // You'll need to adjust this based on your tenant setup
            'name' => $this->faker->unique()->word(),
            'category' => $this->faker->randomElement(['programming', 'design', 'management', 'soft_skills', 'tools']),
            'description' => $this->faker->optional()->sentence(),
            'is_active' => true,
        ];
    }

    public function programming(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'programming',
            'name' => $this->faker->randomElement(['PHP', 'Laravel', 'JavaScript', 'React', 'Vue.js', 'Python', 'Java', 'C#', 'SQL', 'Git']),
        ]);
    }

    public function design(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'design',
            'name' => $this->faker->randomElement(['UI/UX Design', 'Graphic Design', 'Web Design', 'Adobe Photoshop', 'Figma', 'Sketch', 'Illustrator']),
        ]);
    }
} 