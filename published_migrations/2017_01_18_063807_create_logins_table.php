<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLoginsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('logins', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->increments('id');
            $table->string('username',50)->unique();
            $table->string('password',60);
            $table->string('user_type')->comment("The model base class name of a user type e.g App\\Staff");
            $table->integer('user_id')->unsigned()->comment("The is of the record on the Model table");
            $table->dateTime('last_logged_in')->nullable()->comment("The last login time");
            $table->enum('status',array('0','1','2','3'))->default('1')->comment('0=Disabled, 1=Enabled, 2=Locked, 3=Reset');
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
        Schema::drop('logins');
    }
}
