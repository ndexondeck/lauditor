<?php

//namespace App;

use App\Ndexondeck\Lauditor\Util;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;
use Ndexondeck\Lauditor\Contracts\User;
use Ndexondeck\Lauditor\Model\Authorization;

class Staff extends Authorization implements User
{
    //
    protected $fillable = ['group_id','employee_id','active_hour_id','fullname','email', 'holiday_login', 'weekend_login', 'enabled'];

    public $hidden = ['group_id', 'active_hour_id'];

    public $with = ['active_hour', 'group'];

    protected static $request_filters = ['employee_id'];

    public static function boot(){

        static::deleting(function($staff){
            if(!isset(static::$approving)) return;

            $login = $staff->login;
            $login::setUserAction("Login","Deleted login");
            $login->delete();
        });

        parent::boot();

        static::created(function($staff){

            event('user.created',$staff);
            
        });

        static::updated(function($staff){

            event('staff.details.changed',$staff);

        });

    }

    public static function getUsername(Model $staff){
        return $staff->employee_id;
    }

    //eloquent
    public function login(){
        return $this->morphOne(Login::class,'user')->setEagerLoads([]);
    }

    public function group(){
        return $this->belongsTo(Group::class);
    }

    public function authorizations(){
        return $this->hasMany('Ndexondeck\Lauditor\Model\Authorization');
    }

    public function audits(){
        return $this->morphMany('Ndexondeck\Lauditor\Model\Audit', 'trail');
    }

    public function active_hour() {
        return $this->belongsTo(ActiveHour::class);
    }

    public function scopeAuthorizers($q,$model){

        $user = $model->audits->first()->login->user;

        $q->whereHas('group',function($q) use ($model){
            $q->whereHas('authorizer_tasks',function($q) use ($model){
                $q->whereTaskId($model->task_id);
            });
        });

        if($user->branch_id) $q->whereBranchId($user->branch_id);
    }

    public function scopeSearch($q,$query=null){

        $query = is_null($query)?Request::get('q'):$query;
        if($query){
            $q->where(function($q) use ($query){
                $queries = explode(" ",$query);
                foreach($queries as $str){
                    $q->where('fullname','like',"%".$str."%")
                        ->orWhere('employee_id','like',"%".$str."%")
                        ->orWhere('email','like',"%".$str."%");
                }
            });
        }

    }

    public function setActiveHourIdAttribute($value){
        if(empty($value)) $this->attributes['active_hour_id'] = null;
        else $this->attributes['active_hour_id'] = $value;
    }

    public function setHolidayLoginAttribute($value){
        if(trim($value) == "") $this->attributes['holiday_login'] = null;
        else $this->attributes['holiday_login'] = $value;
    }

    public function setWeekendLoginAttribute($value){
        if(trim($value) == "") $this->attributes['weekend_login'] = null;
        else $this->attributes['weekend_login'] = $value;
    }

    public function getActiveHourIdAttribute() {
        if(!$this->attributes['active_hour_id']){
            return $this->group->active_hour_id;
        }
        return $this->attributes['active_hour_id'];
    }

    public function getHolidayLoginAttribute() {
        if(is_null($this->attributes['holiday_login'])){
            return $this->group->holiday_login;
        }
        return $this->attributes['holiday_login'];
    }

    public function getWeekendLoginAttribute() {
        if(is_null($this->attributes['weekend_login'])){
            return $this->group->weekend_login;
        }
        return $this->attributes['weekend_login'];
    }

    public function getIsOnDutyAttribute() {
        if(!$active_hour = $this->active_hour()->first()){
            $active_hour = $this->group->active_hour;
        }

        $now = Carbon::now();
        $from = Util::carbonFromFormat('H:i:s', $active_hour->begin_time_raw);
        $to = Util::carbonFromFormat('H:i:s', $active_hour->end_time_raw);

        if($from->gt($to)){ // time frame rolls across midnight
            $to->addDay();
            if($now->gte($from) && $now->lte($to)){
                return true;
            }
            $to->subDay();
            $from->subDay();

            if($now->gte($from) && $now->lte($to)){
                return true;
            }
        } else { // time frame is within the same day
            if($now->gte($from) && $now->lte($to)){
                return true;
            }
        }

        return false;
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
