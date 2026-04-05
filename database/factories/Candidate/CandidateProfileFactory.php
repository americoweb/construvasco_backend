<?php

namespace Database\Factories\Candidate;

use App\Models\Candidate\CandidateProfile;
use App\Models\Candidate\Candidate;
use Illuminate\Database\Eloquent\Factories\Factory;

class CandidateProfileFactory extends Factory
{
    protected $model = CandidateProfile::class;

    public function definition(): array
    {
        return [
            'candidate_id' => Candidate::factory(),
            'tenant_id' => 1, // You'll need to adjust this based on your tenant setup
            'summary' => $this->faker->paragraph(),
            'linkedin_url' => $this->faker->optional()->url(),
            'github_url' => $this->faker->optional()->url(),
            'portfolio_url' => $this->faker->optional()->url(),
            'address' => $this->faker->optional()->streetAddress(),
            'city' => $this->faker->optional()->city(),
            'state' => $this->faker->optional()->state(),
            'country' => $this->faker->optional()->country(),
            'postal_code' => $this->faker->optional()->postcode(),
            'willing_to_relocate' => $this->faker->boolean(),
            'preferred_locations' => $this->faker->optional()->randomElements([
                'New York, NY', 'San Francisco, CA', 'Austin, TX', 'Seattle, WA', 'Boston, MA'
            ], $this->faker->numberBetween(1, 3)),
            'work_authorization' => $this->faker->randomElement(['citizen', 'permanent_resident', 'work_visa', 'student_visa', 'other']),
            'languages' => $this->faker->optional()->randomElements([
                'English', 'Spanish', 'French', 'German', 'Chinese', 'Japanese', 'Korean'
            ], $this->faker->numberBetween(1, 3)),
            'certifications' => $this->faker->optional()->randomElements([
                'AWS Certified Solutions Architect', 'Google Cloud Professional', 'Microsoft Azure', 'PMP', 'Scrum Master'
            ], $this->faker->numberBetween(0, 2)),
            'achievements' => $this->faker->optional()->randomElements([
                'Led team of 10 developers', 'Increased performance by 50%', 'Reduced costs by 30%', 'Mentored 5 junior developers'
            ], $this->faker->numberBetween(0, 2)),
        ];
    }
}
