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
        //
        Schema::table('questoes', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->default(1)->nullable()->after('fonte');
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
        Schema::table('logs', function (Blueprint $table) {
            $table->string('rota', 250)->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        Schema::table('logs', function (Blueprint $table) {
            $table->string('rota', 100)->nullable()->default(null)->change();
        });
        Schema::table('questoes', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
