<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNonplannedResources extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('nonplanned_resources', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('id_class')->index();
            $table->foreign('id_class')->references('id')->on('classes')->onDelete('cascade');
            $table->unsignedBigInteger('id_calendar')->index();
            $table->foreign('id_calendar')->references('id')->on('calendar')->onDelete('cascade');
            $table->unsignedBigInteger('id_resource')->index();
            $table->foreign('id_resource')->references('id')->on('digital_resources')->onDelete('cascade');
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
        Schema::dropIfExists('nonplanned_resources');
    }
}
