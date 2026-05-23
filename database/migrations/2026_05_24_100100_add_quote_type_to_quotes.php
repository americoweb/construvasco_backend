<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->string('quote_type', 30)->default('architecture')->after('project_request_id');
            $table->index(['project_request_id', 'quote_type', 'status']);
        });

        DB::table('quotes')->whereNull('quote_type')->update(['quote_type' => 'architecture']);
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropIndex(['project_request_id', 'quote_type', 'status']);
            $table->dropColumn('quote_type');
        });
    }
};
