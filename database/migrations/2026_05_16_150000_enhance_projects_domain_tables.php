<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (!Schema::hasColumn('projects', 'project_request_id')) {
                $table->foreignId('project_request_id')->nullable()->after('client_user_id')
                    ->constrained('project_requests')->nullOnDelete();
            }
            if (!Schema::hasColumn('projects', 'quote_id')) {
                $table->foreignId('quote_id')->nullable()->after('project_request_id')
                    ->constrained('quotes')->nullOnDelete();
            }
            if (!Schema::hasColumn('projects', 'project_template_id')) {
                $table->foreignId('project_template_id')->nullable()->after('quote_id')
                    ->constrained('project_templates')->nullOnDelete();
            }
            if (!Schema::hasColumn('projects', 'client_can_download')) {
                $table->boolean('client_can_download')->default(false)->after('current_phase');
            }
            if (!Schema::hasColumn('projects', 'final_payment_status')) {
                $table->string('final_payment_status', 30)->default('pending')->after('client_can_download');
            }
        });

        Schema::table('project_milestones', function (Blueprint $table) {
            if (!Schema::hasColumn('project_milestones', 'project_template_phase_id')) {
                $table->foreignId('project_template_phase_id')->nullable()->after('project_id')
                    ->constrained('project_template_phases')->nullOnDelete();
            }
            if (!Schema::hasColumn('project_milestones', 'order_position')) {
                $table->unsignedInteger('order_position')->default(0)->after('sort_order');
            }
        });

        Schema::table('project_assignments', function (Blueprint $table) {
            if (!Schema::hasColumn('project_assignments', 'assignment_role')) {
                $table->string('assignment_role', 30)->default('collaborator')->after('role');
            }
        });

        Schema::table('project_payments', function (Blueprint $table) {
            if (Schema::hasColumn('project_payments', 'project_id')) {
                $table->unsignedBigInteger('project_id')->nullable()->change();
            }
            if (!Schema::hasColumn('project_payments', 'type')) {
                $table->string('type', 40)->default('project_final')->after('project_id');
            }
            if (!Schema::hasColumn('project_payments', 'credit_package_id')) {
                $table->foreignId('credit_package_id')->nullable()->after('type')
                    ->constrained('credit_packages')->nullOnDelete();
            }
            if (!Schema::hasColumn('project_payments', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('credit_package_id')
                    ->constrained()->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('project_payments', function (Blueprint $table) {
            foreach (['user_id', 'credit_package_id', 'type'] as $col) {
                if (Schema::hasColumn('project_payments', $col)) {
                    if (in_array($col, ['user_id', 'credit_package_id'])) {
                        $table->dropForeign([$col]);
                    }
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('project_assignments', function (Blueprint $table) {
            if (Schema::hasColumn('project_assignments', 'assignment_role')) {
                $table->dropColumn('assignment_role');
            }
        });

        Schema::table('project_milestones', function (Blueprint $table) {
            if (Schema::hasColumn('project_milestones', 'project_template_phase_id')) {
                $table->dropForeign(['project_template_phase_id']);
                $table->dropColumn('project_template_phase_id');
            }
        });

        Schema::table('projects', function (Blueprint $table) {
            foreach (['project_request_id', 'quote_id', 'project_template_id'] as $fk) {
                if (Schema::hasColumn('projects', $fk)) {
                    $table->dropForeign([$fk]);
                    $table->dropColumn($fk);
                }
            }
            foreach (['client_can_download', 'final_payment_status'] as $col) {
                if (Schema::hasColumn('projects', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
