<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCountryToContactsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contacts', function (Blueprint $table) {
            //
            //$table->dropForeign(['user_id']);
            $table->dropForeign(['school_id']);
            $table->dropForeign(['contact_type_id']);
            //$table->dropColumn('user_id');
            $table->dropColumn('school_id');
            $table->dropColumn('contact_type_id');
            //$table->unsignedBigInteger('user_id')->index();
            //$table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('country');
            $table->string('state');
            $table->string('city');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contacts', function (Blueprint $table) {
            //
            $table->dropColumn('country');
            $table->dropColumn('state');
            $table->dropColumn('city');
        });
    }
}
