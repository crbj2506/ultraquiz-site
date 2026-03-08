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
        Schema::create('partida_multiplayers', function (Blueprint $table) {
            $table->id();
            $table->string('pin')->unique();
            $table->string('status')->default('waiting'); // waiting, playing, finished
            $table->unsignedBigInteger('user_id'); // host
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partida_multiplayers');
    }
};
