<?php

/**
 * One-off repair for partial constru_db — creates missing Spatie pivot + project_documents.
 * Safe to re-run (checks hasTable/hasColumn).
 */
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

$created = [];

if (! Schema::hasTable('model_has_permissions')) {
    Schema::create('model_has_permissions', function (Blueprint $table) {
        $table->unsignedBigInteger('permission_id');
        $table->string('model_type');
        $table->unsignedBigInteger('model_id');
        $table->unsignedBigInteger('tenant_id');
        $table->index(['model_id', 'model_type']);
        $table->index('tenant_id');
        $table->foreign('permission_id')->references('id')->on('permissions')->cascadeOnDelete();
        $table->primary(['tenant_id', 'permission_id', 'model_id', 'model_type'], 'model_has_permissions_permission_model_type_primary');
    });
    $created[] = 'model_has_permissions';
}

if (! Schema::hasTable('model_has_roles')) {
    Schema::create('model_has_roles', function (Blueprint $table) {
        $table->unsignedBigInteger('role_id');
        $table->string('model_type');
        $table->unsignedBigInteger('model_id');
        $table->unsignedBigInteger('tenant_id');
        $table->index(['model_id', 'model_type']);
        $table->index('tenant_id');
        $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
        $table->primary(['tenant_id', 'role_id', 'model_id', 'model_type'], 'model_has_roles_role_model_type_primary');
    });
    $created[] = 'model_has_roles';
}

if (! Schema::hasTable('role_has_permissions')) {
    Schema::create('role_has_permissions', function (Blueprint $table) {
        $table->unsignedBigInteger('permission_id');
        $table->unsignedBigInteger('role_id');
        $table->foreign('permission_id')->references('id')->on('permissions')->cascadeOnDelete();
        $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
        $table->primary(['permission_id', 'role_id']);
    });
    $created[] = 'role_has_permissions';
}

if (! Schema::hasTable('project_documents')) {
    Schema::create('project_documents', function (Blueprint $table) {
        $table->id();
        $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
        $table->foreignId('project_request_id')->nullable()->constrained('project_requests')->cascadeOnDelete();
        $table->string('document_type');
        $table->string('file_name');
        $table->string('original_name')->nullable();
        $table->string('file_path');
        $table->string('disk', 30)->default('public');
        $table->string('mime_type')->nullable();
        $table->unsignedBigInteger('size_bytes')->nullable();
        $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
        $table->timestamps();
    });
    $created[] = 'project_documents';
}

if (! Schema::hasTable('project_assignments')) {
    Schema::create('project_assignments', function (Blueprint $table) {
        $table->id();
        $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
        $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
        $table->foreignId('assigned_to')->constrained('users')->cascadeOnDelete();
        $table->string('assignment_role')->default('main');
        $table->string('status')->default('active');
        $table->timestamp('assigned_at')->nullable();
        $table->timestamp('unassigned_at')->nullable();
        $table->timestamps();
    });
    $created[] = 'project_assignments';
}

if (! Schema::hasTable('project_milestones')) {
    Schema::create('project_milestones', function (Blueprint $table) {
        $table->id();
        $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
        $table->string('title');
        $table->text('description')->nullable();
        $table->date('due_date')->nullable();
        $table->date('completed_at')->nullable();
        $table->string('status')->default('pending');
        $table->unsignedInteger('order_position')->default(0);
        $table->timestamps();
    });
    $created[] = 'project_milestones';
}

if (! Schema::hasTable('project_deliverables')) {
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
    $created[] = 'project_deliverables';
}

echo 'Created tables: ' . (count($created) ? implode(', ', $created) : 'none (already present)') . PHP_EOL;
