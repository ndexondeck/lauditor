<?php

//namespace App;

use Ndexondeck\Lauditor\Model\Authorization;

class PermissionAuthorizer extends Authorization
{
    //
    protected $fillable = ['group_id','task_id'];

    public function task(){
        return $this->belongsTo(Task::class);
    }

    public function group(){
        return $this->belongsTo(Group::class);
    }
}
