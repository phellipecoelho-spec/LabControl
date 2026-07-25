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
        // Tabela maintenance_orders — ordens de manutenção preventiva e corretiva (D-01, D-02, D-03, D-04)
        Schema::create('maintenance_orders', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('equipment_id')->constrained('equipments');               // D-01: 1:N equipment→maintenance_orders
            $table->string('type', 20);                                                   // preventive|corrective (D-01)
            $table->string('status', 20)->default('open');                                // open|in_progress|completed|cancelled (D-02)
            $table->string('priority', 20)->default('medium');                            // low|medium|high|critical (D-03)
            $table->text('description');                                                  // descrição do problema/serviço
            $table->timestamp('scheduled_date')->nullable();                              // data agendada (opcional)
            $table->uuid('assigned_to')->nullable();                                       // técnico responsável (D-07) — FK added below
            $table->uuid('opened_by')->nullable();                                         // quem abriu a ordem — FK added below
            $table->timestamp('completed_at')->nullable();                                // preenchido na conclusão (D-09)
            $table->text('resolution')->nullable();                                       // parecer técnico (D-09)
            $table->decimal('time_spent', 10, 2)->nullable();                             // horas gastas (D-09)
            $table->decimal('cost', 12, 2)->nullable();                                   // custo total (D-09)
            $table->integer('interval_value')->nullable();                                // ex: 6, 30, 90 (D-04)
            $table->string('interval_unit', 10)->nullable();                              // months|days|hours (D-04)
            $table->timestamp('next_due_at')->nullable();                                 // calculado: completed_at + interval (D-10)
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Índices para consultas frequentes
            $table->index(['equipment_id']);
            $table->index(['status']);
            $table->index(['type']);
            $table->index(['priority']);
            $table->index(['scheduled_date']);
            $table->index(['next_due_at']);
            $table->index(['status', 'next_due_at']);         // composite: due-check query
            $table->index(['equipment_id', 'status']);         // composite: equipment history filtered
        });

        // Tabela maintenance_order_parts — peças/insumos utilizados na manutenção (D-05)
        Schema::create('maintenance_order_parts', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('maintenance_order_id')->constrained('maintenance_orders')->onDelete('cascade');
            $table->foreignUuid('inventory_item_id')->constrained('inventory_items');     // restrict on delete
            $table->decimal('quantity', 12, 4);                                           // quantidade utilizada
            $table->decimal('unit_cost', 12, 2)->nullable();                              // custo unitário no momento do consumo
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            // Índices
            $table->index(['maintenance_order_id']);
            $table->index(['inventory_item_id']);
        });

        // Defer all FK constraints to users to avoid deadlock on concurrent migrate:fresh
        Schema::table('maintenance_orders', function (Blueprint $table) {
            $table->foreign('assigned_to')->references('id')->on('users')->nullOnDelete();
            $table->foreign('opened_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('maintenance_order_parts', function (Blueprint $table) {
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_order_parts');
        Schema::dropIfExists('maintenance_orders');
    }
};
