<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewFieldsToLicensesType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('licenses_type', function (Blueprint $table) {
            $table->string('title')->after('id');
            $table->integer('price')->after('description_license_type');
            $table->string('category_id')->after('price');
            $table->integer('sold')->default(0)->after('category_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('licenses_type', function (Blueprint $table) {
            //
        });
    }
}
