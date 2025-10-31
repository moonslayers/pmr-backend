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
        Schema::create('propuestas', function (Blueprint $table) {
            $table->id();
            $table->text('nombre');
            $table->enum('tipo', ['Tramite', 'Servicio'])->default('Tramite');
            $table->text('descripcion');
            $table->string('tiempo_actual_realizacion', 255)->nullable();
            $table->string('tiempo_esperado_realizacion', 255)->nullable();
            $table->string('cantidad_actual_requisitos', 255)->nullable();
            $table->string('cantidad_esperada_requisitos', 255)->nullable();
            $table->date('fecha_cumplimiento')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('propuestas');
    }
};
