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
        Schema::create('permissoes_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('permissao_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
            //Chave Estrangeira
            $table->foreign('permissao_id')->references('id')->on('permissoes')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('permissoes_users');
    }
};