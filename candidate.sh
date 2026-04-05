#!/bin/bash

# iHRM Candidate Module - Database Migrations Setup Script
# Run this from the Laravel root directory (where vendor folder exists)

echo "🗄️ Setting up iHRM Candidate Module Database Migrations..."

# Check if we're in the correct directory
if [ ! -d "vendor" ]; then
    echo "❌ Error: Please run this script from the Laravel root directory (where vendor folder exists)"
    exit 1
fi

# Create directories if they don't exist
mkdir -p database/migrations
mkdir -p database/factories
mkdir -p database/seeders

# Generate sequential timestamps starting from current time
# This ensures migrations run after existing ones
CURRENT_TIME=$(date +%s)
BASE_TIMESTAMP=$(date -d "@$CURRENT_TIME" +%Y_%m_%d_%H%M%S)

# Calculate incremental timestamps for each migration
TIMESTAMP_1=$(date -d "@$((CURRENT_TIME + 1))" +%Y_%m_%d_%H%M%S)
TIMESTAMP_2=$(date -d "@$((CURRENT_TIME + 2))" +%Y_%m_%d_%H%M%S)
TIMESTAMP_3=$(date -d "@$((CURRENT_TIME + 3))" +%Y_%m_%d_%H%M%S)
TIMESTAMP_4=$(date -d "@$((CURRENT_TIME + 4))" +%Y_%m_%d_%H%M%S)
TIMESTAMP_5=$(date -d "@$((CURRENT_TIME + 5))" +%Y_%m_%d_%H%M%S)
TIMESTAMP_6=$(date -d "@$((CURRENT_TIME + 6))" +%Y_%m_%d_%H%M%S)
TIMESTAMP_7=$(date -d "@$((CURRENT_TIME + 7))" +%Y_%m_%d_%H%M%S)
TIMESTAMP_8=$(date -d "@$((CURRENT_TIME + 8))" +%Y_%m_%d_%H%M%S)

echo "📊 Creating migration files with sequential timestamps..."

# 1. Create candidates table migration
cat > database/migrations/${TIMESTAMP_1}_create_candidates_table.php << 'EOF'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('tenant_id');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('status')->default('active');
            $table->string('source')->nullable();
            $table->text('notes')->nullable();
            $table->string('experience_level')->nullable();
            $table->string('location')->nullable();
            $table->date('availability')->nullable();
            $table->decimal('salary_expectation', 10, 2)->nullable();
            $table->string('preferred_work_type')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'email']);
            $table->unique(['tenant_id', 'email']);
            
            // Foreign key constraint (uncomment when you have a tenants table)
            // $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};
EOF

# 2. Create candidate_profiles table migration
cat > database/migrations/${TIMESTAMP_2}_create_candidate_profiles_table.php << 'EOF'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('candidate_id');
            $table->unsignedBigInteger('tenant_id');
            $table->text('summary')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('github_url')->nullable();
            $table->string('portfolio_url')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('postal_code')->nullable();
            $table->boolean('willing_to_relocate')->default(false);
            $table->json('preferred_locations')->nullable();
            $table->string('work_authorization')->nullable();
            $table->json('languages')->nullable();
            $table->json('certifications')->nullable();
            $table->json('achievements')->nullable();
            $table->timestamps();

            $table->unique(['candidate_id']);
            $table->index(['tenant_id']);
            
            $table->foreign('candidate_id')->references('id')->on('candidates')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_profiles');
    }
};
EOF

# 3. Create skills table migration
cat > database/migrations/${TIMESTAMP_3}_create_skills_table.php << 'EOF'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('category')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['category', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skills');
    }
};
EOF

# 4. Create candidate_skills pivot table migration
cat > database/migrations/${TIMESTAMP_4}_create_candidate_skills_table.php << 'EOF'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_skills', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('candidate_id');
            $table->unsignedBigInteger('skill_id');
            $table->string('level')->default('intermediate'); // beginner, intermediate, advanced, expert
            $table->integer('years_experience')->default(0);
            $table->boolean('verified')->default(false);
            $table->string('source')->nullable(); // resume, manual, assessment, etc.
            $table->timestamps();

            $table->unique(['candidate_id', 'skill_id']);
            $table->index(['candidate_id', 'level']);
            $table->index(['skill_id', 'level']);
            
            $table->foreign('candidate_id')->references('id')->on('candidates')->onDelete('cascade');
            $table->foreign('skill_id')->references('id')->on('skills')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_skills');
    }
};
EOF

# 5. Create candidate_experiences table migration
cat > database/migrations/${TIMESTAMP_5}_create_candidate_experiences_table.php << 'EOF'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_experiences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('candidate_id');
            $table->unsignedBigInteger('tenant_id');
            $table->string('company_name');
            $table->string('job_title');
            $table->text('description')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('is_current')->default(false);
            $table->string('location')->nullable();
            $table->json('achievements')->nullable();
            $table->json('technologies_used')->nullable();
            $table->timestamps();

            $table->index(['candidate_id', 'start_date']);
            $table->index(['tenant_id']);
            $table->index(['company_name']);
            
            $table->foreign('candidate_id')->references('id')->on('candidates')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_experiences');
    }
};
EOF

# 6. Create candidate_educations table migration
cat > database/migrations/${TIMESTAMP_6}_create_candidate_educations_table.php << 'EOF'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_educations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('candidate_id');
            $table->unsignedBigInteger('tenant_id');
            $table->string('institution_name');
            $table->string('degree_type');
            $table->string('field_of_study');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->decimal('gpa', 3, 2)->nullable();
            $table->string('honors')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['candidate_id', 'start_date']);
            $table->index(['tenant_id']);
            $table->index(['institution_name']);
            $table->index(['degree_type', 'field_of_study']);
            
            $table->foreign('candidate_id')->references('id')->on('candidates')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_educations');
    }
};
EOF

# 7. Create resumes table migration
cat > database/migrations/${TIMESTAMP_7}_create_resumes_table.php << 'EOF'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resumes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('candidate_id');
            $table->unsignedBigInteger('tenant_id');
            $table->string('original_filename');
            $table->string('file_path');
            $table->unsignedBigInteger('file_size');
            $table->string('mime_type');
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->json('parsed_data')->nullable();
            $table->decimal('confidence_score', 5, 2)->nullable();
            $table->json('parsing_errors')->nullable();
            $table->timestamp('reparsed_at')->nullable();
            $table->timestamps();

            $table->index(['candidate_id', 'status']);
            $table->index(['tenant_id']);
            $table->index(['status']);
            
            $table->foreign('candidate_id')->references('id')->on('candidates')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resumes');
    }
};
EOF

# 8. Create candidate_job_matches table migration
cat > database/migrations/${TIMESTAMP_8}_create_candidate_job_matches_table.php << 'EOF'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_job_matches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('candidate_id');
            $table->unsignedBigInteger('job_id'); // This will reference jobs table when created
            $table->unsignedBigInteger('tenant_id');
            $table->decimal('match_score', 5, 2); // 0.00 to 100.00
            $table->json('match_details')->nullable(); // Explanation of match factors
            $table->string('status')->default('potential'); // potential, interested, applied, rejected
            $table->text('notes')->nullable();
            $table->timestamp('matched_at');
            $table->timestamps();

            $table->unique(['candidate_id', 'job_id']);
            $table->index(['candidate_id', 'match_score']);
            $table->index(['job_id', 'match_score']);
            $table->index(['tenant_id', 'status']);
            $table->index(['match_score']);
            
            $table->foreign('candidate_id')->references('id')->on('candidates')->onDelete('cascade');
            // $table->foreign('job_id')->references('id')->on('jobs')->onDelete('cascade'); // Uncomment when jobs table exists
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_job_matches');
    }
};
EOF

echo "🏭 Creating factory files..."

# Create CandidateFactory
cat > database/factories/CandidateFactory.php << 'EOF'
<?php

namespace Database\Factories;

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
EOF

# Create CandidateProfileFactory
cat > database/factories/CandidateProfileFactory.php << 'EOF'
<?php

namespace Database\Factories;

use App\Models\Candidate\CandidateProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

class CandidateProfileFactory extends Factory
{
    protected $model = CandidateProfile::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'summary' => $this->faker->paragraphs(3, true),
            'linkedin_url' => $this->faker->optional()->url(),
            'github_url' => $this->faker->optional()->url(),
            'portfolio_url' => $this->faker->optional()->url(),
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'country' => $this->faker->country(),
            'postal_code' => $this->faker->postcode(),
            'willing_to_relocate' => $this->faker->boolean(30),
            'preferred_locations' => $this->faker->optional()->randomElements([
                'New York, NY', 'San Francisco, CA', 'Austin, TX', 'Seattle, WA', 'Remote'
            ], $this->faker->numberBetween(1, 3)),
            'work_authorization' => $this->faker->randomElement([
                'US Citizen', 'Green Card', 'H1B', 'OPT', 'Requires Sponsorship'
            ]),
            'languages' => [
                ['language' => 'English', 'proficiency' => 'Native'],
                ['language' => $this->faker->randomElement(['Spanish', 'French', 'German', 'Mandarin']), 'proficiency' => $this->faker->randomElement(['Basic', 'Intermediate', 'Advanced'])]
            ],
            'certifications' => $this->faker->optional()->randomElements([
                'AWS Certified Solutions Architect',
                'PMP Certification',
                'Scrum Master Certification',
                'Google Cloud Professional',
                'Microsoft Azure Fundamentals'
            ], $this->faker->numberBetween(0, 3)),
            'achievements' => $this->faker->optional()->randomElements([
                'Led team of 10+ developers',
                'Increased system performance by 40%',
                'Published research paper',
                'Speaker at tech conferences',
                'Open source contributor'
            ], $this->faker->numberBetween(0, 3)),
        ];
    }
}
EOF

# Create ResumeFactory
cat > database/factories/ResumeFactory.php << 'EOF'
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
EOF

echo "🌱 Creating seeder files..."

# Create SkillsSeeder
cat > database/seeders/SkillsSeeder.php << 'EOF'
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SkillsSeeder extends Seeder
{
    public function run(): void
    {
        $skills = [
            // Programming Languages
            ['name' => 'JavaScript', 'category' => 'Programming Languages'],
            ['name' => 'Python', 'category' => 'Programming Languages'],
            ['name' => 'Java', 'category' => 'Programming Languages'],
            ['name' => 'PHP', 'category' => 'Programming Languages'],
            ['name' => 'C#', 'category' => 'Programming Languages'],
            ['name' => 'TypeScript', 'category' => 'Programming Languages'],
            ['name' => 'Go', 'category' => 'Programming Languages'],
            ['name' => 'Rust', 'category' => 'Programming Languages'],
            ['name' => 'Ruby', 'category' => 'Programming Languages'],
            ['name' => 'Swift', 'category' => 'Programming Languages'],

            // Frontend Frameworks
            ['name' => 'React', 'category' => 'Frontend Frameworks'],
            ['name' => 'Angular', 'category' => 'Frontend Frameworks'],
            ['name' => 'Vue.js', 'category' => 'Frontend Frameworks'],
            ['name' => 'Svelte', 'category' => 'Frontend Frameworks'],
            ['name' => 'Next.js', 'category' => 'Frontend Frameworks'],

            // Backend Frameworks
            ['name' => 'Laravel', 'category' => 'Backend Frameworks'],
            ['name' => 'Django', 'category' => 'Backend Frameworks'],
            ['name' => 'Spring Boot', 'category' => 'Backend Frameworks'],
            ['name' => 'Express.js', 'category' => 'Backend Frameworks'],
            ['name' => 'ASP.NET', 'category' => 'Backend Frameworks'],

            // Databases
            ['name' => 'MySQL', 'category' => 'Databases'],
            ['name' => 'PostgreSQL', 'category' => 'Databases'],
            ['name' => 'MongoDB', 'category' => 'Databases'],
            ['name' => 'Redis', 'category' => 'Databases'],
            ['name' => 'SQLite', 'category' => 'Databases'],
            ['name' => 'Oracle', 'category' => 'Databases'],

            // Cloud Platforms
            ['name' => 'AWS', 'category' => 'Cloud Platforms'],
            ['name' => 'Google Cloud', 'category' => 'Cloud Platforms'],
            ['name' => 'Microsoft Azure', 'category' => 'Cloud Platforms'],
            ['name' => 'DigitalOcean', 'category' => 'Cloud Platforms'],

            // DevOps & Tools
            ['name' => 'Docker', 'category' => 'DevOps & Tools'],
            ['name' => 'Kubernetes', 'category' => 'DevOps & Tools'],
            ['name' => 'Jenkins', 'category' => 'DevOps & Tools'],
            ['name' => 'Git', 'category' => 'DevOps & Tools'],
            ['name' => 'Terraform', 'category' => 'DevOps & Tools'],

            // Soft Skills
            ['name' => 'Leadership', 'category' => 'Soft Skills'],
            ['name' => 'Communication', 'category' => 'Soft Skills'],
            ['name' => 'Problem Solving', 'category' => 'Soft Skills'],
            ['name' => 'Team Collaboration', 'category' => 'Soft Skills'],
            ['name' => 'Project Management', 'category' => 'Soft Skills'],
            ['name' => 'Agile/Scrum', 'category' => 'Methodologies'],
        ];

        foreach ($skills as $skill) {
            $skill['is_active'] = true;
            $skill['created_at'] = now();
            $skill['updated_at'] = now();
        }

        DB::table('skills')->insert($skills);
    }
}
EOF

# Create CandidateSeeder
cat > database/seeders/CandidateSeeder.php << 'EOF'
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Candidate\Candidate;
use App\Models\Candidate\CandidateProfile;
use App\Models\Candidate\Resume;

class CandidateSeeder extends Seeder
{
    public function run(): void
    {
        // Create 50 candidates with profiles and resumes
        Candidate::factory()
            ->count(50)
            ->has(CandidateProfile::factory(), 'profile')
            ->has(Resume::factory()->completed(), 'resumes')
            ->create()
            ->each(function ($candidate) {
                // Attach random skills to each candidate
                $skillIds = \DB::table('skills')->pluck('id')->random(rand(3, 8));
                
                foreach ($skillIds as $skillId) {
                    $candidate->skills()->attach($skillId, [
                        'level' => fake()->randomElement(['beginner', 'intermediate', 'advanced', 'expert']),
                        'years_experience' => fake()->numberBetween(1, 10),
                        'verified' => fake()->boolean(70),
                        'source' => 'resume',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
    }
}
EOF

# Create main database seeder file
cat > database/seeders/CandidateModuleSeeder.php << 'EOF'
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CandidateModuleSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SkillsSeeder::class,
            CandidateSeeder::class,
        ]);
    }
}
EOF

echo ""
echo "✅ iHRM Candidate Module database setup completed successfully!"
echo ""
echo "📊 Created:"
echo "   - 8 Migration files with complete schema"
echo "   - 3 Factory files for testing data"
echo "   - 3 Seeder files with realistic data"
echo ""
echo "🚀 Next steps:"
echo "   1. Run migrations:"
echo "      php artisan migrate"
echo ""
echo "   2. Seed the database with test data:"
echo "      php artisan db:seed --class=SkillsSeeder"
echo "      php artisan db:seed --class=CandidateSeeder"
echo ""
echo "   3. Or run all candidate seeders at once:"
echo "      php artisan db:seed --class=CandidateModuleSeeder"
echo ""
echo "📋 Database Tables Created:"
echo "   - candidates (main candidate table)"
echo "   - candidate_profiles (extended profile info)"
echo "   - skills (master skills table)"
echo "   - candidate_skills (candidate-skill relationships)"
echo "   - candidate_experiences (work history)"
echo "   - candidate_educations (education background)"
echo "   - resumes (resume files and parsed data)"
echo "   - candidate_job_matches (AI matching results)"
echo ""
echo "🎉 Database ready for iHRM Candidate Module!"