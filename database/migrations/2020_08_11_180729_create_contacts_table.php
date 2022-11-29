<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContactsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->string('id', 100)->primary();
            //foreign keys
            $table->unsignedBigInteger('school_id')->index();
            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
            $table->uuid('user_id')->index();
            $table->foreign('user_id')->references('uuid')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('contact_type_id')->index();
            $table->foreign('contact_type_id')->references('id')->on('contact_type')->onDelete('cascade');
            $table->string('phone_number');
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
        Schema::dropIfExists('contacts');
    }
}
