<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {

            $table->bigIncrements('id');
            $table->integer('AppUserId')->nullable();
            $table->uuid('uuid')->index();
            $table->string('username');
            $table->string('name');
            $table->string('second_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('second_last_name')->nullable();
            $table->string('email');
            $table->integer('grade')->nullable();
            $table->string('avatar')->nullable();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            //extra info
            $table->dateTime('member_since');
            $table->dateTime('last_login')->nullable();

            $table->boolean('verified_email')->default(false);
            $table->rememberToken();
            $table->softDeletes();
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
        Schema::dropIfExists('users');
    }
}
