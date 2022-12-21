<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('estatisticas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('questao_id');
            $table->unsignedBigInteger('resposta_id')->nullable()->default(null);
            $table->timestamps();

            $table->foreign('questao_id')->references('id')->on('questoes')->onDelete('cascade');
            $table->foreign('resposta_id')->references('id')->on('respostas')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('estatisticas');
    }
};
