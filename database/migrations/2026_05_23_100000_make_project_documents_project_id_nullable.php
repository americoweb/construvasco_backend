<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('project_documents', 'project_id')) {
            return;
        }

        Schema::table('project_documents', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
        });

        Schema::table('project_documents', function (Blueprint $table) {
            $table->unsignedBigInteger('project_id')->nullable()->change();
            $table->foreign('project_id')->references('id')->on('projects')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('project_documents', 'project_id')) {
            return;
        }

        Schema::table('project_documents', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
        });

        Schema::table('project_documents', function (Blueprint $table) {
            $table->unsignedBigInteger('project_id')->nullable(false)->change();
            $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
        });
    }
};
