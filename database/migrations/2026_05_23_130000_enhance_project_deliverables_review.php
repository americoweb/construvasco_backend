<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_deliverables', function (Blueprint $table) {
            if (! Schema::hasColumn('project_deliverables', 'description')) {
                $table->text('description')->nullable()->after('title');
            }
            if (! Schema::hasColumn('project_deliverables', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('uploaded_by')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('project_deliverables', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
            if (! Schema::hasColumn('project_deliverables', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('approved_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('project_deliverables', function (Blueprint $table) {
            if (Schema::hasColumn('project_deliverables', 'rejection_reason')) {
                $table->dropColumn('rejection_reason');
            }
            if (Schema::hasColumn('project_deliverables', 'approved_at')) {
                $table->dropColumn('approved_at');
            }
            if (Schema::hasColumn('project_deliverables', 'approved_by')) {
                $table->dropConstrainedForeignId('approved_by');
            }
            if (Schema::hasColumn('project_deliverables', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
};
