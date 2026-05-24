<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('project_payments', 'notes')) {
                $table->text('notes')->nullable()->after('metadata');
            }
        });
    }

    public function down(): void
    {
        Schema::table('project_payments', function (Blueprint $table) {
            if (Schema::hasColumn('project_payments', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }
};
