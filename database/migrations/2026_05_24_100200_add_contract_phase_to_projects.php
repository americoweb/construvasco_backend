<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('contract_phase', 40)->default('architecture')->after('status');
            $table->index('contract_phase');
        });

        DB::table('projects')->update(['contract_phase' => 'architecture']);
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['contract_phase']);
            $table->dropColumn('contract_phase');
        });
    }
};
