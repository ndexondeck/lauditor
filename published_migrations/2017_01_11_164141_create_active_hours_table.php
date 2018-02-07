<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateActiveHoursTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('active_hours', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->increments('id');
            $table->string('name', '100')->unique();
            $table->time('begin_time');
            $table->time('end_time');
            $table->enum('enabled',['0','1','2','3'])->default('1')->comment('1=Enabled, 0=Disabled, 2=Pending Disable, 3=Pending Enabled');
            $table->timestamps();

            $table->unique(['begin_time','end_time']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('active_hours');
    }
}
