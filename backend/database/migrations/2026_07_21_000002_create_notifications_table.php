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
        // Tabela notifications — notificações do sistema (necessária para CheckOverdueLoans command - 07-02)
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('type', 255);                              // ex: 'App\Notifications\LoanOverdue'
            $table->string('notifiable_type', 255);                   // ex: 'App\Models\User'
            $table->uuid('notifiable_id');                            // FK lógica — sem constraint porque notifiable pode ser qualquer model
            $table->text('data');                                      // JSON armazenado como string
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            // Índices
            $table->index(['notifiable_type', 'notifiable_id']);      // busca eficiente por destinatário
            $table->index(['type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
