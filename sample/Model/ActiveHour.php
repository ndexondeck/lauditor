<?php

//namespace App;

use Ndexondeck\Lauditor\Model\Authorization;

class ActiveHour extends Authorization
{
    protected $fillable = ['name', 'begin_time', 'end_time'];

    public function audits(){
        return $this->morphMany('Ndexondeck\Lauditor\Model\Audit', 'trail');
    }

    public function staff() {
        return $this->hasMany(Staff::class);
    }

    public function groups() {
        return $this->hasMany(Group::class);
    }

    public function scopeEnabled($q){
        $q->where(function($q){
            $q->whereEnabled('1')->orWhere('enabled','2');
        });
    }

    public function scopeDisabled($q){
        $q->where(function($q){
            $q->whereEnabled('0')->orWhere('enabled','3');
        });
    }

    public function getBeginTimeRawAttribute() {
        return $this->attributes['begin_time'];
    }

    public function getEndTimeRawAttribute() {
        return $this->attributes['end_time'];
    }

    public function getBeginTimeAttribute() {
        return date('H:i',strtotime($this->attributes['begin_time']));
    }
    public function getEndTimeAttribute() {
        return date('H:i',strtotime($this->attributes['end_time']));
    }
}
