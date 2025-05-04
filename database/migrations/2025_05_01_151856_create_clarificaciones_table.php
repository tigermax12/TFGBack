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
        Schema::create('clarificaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_orden');
            $table->foreign('id_orden')
                ->references('id_orden')
                ->on('orden_de_trabajo')
                ->onDelete('cascade');
            $table->foreignId('operario')->nullable()->constrained('users')->onDelete('set null');
            $table->string('producto')->nullable();
            $table->string('deposito')->nullable();
            $table->string('coadyuvantes_extra')->nullable();
            $table->string('observaciones')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clarificaciones');
    }
};
