<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStaffTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    public function up()
    {
        Schema::create('staff', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->increments('id');
            $table->integer('group_id')->unsigned();
            $table->string('employee_id',20)->unique()->comment("Staff number");
            $table->integer('active_hour_id')->unsigned()->nullable()->comment("When active_hour_id is null, it uses the group value");
            $table->string('fullname',60);
            $table->string('email',100)->unique();
            $table->enum('holiday_login', ['0', '1'])->nullable()->comment('null => uses group settings, 0 => Cannot login during holiday, 1=> Can login during holiday');
            $table->enum('weekend_login', ['0', '1'])->nullable()->comment('null => uses group settings, 0 => Cannot login during weekend, 1=> Can login during holiday');
            $table->enum('enabled',['0','1','2','3'])->default('1')->comment('1=Enabled, 0=Disabled, 2=Pending Disable, 3=Pending Enabled');
            $table->timestamps();

            $table->foreign('group_id')->references('id')->on('groups');
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
        Schema::drop('staff');
    }
}
