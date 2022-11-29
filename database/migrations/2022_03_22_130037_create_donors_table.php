<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDonorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('donors', function (Blueprint $table) {
            $table->id();
            $table->string('institution');
            $table->string('business_name');
            $table->string('position');
            $table->string('name');
            $table->string('logo');
            $table->boolean('publish_donors');
            $table->boolean('publish_logo');
            $table->unsignedBigInteger('id_order')->index();
            $table->foreign('id_order')->references('id')->on('orders')->onDelete('cascade');
            $table->unsignedBigInteger('id_rol')->index();
            $table->foreign('id_rol')->references('id')->on('roles')->onDelete('cascade');
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
        Schema::dropIfExists('donors');
    }
}
