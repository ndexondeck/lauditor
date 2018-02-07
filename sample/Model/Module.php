<?php

//namespace App;

class Module extends BaseModel
{
    //
    protected $fillable = ['name','description','visibility','order','icon'];
    public $timestamps = false;

    public function tasks(){
        return $this->hasMany('App\Task');
    }

    public function lazy_tasks(){
        return $this->hasMany('App\Task')->setEagerLoads([]);
    }


    public function getIsVisibleAttribute(){
        return $this->attributes['visibility']?"Yes":"No";
    }

    public function getModuleIconAttribute(){
        return "<div class='text-center'><i class='fa-2x ".$this->attributes['icon']."'></i></div>";
    }
}
