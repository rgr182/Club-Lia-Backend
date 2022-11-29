<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableTeacherValidations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('teacher_validations', function (Blueprint $table) {
            $table->id();
            $table->string('level_school');
            $table->string('school_name');
            $table->string('intereses');
            $table->string('membership');
            $table->string('status');
            $table->string('document_type');
            $table->unsignedBigInteger('uuid')->index();
            $table->foreign('uuid')->references('id')->on('users')->onDelete('cascade');
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
        Schema::dropIfExists('table_teacher_validations');
    }
}