<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_card_files', function (Blueprint $table) {
            $table->string('disk')->default('local')->after('notes');
            $table->string('disk_path')->after('disk');
            $table->string('drive_file_id')->nullable()->after('disk_path');
            $table->string('drive_link')->nullable()->after('drive_file_id');
            $table->string('drive_download_link')->nullable()->after('drive_link');
            $table->timestamp('drive_synced_at')->nullable()->after('drive_download_link');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('job_card_files', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn([
                'disk',
                'disk_path',
                'drive_file_id',
                'drive_link',
                'drive_download_link',
                'drive_synced_at',
            ]);
        });
    }
};
