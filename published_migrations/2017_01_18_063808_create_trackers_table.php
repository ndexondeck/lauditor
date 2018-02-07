<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTrackersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    public function up()
    {
        Schema::create('trackers', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->increments('id');
            $table->integer('login_id')->unsigned();
            $table->string('session_id',60)->default('')->comment('PHP/Laravel Session ID');
            $table->string('ip_address',15)->default('')->comment('Last/current IP from which the user (is) logged in');
            $table->string('user_agent',255)->default('')->comment('Last/current Device from which the user (is) logged in');
            $table->dateTime('date_logged_in')->nullable()->comment('Datetime the user logged in for the current/last session');
            $table->dateTime('date_logged_out')->nullable()->comment('Datetime the user logged out of the last session');
            $table->timestamp('time_of_last_activity')->nullable()->comment('The last time the user was active (sent a request to the server)');
            $table->tinyInteger('failed_attempts')->length(1)->default('0')->comment('Counts number of failed attempts');
            $table->enum('logged_in',['0','1'])->default('0')->comment('Tells if a user is currently logged in or not');
            $table->timestamps();

            $table->foreign('login_id')->references('id')->on('logins');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('trackers');
    }
}
