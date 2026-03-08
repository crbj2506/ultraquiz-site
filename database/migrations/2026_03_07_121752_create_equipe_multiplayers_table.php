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
        Schema::create('equipe_multiplayers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('partida_multiplayer_id');
            $table->string('nome');
            $table->string('cor')->nullable();
            $table->integer('pontuacao')->default(0);
            $table->timestamps();

            $table->foreign('partida_multiplayer_id')->references('id')->on('partida_multiplayers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipe_multiplayers');
    }
};
