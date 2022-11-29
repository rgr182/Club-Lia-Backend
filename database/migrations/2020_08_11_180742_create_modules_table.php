<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateModulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->bigIncrements('id');
            //foreign keys
            $table->unsignedBigInteger('app_id')->index();
            $table->foreign('app_id')->references('id')->on('applications')->onDelete('cascade');
            $table->unsignedBigInteger('module_type_id')->index();
            $table->foreign('module_type_id')->references('id')->on('modules_type')->onDelete('cascade');
            //Info module
            $table->string('name')->unique();
            $table->string('description');
            $table->boolean('status')->default(false);
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
        Schema::dropIfExists('modules');
    }
}
