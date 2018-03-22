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

            $authorization_user = config('ndexondeck.lauditor.authorization_user',[
                'column' => 'staff_id',
                'model' => 'Staff',
                'table' => 'staff',
            ]);

            $table->increments('id');
            $table->integer('task_id')->unsigned()->nullable();
            $table->integer($authorization_user['column'])->unsigned()->nullable();
            $table->string('action')->comment('User defined action relative to task');
            $table->string('rid',40)->comment('Unique request hash to track each authorization request');
            $table->enum('status',['0','1','2','3'])->default('0')->comment('0=Not forwarded,1=PENDING, 2=AUTHORIZED, 3= NOT-AUTHORIZED');
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->foreign('task_id')->references('id')->on('tasks');
            $table->foreign($authorization_user['column'])->references('id')->on($authorization_user['table']);
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
