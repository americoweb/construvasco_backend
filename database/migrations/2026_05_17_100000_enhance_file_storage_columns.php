<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_documents', function (Blueprint $table) {
            if (!Schema::hasColumn('project_documents', 'project_request_id')) {
                $table->foreignId('project_request_id')->nullable()->after('project_id')
                    ->constrained('project_requests')->cascadeOnDelete();
            }
            if (!Schema::hasColumn('project_documents', 'disk')) {
                $table->string('disk', 30)->default('public')->after('file_path');
            }
            if (!Schema::hasColumn('project_documents', 'original_name')) {
                $table->string('original_name')->nullable()->after('file_name');
            }
        });

        Schema::table('project_deliverables', function (Blueprint $table) {
            if (!Schema::hasColumn('project_deliverables', 'file_disk')) {
                $table->string('file_disk', 30)->default('local')->after('file_path');
            }
            if (!Schema::hasColumn('project_deliverables', 'mime_type')) {
                $table->string('mime_type', 100)->nullable()->after('file_format');
            }
            if (!Schema::hasColumn('project_deliverables', 'size_bytes')) {
                $table->unsignedBigInteger('size_bytes')->nullable()->after('mime_type');
            }
            if (!Schema::hasColumn('project_deliverables', 'original_name')) {
                $table->string('original_name')->nullable()->after('size_bytes');
            }
            if (!Schema::hasColumn('project_deliverables', 'project_milestone_id')) {
                $table->foreignId('project_milestone_id')->nullable()->after('project_id')
                    ->constrained('project_milestones')->nullOnDelete();
            }
        });

        Schema::table('project_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('project_payments', 'provider_reference')) {
                $table->string('provider_reference')->nullable()->after('reference');
            }
            if (!Schema::hasColumn('project_payments', 'phone_number')) {
                $table->string('phone_number', 30)->nullable()->after('provider_reference');
            }
        });
    }

    public function down(): void
    {
        Schema::table('project_payments', function (Blueprint $table) {
            foreach (['provider_reference', 'phone_number'] as $col) {
                if (Schema::hasColumn('project_payments', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('project_deliverables', function (Blueprint $table) {
            if (Schema::hasColumn('project_deliverables', 'project_milestone_id')) {
                $table->dropForeign(['project_milestone_id']);
                $table->dropColumn('project_milestone_id');
            }
            foreach (['file_disk', 'mime_type', 'size_bytes', 'original_name'] as $col) {
                if (Schema::hasColumn('project_deliverables', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('project_documents', function (Blueprint $table) {
            if (Schema::hasColumn('project_documents', 'project_request_id')) {
                $table->dropForeign(['project_request_id']);
                $table->dropColumn('project_request_id');
            }
            foreach (['disk', 'original_name'] as $col) {
                if (Schema::hasColumn('project_documents', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
