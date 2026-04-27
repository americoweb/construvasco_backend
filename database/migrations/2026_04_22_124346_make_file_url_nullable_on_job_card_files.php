<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * file_url is no longer stored — the JobCardFileResource generates it
     * dynamically via the authenticated /serve endpoint.
     * Make the column nullable so existing rows are untouched and new
     * uploads don't fail the NOT NULL constraint.
     */
    public function up(): void
    {
        Schema::table('job_card_files', function (Blueprint $table) {
            $table->string('file_url')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('job_card_files', function (Blueprint $table) {
            $table->string('file_url')->nullable(false)->change();
        });
    }
};
