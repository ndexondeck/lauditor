<?php

//namespace App;

use Ndexondeck\Lauditor\Model\Authorization;

class Tracker extends Authorization
{
    //
    protected $fillable = ['login_id','session_id','ip_address','user_agent','date_logged_in','date_logged_out','time_of_last_activity','failed_attempts','logged_in'];

    function login()
    {
        return $this->belongsTo(Login::class);
    }

    public function scopeFailed($q,$max=null){
        $q->where('failed_attempts','>','0');
        if($max) $q->where('failed_attempts','<',$max);
    }

    public function scopeFailing($q,$max=null){
        $lt = $q->latest()->first();
        $q->failed($max)->notLoggedIn();
        if($lt) $q->whereCreatedAt($lt->created_at->toDateTimeString());
    }

    public function scopeNotLoggedIn($q){
        $q->where(function ($q){
            $q->where('date_logged_in','')->orWhereNull('date_logged_in');
        });
    }

    public function setUserAgentAttribute($value){
        if(is_null($value)) $this->attributes['user_agent'] = "";
        else $this->attributes['user_agent'] = $value;
    }

    public function setIpAddressAttribute($value){
        if(is_null($value)) $this->attributes['ip_address'] = "";
        else $this->attributes['ip_address'] = $value;
    }


}
