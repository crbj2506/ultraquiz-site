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
        Schema::create('voto_questaos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('questao_id');
            $table->unsignedBigInteger('user_id');
            $table->integer('voto'); // 1 = gostou, -1 = nao gostou
            $table->timestamps();

            $table->foreign('questao_id')->references('id')->on('questoes')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            $table->unique(['questao_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voto_questaos');
    }
};
