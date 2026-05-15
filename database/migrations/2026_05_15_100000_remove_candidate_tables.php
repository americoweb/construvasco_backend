<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop recruitment (Candidate) domain tables if they exist.
     * Original migrations were never committed to this repository.
     */
    public function up(): void
    {
        Schema::dropIfExists('candidate_skills');
        Schema::dropIfExists('candidate_education');
        Schema::dropIfExists('candidate_experiences');
        Schema::dropIfExists('candidate_profiles');
        Schema::dropIfExists('resumes');
        Schema::dropIfExists('candidates');
        Schema::dropIfExists('skills');
    }

    public function down(): void
    {
        // Irreversible — tables had no migrations in this codebase.
    }
};
