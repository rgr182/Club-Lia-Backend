<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDigitalResourcesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('digital_resources', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('bloque');
            $table->integer('grade');
            $table->integer('level');
            $table->string('name');
            $table->string('url_resource');
            $table->unsignedBigInteger('id_materia_base')->index();
            $table->foreign('id_materia_base')->references('id')->on('subjects')->onDelete('cascade');
            $table->unsignedBigInteger('id_category')->index();
            $table->foreign('id_category')->references('id')->on('digital_resources_categories')->onDelete('cascade');
            $table->string('description');
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
        Schema::dropIfExists('digital_resources');
    }
}
