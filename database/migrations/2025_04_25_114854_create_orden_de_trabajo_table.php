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
        Schema::create('orden_de_trabajo', function (Blueprint $table) {
            $table->id('id_orden');
            $table->string('tipo_de_orden');
            $table->integer('prioridad');
            $table->string('estado')->default('pendiente');
            $table->foreignId('id_user_creador')->constrained('users')->onDelete('cascade');

            $table->timestamp('fecha_de_creacion')->nullable();
            $table->timestamp('fecha_de_realizacion')->nullable();
            $table->timestamp('fecha_de_modificacion')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orden_de_trabajo');
    }
};
