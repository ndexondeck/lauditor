<?php

//namespace App;

use Ndexondeck\Lauditor\Model\Authorization;

class Group extends Authorization
{
    //
    protected $fillable = ['name', 'active_hour_id', 'holiday_login', 'weekend_login'];

    public $hidden = ['active_hour_id'];
    public $with = ['active_hour'];

    static function boot(){

        static::creating(function($group){
            $group->role = strtoupper(str_replace(' ','_',$group->name));
        });

        static::updating(function($group){
            $group->role = strtoupper(str_replace(' ','_',$group->name));
        });

        parent::boot();

    }

    public function active_hour() {
        return $this->belongsTo('App\ActiveHour');
    }

    public function permissions(){
        return $this->hasMany('App\Permission');
    }

    public function tasks(){
        return $this->belongstoMany('App\Task','permissions');
    }

    public function authorizers(){
        return $this->hasMany('App\PermissionAuthorizer');
    }

    public function authorizer_tasks(){
        return $this->belongstoMany('App\Task','permission_authorizers');
    }

    public function staff(){
        return $this->hasMany('App\Staff');
    }

    public function scopeEnabled($q){
        $q->where(function($q){
            $q->whereEnabled(1)->orWhere('enabled',2);
        });
    }

    public function scopeDisabled($q){
        $q->where(function($q){
            $q->whereEnabled(0)->orWhere('enabled',3);
        });
    }

}
