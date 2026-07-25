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
        // Tabela calibrations — calibrações de equipamentos (D-01, D-02, D-03, D-04, D-05)
        Schema::create('calibrations', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('equipment_id')->constrained('equipments');           // D-01: 1:N equipment→calibrations
            $table->string('part_name', 255)->nullable();                             // D-05: optional component/part
            $table->string('status', 20)->default('scheduled');                       // D-03: scheduled|completed|cancelled
            $table->date('scheduled_date');                                           // data planejada
            $table->timestamp('completed_at')->nullable();                            // preenchido na conclusão (D-02)
            $table->timestamp('next_due_at')->nullable();                             // calculado: completed_at + interval (D-02)
            $table->integer('interval_value');                                        // ex: 6, 30, 1000 (D-02)
            $table->string('interval_unit', 10);                                      // months, days, hours (D-02)
            $table->string('responsible', 255)->nullable();                           // responsável pela calibração
            $table->string('laboratory', 255)->nullable();                            // laboratório externo ou interno
            $table->string('certificate_number', 100)->nullable();                    // número do certificado
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users');
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Índices para consultas frequentes
            $table->index(['equipment_id']);
            $table->index(['status']);
            $table->index(['scheduled_date']);
            $table->index(['next_due_at']);
            $table->index(['laboratory']);
            $table->index(['status', 'next_due_at']);          // composite: due-check query (D-11)
            $table->index(['equipment_id', 'status']);          // composite: equipment history filtered (CAL-04)
        });

        // Tabela calibration_certificates — certificados de calibração (D-07, D-08)
        Schema::create('calibration_certificates', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('calibration_id')->constrained('calibrations')->onDelete('cascade');
            $table->string('filename', 255);                                          // nome original do arquivo
            $table->string('filepath', 255);                                          // caminho no storage
            $table->string('mime_type', 50);
            $table->integer('size_bytes');
            $table->string('certificate_number', 100)->nullable();                    // número do certificado
            $table->string('issuer', 255)->nullable();                                // emissor do certificado
            $table->date('issued_at')->nullable();                                    // data de emissão
            $table->date('validity_start')->nullable();
            $table->date('validity_end')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Índices
            $table->index(['calibration_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calibration_certificates');
        Schema::dropIfExists('calibrations');
    }
};
