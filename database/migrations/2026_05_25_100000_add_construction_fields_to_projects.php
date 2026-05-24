<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (! Schema::hasColumn('projects', 'construction_completed_at')) {
                $table->timestamp('construction_completed_at')->nullable()->after('architecture_completed_at');
            }
            if (! Schema::hasColumn('projects', 'construction_request_notes')) {
                $table->text('construction_request_notes')->nullable()->after('suggested_site_visit_date');
            }
            if (! Schema::hasColumn('projects', 'construction_quote_requested_at')) {
                $table->timestamp('construction_quote_requested_at')->nullable()->after('construction_request_notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            foreach (['construction_completed_at', 'construction_request_notes', 'construction_quote_requested_at'] as $col) {
                if (Schema::hasColumn('projects', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
