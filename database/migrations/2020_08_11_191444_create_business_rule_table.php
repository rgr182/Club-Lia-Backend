<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusinessRuleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_rule', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('action_id')->index();
            $table->foreign('action_id')->references('id')->on('actions')->onDelete('cascade');
            $table->unsignedBigInteger('rule_type_id')->index();
            $table->foreign('rule_type_id')->references('id')->on('rules_type')->onDelete('cascade');
            $table->string('business_rule')->unique();
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
        Schema::dropIfExists('business_rule');
    }
}
