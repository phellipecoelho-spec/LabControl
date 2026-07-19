<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar_path', 255)->nullable()->after('remember_token');
            $table->string('phone', 20)->nullable()->after('avatar_path');
            $table->string('position', 100)->nullable()->after('phone');
            $table->string('department', 100)->nullable()->after('position');
            $table->string('signature', 255)->nullable()->after('department');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['avatar_path', 'phone', 'position', 'department', 'signature']);
        });
    }
};
