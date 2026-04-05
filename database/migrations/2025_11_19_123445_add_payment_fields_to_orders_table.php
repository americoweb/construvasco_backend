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
        // Add columns only if they don't exist (handles partial migration scenarios)
        try {
            Schema::table('orders', function (Blueprint $table) {
                if (!Schema::hasColumn('orders', 'payment_method')) {
                    $table->string('payment_method', 50)->nullable()->after('payment_status');
                }
            });
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate column') === false) {
                throw $e;
            }
        }
        
        try {
            Schema::table('orders', function (Blueprint $table) {
                if (!Schema::hasColumn('orders', 'payment_reference')) {
                    $table->string('payment_reference', 255)->nullable()->after('payment_method');
                }
            });
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate column') === false) {
                throw $e;
            }
        }
        
        try {
            Schema::table('orders', function (Blueprint $table) {
                if (!Schema::hasColumn('orders', 'payment_transaction_id')) {
                    $table->string('payment_transaction_id', 255)->nullable()->after('payment_reference');
                }
            });
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate column') === false) {
                throw $e;
            }
        }
        
        try {
        Schema::table('orders', function (Blueprint $table) {
                if (!Schema::hasColumn('orders', 'paid_at')) {
                    $table->timestamp('paid_at')->nullable()->after('payment_transaction_id');
                }
            });
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate column') === false) {
                throw $e;
            }
        }
        
        // Use prefix indexes for VARCHAR(255) fields to avoid MySQL key length limit
        // 191 characters is safe for utf8mb4 (191 * 4 = 764 bytes < 1000 bytes limit)
        try {
            DB::statement('CREATE INDEX orders_payment_reference_index ON orders (payment_reference(191))');
        } catch (\Exception $e) {
            // Index might already exist from a previous failed migration attempt
            if (strpos($e->getMessage(), 'Duplicate key name') === false && 
                strpos($e->getMessage(), 'already exists') === false) {
                throw $e;
            }
        }
        
        try {
            DB::statement('CREATE INDEX orders_payment_transaction_id_index ON orders (payment_transaction_id(191))');
        } catch (\Exception $e) {
            // Index might already exist from a previous failed migration attempt
            if (strpos($e->getMessage(), 'Duplicate key name') === false && 
                strpos($e->getMessage(), 'already exists') === false) {
                throw $e;
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_payment_reference_index');
            $table->dropIndex('orders_payment_transaction_id_index');
            $table->dropColumn(['payment_method', 'payment_reference', 'payment_transaction_id', 'paid_at']);
        });
    }
};
