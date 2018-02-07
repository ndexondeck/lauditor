<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAuthorizationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('authorizations', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->increments('id');
            $table->integer('task_id')->unsigned()->nullable();
            $table->integer('staff_id')->unsigned()->nullable();
            $table->string('action')->comment('User defined action relative to task');
            $table->string('rid',40)->comment('Unique request hash to track each authorization request');
            $table->enum('status',['0','1','2','3'])->default('0')->comment('0=Not forwarded,1=PENDING, 2=AUTHORIZED, 3= NOT-AUTHORIZED');
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->foreign('task_id')->references('id')->on('tasks');
            $table->foreign('staff_id')->references('id')->on('staff');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('authorizations');
    }
}
