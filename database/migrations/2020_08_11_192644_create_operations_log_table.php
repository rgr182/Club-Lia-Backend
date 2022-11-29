<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOperationsLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('operations_log', function (Blueprint $table) {
            $table->string('id', 100)->primary();
            //foreign keys
            $table->uuid('user_id')->index();
            $table->foreign('user_id')->references('uuid')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('action_id')->index();
            $table->foreign('action_id')->references('id')->on('actions')->onDelete('cascade');
            $table->unsignedBigInteger('module_id')->index();
            $table->foreign('module_id')->references('id')->on('modules')->onDelete('cascade');
            //Actions log info
            $table->dateTime('action_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('operations_log');
    }
}
