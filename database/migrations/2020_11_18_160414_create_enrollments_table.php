<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEnrollmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('user_id')->index();
            $table->foreign('user_id')->references('id')->on('users');

            $table->unsignedBigInteger('period_id')->index();
            $table->foreign('period_id')->references('id')->on('periodos');

            $table->unsignedBigInteger('school_id')->index()->nullable();
            $table->foreign('school_id')->references('id')->on('schools');

            $table->uuid('license_id')->index();
            $table->foreign('license_id')->references('id')->on('licenses');

            $table->uuid('license_key_id')->index();
            $table->foreign('license_key_id')->references('id')->on('licenses_key');

            $table->unsignedBigInteger('role_id')->index();
            $table->foreign('role_id')->references('id')->on('roles');

            $table->unsignedBigInteger('grade_id')->index();
            $table->foreign('grade_id')->references('id')->on('grades');

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
        Schema::dropIfExists('enrollments');
    }
}
