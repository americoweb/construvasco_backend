<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_payments', function (Blueprint $table) {
            $table->string('phase', 30)->nullable()->after('type');
            $table->string('proof_path')->nullable()->after('metadata');
            $table->timestamp('proof_uploaded_at')->nullable()->after('proof_path');
            $table->foreignId('confirmed_by')->nullable()->after('proof_uploaded_at')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable()->after('confirmed_by');
            $table->text('rejected_reason')->nullable()->after('confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::table('project_payments', function (Blueprint $table) {
            $table->dropForeign(['confirmed_by']);
            $table->dropColumn([
                'phase',
                'proof_path',
                'proof_uploaded_at',
                'confirmed_by',
                'confirmed_at',
                'rejected_reason',
            ]);
        });
    }
};
