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
        // Tabela inventory_categories — categorias próprias do estoque (D-02)
        Schema::create('inventory_categories', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('name', 255);
            $table->string('slug', 255)->unique();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['slug']);
            $table->index(['name']);
        });

        // Tabela inventory_items (D-01, D-03, D-05, D-14, D-15, D-16)
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('name', 255);                                    // D-14 required
            $table->string('code', 100)->nullable()->unique();               // D-15 optional
            $table->text('description')->nullable();                         // D-15 optional
            $table->foreignUuid('category_id')->constrained('inventory_categories');  // D-14 required
            $table->foreignUuid('supplier_id')->constrained('suppliers');    // D-03, D-14 required
            $table->string('unit', 10);                                      // D-16 fixed list
            $table->integer('min_stock')->default(0);                        // D-14 required
            $table->string('batch_lot', 100)->nullable();                    // D-15 optional
            $table->date('expiry_date')->nullable();                         // D-15 optional
            $table->string('physical_location', 255)->nullable();            // D-05 texto
            $table->foreignUuid('user_id')->constrained('users');
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Índices de performance
            $table->index(['category_id']);
            $table->index(['supplier_id']);
            $table->index(['code']);
            $table->index(['name']);
            $table->index(['unit']);
        });

        // Tabela inventory_movements — ledger de movimentações (D-06, D-07, D-08, D-09, D-10)
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('item_id')->constrained('inventory_items')->onDelete('cascade');
            $table->string('type', 20);              // purchase, consumption, adjustment, disposal, return
            $table->integer('quantity');              // sempre positivo; direção definida pelo tipo
            $table->integer('balance_after');          // saldo resultante desnormalizado (D-10)
            $table->text('reason')->nullable();        // motivo — obrigatório para adjustment/disposal
            $table->text('notes')->nullable();          // observações adicionais
            $table->foreignUuid('user_id')->constrained('users');  // responsável (D-08)
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            // Sem softDeletes — movimentações são imutáveis (ledger append-only)

            // Índices para consultas
            $table->index(['item_id', 'created_at']);     // movimentações por item (cronológico)
            $table->index(['type', 'created_at']);         // filtro por tipo + período
            $table->index(['user_id', 'created_at']);      // filtro por responsável
            $table->index(['created_at']);                 // filtro global por período
        });

        // CHECK constraint como safety net para saldo negativo
        DB::statement('ALTER TABLE inventory_movements ADD CONSTRAINT inventory_movements_balance_check CHECK (balance_after >= 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE inventory_movements DROP CONSTRAINT IF EXISTS inventory_movements_balance_check');
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('inventory_categories');
    }
};
