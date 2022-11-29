<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('payment_id')->nullable();
            $table->integer('merchant_order_id')->nullable();
            $table->string('preference_id')->nullable();
            $table->string('name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone_number')->nullable();
            $table->unsignedBigInteger('id_licenses_type');
            $table->foreign('id_licenses_type')->references('id')->on('licenses_type');
            $table->float('unit_price', 10, 2);
            $table->string('payment_type')->nullable();
            $table->string('status')->nullable();
            $table->timestamp('expiry_date')->nullable();
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
        Schema::dropIfExists('orders');
    }
}
