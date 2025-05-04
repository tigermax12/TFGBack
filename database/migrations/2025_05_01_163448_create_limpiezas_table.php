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
        Schema::create('limpiezas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_orden');
            $table->foreign('id_orden')
                ->references('id_orden')
                ->on('orden_de_trabajo')
                ->onDelete('cascade');
            $table->foreignId('operario')->nullable()->constrained('users')->onDelete('set null');

            $table->string('deposito')->nullable();
            $table->string('tipo_de_limpieza')->nullable();
            $table->string('observaciones')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('limpiezas');
    }
};
