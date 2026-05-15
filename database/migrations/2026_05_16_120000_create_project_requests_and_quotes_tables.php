<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reference_code', 30)->unique();
            $table->string('project_type')->nullable();
            $table->string('tipologia')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('area_m2', 10, 2)->nullable();
            $table->decimal('largura_m', 10, 2)->nullable();
            $table->decimal('comprimento_m', 10, 2)->nullable();
            $table->unsignedTinyInteger('num_pisos')->nullable();
            $table->unsignedTinyInteger('num_quartos')->nullable();
            $table->decimal('orcamento_estimado_mt', 14, 2)->nullable();
            $table->date('prazo_desejado')->nullable();
            $table->string('localizacao')->nullable();
            $table->string('estilo_arquitectonico')->nullable();
            $table->string('paleta_acabamento')->nullable();
            $table->string('zona_prioritaria')->nullable();
            $table->string('whatsapp', 30)->nullable();
            $table->text('observacoes')->nullable();
            $table->json('reference_files')->nullable();
            $table->unsignedBigInteger('approved_ai_generation_id')->nullable();
            $table->string('status', 40)->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('converted_project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'status']);
        });

        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->decimal('total_amount_mt', 14, 2);
            $table->json('breakdown')->nullable();
            $table->unsignedInteger('delivery_days')->nullable();
            $table->text('conditions')->nullable();
            $table->string('status', 30)->default('draft');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('project_template_id')->nullable()->constrained('project_templates')->nullOnDelete();
            $table->timestamps();
            $table->index(['project_request_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotes');
        Schema::dropIfExists('project_requests');
    }
};
