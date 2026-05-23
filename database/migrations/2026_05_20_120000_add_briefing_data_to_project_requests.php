<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('project_requests', 'briefing_data')) {
                $table->json('briefing_data')->nullable()->after('observacoes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('project_requests', function (Blueprint $table) {
            if (Schema::hasColumn('project_requests', 'briefing_data')) {
                $table->dropColumn('briefing_data');
            }
        });
    }
};
