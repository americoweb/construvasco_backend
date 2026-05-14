<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->string('project_type', 50)->nullable()->after('session_id');
            $table->string('service_type', 100)->nullable()->after('project_type');
            $table->decimal('terrain_area_sqm', 10, 2)->nullable()->after('service_type');
            $table->string('terrain_location')->nullable()->after('terrain_area_sqm');
            $table->string('terrain_type', 100)->nullable()->after('terrain_location');
            $table->decimal('budget_target', 14, 2)->nullable()->after('terrain_type');
            $table->date('desired_deadline')->nullable()->after('budget_target');
            $table->string('style_preferences', 255)->nullable()->after('desired_deadline');
            $table->unsignedTinyInteger('floors_count')->nullable()->after('style_preferences');
            $table->unsignedTinyInteger('rooms_count')->nullable()->after('floors_count');
            $table->json('technical_requirements')->nullable()->after('rooms_count');
            $table->json('briefing_metadata')->nullable()->after('technical_requirements');
            $table->json('briefing_attachments')->nullable()->after('briefing_metadata');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'project_type',
                'service_type',
                'terrain_area_sqm',
                'terrain_location',
                'terrain_type',
                'budget_target',
                'desired_deadline',
                'style_preferences',
                'floors_count',
                'rooms_count',
                'technical_requirements',
                'briefing_metadata',
                'briefing_attachments',
            ]);
        });
    }
};
