<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, modify the ENUM column to include 'whatsapp' (keeping 'phone' temporarily)
        // This allows us to update the data
        DB::statement("ALTER TABLE `users` MODIFY COLUMN `type` ENUM('email', 'phone', 'whatsapp') NOT NULL");
        DB::statement("ALTER TABLE `tenant_invitations` MODIFY COLUMN `type` ENUM('email', 'phone', 'whatsapp') NOT NULL");

        // Now update existing 'phone' values to 'whatsapp'
        DB::table('users')
            ->where('type', 'phone')
            ->update(['type' => 'whatsapp']);

        DB::table('tenant_invitations')
            ->where('type', 'phone')
            ->update(['type' => 'whatsapp']);

        // Finally, remove 'phone' from the ENUM, keeping only 'email' and 'whatsapp'
        DB::statement("ALTER TABLE `users` MODIFY COLUMN `type` ENUM('email', 'whatsapp') NOT NULL");
        DB::statement("ALTER TABLE `tenant_invitations` MODIFY COLUMN `type` ENUM('email', 'whatsapp') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First, add 'phone' back to the ENUM
        DB::statement("ALTER TABLE `users` MODIFY COLUMN `type` ENUM('email', 'phone', 'whatsapp') NOT NULL");
        DB::statement("ALTER TABLE `tenant_invitations` MODIFY COLUMN `type` ENUM('email', 'phone', 'whatsapp') NOT NULL");

        // Update existing 'whatsapp' values back to 'phone'
        DB::table('users')
            ->where('type', 'whatsapp')
            ->update(['type' => 'phone']);

        DB::table('tenant_invitations')
            ->where('type', 'whatsapp')
            ->update(['type' => 'phone']);

        // Finally, remove 'whatsapp' from the ENUM, keeping only 'email' and 'phone'
        DB::statement("ALTER TABLE `users` MODIFY COLUMN `type` ENUM('email', 'phone') NOT NULL");
        DB::statement("ALTER TABLE `tenant_invitations` MODIFY COLUMN `type` ENUM('email', 'phone') NOT NULL");
    }
};
