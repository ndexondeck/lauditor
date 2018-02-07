<?php

//namespace App;

use Ndexondeck\Lauditor\Model\Authorization;

class Permission extends Authorization
{
    //
    protected $fillable = ['group_id','task_id'];

    public function task(){
        return $this->belongsTo('App\Task');
    }

    public function group(){
        return $this->belongsTo('App\Group');
    }

}
