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
        Schema::create('jogador_partidas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('partida_multiplayer_id');
            $table->unsignedBigInteger('equipe_multiplayer_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->boolean('is_host')->default(false);
            $table->timestamps();

            $table->foreign('partida_multiplayer_id')->references('id')->on('partida_multiplayers')->onDelete('cascade');
            $table->foreign('equipe_multiplayer_id')->references('id')->on('equipe_multiplayers')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            $table->unique(['partida_multiplayer_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jogador_partidas');
    }
};
