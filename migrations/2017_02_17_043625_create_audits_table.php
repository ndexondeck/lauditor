<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAuditsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('audits', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $audit_user = config('ndexondeck.lauditor.audit_user');

            $table->increments('id');
            $table->integer($audit_user['column'])->unsigned();
            $table->string('trail_type')->default('')->comment("The model base class name of a user type e.g App\\Staff");
            $table->integer('trail_id')->unsigned()->comment("The is of the record on the Model table");
            $table->integer('authorization_id')->unsigned()->nullable()->comment("To determine the authorization request that led to this audit where exists");
            $table->string('user_name',60)->default('')->comment("User full name");
            $table->string('task_route',80)->default('')->comment("Task route name");
            $table->string('user_action')->default('')->comment("User defined action name for this audit");
            $table->string('table_name')->default('')->comment("The table name of the Model being audited");
            $table->string('action',20)->default('')->comment("Database action of an audit create, update or delete");
            $table->string('ip')->default('')->comment("The IP address of the user who initiated this action");
            $table->string('rid',40)->default('')->comment("The request identification hash aka the commit id");
            $table->enum('status',['0','1','2','3'])->length(1)->default('1')->comment("0=revoked state, 1=active state,2=log i.e logs of audit events,3=Awaiting authorization (pending trail)");
            $table->text('before')->nullable()->comment("The request identification hash aka the commit id");
            $table->text('after')->nullable()->comment("A json value that keeps the trails state before an action");
            $table->text('dependency')->nullable()->comment("A json value that keeps the trail state after an action");
            $table->timestamps();

            $table->foreign($audit_user['column'])->references('id')->on($audit_user['table']);
            $table->foreign('authorization_id')->references('id')->on('authorizations');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('audits');
    }
}
