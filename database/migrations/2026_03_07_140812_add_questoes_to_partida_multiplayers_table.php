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
        Schema::table('partida_multiplayers', function (Blueprint $table) {
            $table->longText('questoes_json')->nullable();
            $table->integer('pergunta_atual_index')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('partida_multiplayers', function (Blueprint $table) {
            //
        });
    }
};
