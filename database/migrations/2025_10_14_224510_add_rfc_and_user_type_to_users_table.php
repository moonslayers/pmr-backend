<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('rfc', 13)->nullable()->after('id');
            $table->enum('user_type', ['INTERNO', 'EXTERNO'])->default('EXTERNO')->after('rfc');

            // Agregar índice único para RFC
            $table->unique('rfc');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_rfc_unique');
            $table->dropColumn(['user_type', 'rfc']);
        });
    }
};
