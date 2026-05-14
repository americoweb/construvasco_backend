<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('construction_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_category_id')->nullable()->constrained('service_categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('base_price', 14, 2)->nullable();
            $table->string('currency', 3)->default('MZN');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('projects', function (Blueprint $table) {
            if (!Schema::hasColumn('projects', 'client_user_id')) {
                $table->foreignId('client_user_id')->nullable()->after('tenant_id')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('projects', 'service_category_id')) {
                $table->foreignId('service_category_id')->nullable()->after('client_user_id')->constrained('service_categories')->nullOnDelete();
            }
            if (!Schema::hasColumn('projects', 'construction_service_id')) {
                $table->foreignId('construction_service_id')->nullable()->after('service_category_id')->constrained('construction_services')->nullOnDelete();
            }
            if (!Schema::hasColumn('projects', 'project_type')) {
                $table->string('project_type')->nullable()->after('status');
            }
            if (!Schema::hasColumn('projects', 'location')) {
                $table->string('location')->nullable()->after('project_type');
            }
            if (!Schema::hasColumn('projects', 'target_budget')) {
                $table->decimal('target_budget', 14, 2)->nullable()->after('budget');
            }
            if (!Schema::hasColumn('projects', 'desired_deadline')) {
                $table->date('desired_deadline')->nullable()->after('end_date');
            }
            if (!Schema::hasColumn('projects', 'current_phase')) {
                $table->string('current_phase')->default('new')->after('desired_deadline');
            }
        });

        Schema::create('project_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('terrain_type')->nullable();
            $table->decimal('estimated_area_m2', 10, 2)->nullable();
            $table->unsignedInteger('floors')->nullable();
            $table->unsignedInteger('rooms')->nullable();
            $table->text('style_preferences')->nullable();
            $table->text('technical_needs')->nullable();
            $table->text('constraints')->nullable();
            $table->json('raw_briefing')->nullable();
            $table->timestamps();
        });

        Schema::create('project_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('document_type');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('project_estimates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('source')->default('ai');
            $table->decimal('estimated_cost', 14, 2)->nullable();
            $table->integer('estimated_duration_days')->nullable();
            $table->json('cost_breakdown')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('project_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to')->constrained('users')->cascadeOnDelete();
            $table->string('role')->default('project_manager');
            $table->string('status')->default('active');
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('unassigned_at')->nullable();
            $table->timestamps();
        });

        Schema::create('project_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('due_date')->nullable();
            $table->date('completed_at')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('project_deliverables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('deliverable_type');
            $table->string('title');
            $table->string('file_path');
            $table->string('file_format')->nullable();
            $table->string('version')->default('v1');
            $table->string('status')->default('submitted');
            $table->timestamps();
        });

        Schema::create('project_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('provider')->nullable();
            $table->string('reference')->unique();
            $table->string('transaction_id')->nullable();
            $table->decimal('amount', 14, 2);
            $table->string('currency', 3)->default('MZN');
            $table->string('status')->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('project_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('invoice_number')->unique();
            $table->decimal('amount', 14, 2);
            $table->string('currency', 3)->default('MZN');
            $table->date('issued_at');
            $table->date('due_at')->nullable();
            $table->string('status')->default('issued');
            $table->json('line_items')->nullable();
            $table->timestamps();
        });

        Schema::create('project_portfolio_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('title');
            $table->text('summary')->nullable();
            $table->json('media')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_portfolio_items');
        Schema::dropIfExists('project_invoices');
        Schema::dropIfExists('project_payments');
        Schema::dropIfExists('project_deliverables');
        Schema::dropIfExists('project_milestones');
        Schema::dropIfExists('project_assignments');
        Schema::dropIfExists('project_estimates');
        Schema::dropIfExists('project_documents');
        Schema::dropIfExists('project_requirements');

        Schema::table('projects', function (Blueprint $table) {
            foreach ([
                'client_user_id',
                'service_category_id',
                'construction_service_id',
            ] as $foreignColumn) {
                if (Schema::hasColumn('projects', $foreignColumn)) {
                    $table->dropForeign([$foreignColumn]);
                }
            }

            foreach ([
                'client_user_id',
                'service_category_id',
                'construction_service_id',
                'project_type',
                'location',
                'target_budget',
                'desired_deadline',
                'current_phase',
            ] as $column) {
                if (Schema::hasColumn('projects', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('construction_services');
        Schema::dropIfExists('service_categories');
    }
};
