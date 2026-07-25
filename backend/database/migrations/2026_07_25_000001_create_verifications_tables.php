<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tabela verification_templates — templates de parâmetros por categoria de equipamento (D-01, D-02)
        Schema::create('verification_templates', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('equipment_category_id')->constrained('categories');
            $table->string('parameter_name', 255);
            $table->string('parameter_unit', 50)->nullable();
            $table->decimal('tolerance_min', 16, 6)->nullable();
            $table->decimal('tolerance_max', 16, 6)->nullable();
            $table->integer('sort_order')->default(0);
            $table->foreignUuid('created_by')->nullable()->constrained('users');
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Índices para consultas frequentes
            $table->index(['equipment_category_id']);
            $table->index(['equipment_category_id', 'sort_order']);
        });

        // Tabela verifications — registros de aferição (D-03)
        Schema::create('verifications', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('equipment_id')->constrained('equipments');
            $table->timestamp('verified_at')->useCurrent();
            $table->foreignUuid('operator_id')->constrained('users');
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users');
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Índices para consultas frequentes
            $table->index(['equipment_id']);
            $table->index(['equipment_id', 'verified_at']);
            $table->index(['verified_at']);
        });

        // Tabela verification_params — parâmetros aferidos em cada verificação (D-04)
        Schema::create('verification_params', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('verification_id')->constrained('verifications')->onDelete('cascade');
            $table->foreignUuid('template_id')->constrained('verification_templates');
            $table->decimal('value', 16, 6)->nullable();
            $table->string('result', 20)->default('not_measured'); // within_range, outside_range, not_measured
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users');
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Índices para consultas frequentes
            $table->index(['verification_id']);
            $table->index(['template_id']);
            $table->index(['result']);
        });

        // Adicionar coluna verification_frequency à tabela equipments (D-06, D-07)
        Schema::table('equipments', function (Blueprint $table) {
            $table->string('verification_frequency', 10)->nullable(); // daily, weekly, shift
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verification_params');
        Schema::dropIfExists('verifications');
        Schema::dropIfExists('verification_templates');

        Schema::table('equipments', function (Blueprint $table) {
            $table->dropColumn('verification_frequency');
        });
    }
};
