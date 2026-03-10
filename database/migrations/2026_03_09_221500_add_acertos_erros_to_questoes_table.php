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
        Schema::table('questoes', function (Blueprint $table) {
            if (!Schema::hasColumn('questoes', 'acertos')) {
                $table->unsignedInteger('acertos')->default(0);
            }

            if (!Schema::hasColumn('questoes', 'erros')) {
                $table->unsignedInteger('erros')->default(0);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('questoes', function (Blueprint $table) {
            if (Schema::hasColumn('questoes', 'acertos')) {
                $table->dropColumn('acertos');
            }

            if (Schema::hasColumn('questoes', 'erros')) {
                $table->dropColumn('erros');
            }
        });
    }
};
