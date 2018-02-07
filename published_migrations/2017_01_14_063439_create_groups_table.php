<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->increments('id');
            $table->string('name',40)->unique();
            $table->string('role',40)->unique()->comment('URL friendly Group slug');
            $table->integer('active_hour_id')->unsigned()->comment('specifies the time range an employee can access the system');;
            $table->enum('holiday_login', ['0', '1'])->default('0')->comment('0 => Cannot login during holiday, 1=> Can login during holiday');
            $table->enum('weekend_login', ['0', '1'])->default('0')->comment('0 => Cannot login during weekend, 1=> Can login during holiday');
            $table->enum('enabled',['0','1','2','3'])->default('1')->comment('1=Enabled, 0=Disabled, 2=Pending Disable, 3=Pending Enabled');
            $table->timestamps();

            $table->foreign('active_hour_id')->references('id')->on('active_hours');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('groups');
    }
}
