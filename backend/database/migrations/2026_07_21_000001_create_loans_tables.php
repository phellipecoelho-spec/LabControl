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
        // Tabela loans — empréstimos de equipamentos (D-01, D-02, D-03, D-05)
        Schema::create('loans', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('borrower_id')->constrained('users');           // D-02: mutuário é user interno
            $table->string('status', 20)->default('reserved');                  // D-03: reserved → active → returned / cancelled
            $table->timestamp('borrowed_at');                                   // D-05: data do empréstimo
            $table->timestamp('expected_return_at');                            // D-05: data prevista de devolução
            $table->timestamp('returned_at')->nullable();                       // D-04: preenchido quando TODOS itens devolvidos
            $table->text('reason')->nullable();                                 // D-05: motivo do empréstimo
            $table->string('destination', 255)->nullable();                     // D-05: setor/laboratório destino
            $table->string('contact', 255)->nullable();                         // D-05: contato do mutuário
            $table->text('notes')->nullable();                                  // D-05: observações
            $table->foreignUuid('approved_by')->nullable()->constrained('users'); // D-05: quem aprovou
            $table->foreignUuid('created_by')->nullable()->constrained('users');
            $table->foreignUuid('user_id')->constrained('users');               // proprietário do registro
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Índices para consultas frequentes
            $table->index(['borrower_id']);
            $table->index(['status']);
            $table->index(['expected_return_at']);
            $table->index(['status', 'expected_return_at']);       // composite: overdue query
            $table->index(['borrower_id', 'status']);               // composite: empréstimos do usuário por status
        });

        // Tabela pivot equipment_loan — itens do empréstimo (D-01, D-04, D-18, D-19)
        Schema::create('equipment_loan', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('loan_id')->constrained('loans')->onDelete('cascade');
            $table->foreignUuid('equipment_id')->constrained('equipments');
            $table->timestamp('returned_at')->nullable();           // D-19: null no create, preenche na devolução
            $table->text('notes')->nullable();                      // D-18: observações por item na devolução
            $table->timestamps();

            // Evitar duplicatas do mesmo equipamento no mesmo empréstimo
            $table->unique(['loan_id', 'equipment_id']);

            // Índices para consultas
            $table->index(['equipment_id']);
            $table->index(['returned_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment_loan');
        Schema::dropIfExists('loans');
    }
};
