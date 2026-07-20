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
        // Tabela categories
        Schema::create('categories', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('name', 255);
            $table->string('slug', 255)->unique();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Tabela manufacturers
        Schema::create('manufacturers', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('name', 255);
            $table->string('country', 100)->nullable();
            $table->string('website', 255)->nullable();
            $table->string('logo_path', 255)->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Tabela suppliers
        Schema::create('suppliers', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('name', 255);
            $table->string('cnpj', 18)->nullable()->unique();
            $table->string('contact_name', 255)->nullable();
            $table->string('contact_email', 255)->nullable();
            $table->string('contact_phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Tabela equipments
        Schema::create('equipments', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('name', 255);
            $table->string('patrimony_id', 50)->nullable();
            $table->string('serial_number', 100)->nullable();
            $table->foreignUuid('category_id')->nullable()->constrained('categories');
            $table->foreignUuid('manufacturer_id')->nullable()->constrained('manufacturers');
            $table->foreignUuid('supplier_id')->nullable()->constrained('suppliers');
            $table->string('location', 255)->nullable();
            $table->date('acquisition_date')->nullable();
            $table->date('warranty_end')->nullable();
            $table->string('status', 20)->default('active');
            $table->text('description')->nullable();
            $table->text('technical_specs')->nullable();
            $table->text('notes')->nullable();
            $table->foreignUuid('user_id')->constrained('users');
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Índices para campos de filtro
            $table->index(['status']);
            $table->index(['category_id']);
            $table->index(['manufacturer_id']);
            $table->index(['supplier_id']);
            $table->index(['user_id']);
        });

        // Tabela equipment_photos
        Schema::create('equipment_photos', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('equipment_id')->constrained('equipments')->onDelete('cascade');
            $table->string('path', 255);
            $table->string('original_name', 255);
            $table->integer('size');
            $table->string('mime_type', 50);
            $table->integer('sort_order')->default(0);
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            // Índices
            $table->index(['equipment_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment_photos');
        Schema::dropIfExists('equipments');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('manufacturers');
        Schema::dropIfExists('categories');
    }
};