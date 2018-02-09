<?php

namespace Ndexondeck\Lauditor\Model;

use App\Ndexondeck\Lauditor\Util;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Ndexondeck\Lauditor\Exceptions\ResponseException;
use Ndexondeck\Lauditor\Sql;

class Authorization extends Audit
{
    //

    protected static $auth_action;

    protected static $auth_rid;

    protected static $auth_dependency;

    protected static $approving;

    protected static $kill_auth = false;

    protected static $prevent_auth;

    public static $audit;

    protected static $auth_id;

    protected static $auth_new;

    protected $fillable = ['action','rid','status','staff_id','comment','task_id'];

    public $hidden = ['staff_id','task_id'];

    /**
     * @return mixed
     */
    public static function getAuthId()
    {
        return self::$auth_id;
    }

    protected static function boot()
    {

        static::creating(function($model) {

            $connection = $model->getConnectionName();

            if (static::$approving) return;

            if (static::isNotAllowed($model)){
                if(static::$prevent_auth or static::$kill_auth) return;
                return true;
            }

            if (empty(static::$auth_action)) {
                throw new ResponseException('missing_authorization_action');
            }
            else{

                $Authorization = self::getAuth($connection);

                $baseClass = class_basename($model);

                $audit = (new Audit())->setConnection($connection);
                $login = Util::login($connection);

                $audit->status = 3;
                $audit->action = "create";
                $audit->user_action = static::generateUserAction($audit->action,$baseClass);
                $audit->trail_type = get_class($model);
                $audit->trail_id = 0;
                $audit->ip = Util::getIp();
                $audit->rid = '';
                $audit->dependency = json_encode(static::$auth_dependency);
                $audit->after = static::exclusiveJsonAttributes($model->getAttributes());
                $audit->table_name = $model->getTable();
                $audit->login_id = $login->id;
                $audit->authorization_id = $Authorization->id;
                $audit->user_name = $login->user->fullname." ($login->user_type_name)";
                $audit->task_route = Route::currentRouteName();

                self::checkDuplicate($audit);

                $audit->save();

                return false;
            }
        });

        static::updating(function($model){

            $connection = $model->getConnectionName();

            if(static::$approving) return;

            if(static::isNotAllowed($model)) {

                if(get_class($model) == self::class){

                    $currentModel =  $model->getOriginal();

                    if($currentModel['status'] == '2' or $currentModel['status'] == '3')
                        throw new ResponseException('access_denied');

                    if($model->status == "1" and $currentModel['status'] != "0")
                        throw new ResponseException('authorize_forwards_only_pending');

                    if($model->status == "2"){
                        if($currentModel['status'] != "1") throw new ResponseException('authorize_approves_only_forwarded');

                        $pending_audits = $model->audits()->pending()->get();

                        if($pending_audits->isEmpty()) throw new ResponseException('empty_authorization_request');

                        static::approving($currentModel['id']);

                        $predecessor = null;

                        foreach($pending_audits as $audit){

                            $class = $audit->trail_type;
                            $dependencies = json_decode($audit->dependency,true);

                            static::setTempAudit($audit);

                            static::setUserAction($audit->trail_type,$audit->user_action);

                            if($audit->trail){

                                $auth_model = $audit->trail;

                                switch ($audit->action) {

                                    case "delete":
                                        $auth_model->delete();
                                        break;

                                    case "update":
                                        $record = json_decode($audit->after,true);

                                        foreach($record as $k=>$v){
                                            if(!is_string($v) and !is_null($v)) $record[$k] = json_encode($v);
                                        }

                                        $record = static::mergeDependency($record,$predecessor,$dependencies);

                                        $changed_data = array_diff_assoc($record,$auth_model->getAttributes());

                                        $auth_model->casts = [];

                                        foreach($changed_data as $k=>$v){
                                            $auth_model->$k = $v;
                                        }

                                        $auth_model->save();
                                        break;

                                    default :
                                        return "Unknown trail";
                                }
                            }
                            elseif($audit->action == "create"){

                                /** @var Model $auth_model */
                                $auth_model = new $class;

                                $record = json_decode($audit->after,true);

                                foreach($record as $k=>$v){
                                    if(!is_string($v) and !is_null($v)) $record[$k] = json_encode($v);
                                }

                                $record = static::mergeDependency($record,$predecessor,$dependencies);

                                $auth_model->casts = [];

                                foreach($record as $k=>$v){
                                    $auth_model->$k = $v;
                                }

                                $auth_model->save();
                            }

                            if(isset($auth_model)) $predecessor[$audit->table_name] = $auth_model;

                            $audit->delete();

                        }

                    }

                    if($model->status == "3"){
                        if($currentModel['status'] != "1") throw new ResponseException('authorize_rejects_only_forwarded');

                        if(empty($model->comment) or empty($model->staff_id)) throw new ResponseException('missing_authorize_rejection_data');

                        //we need to return all toggle states to its default
                        foreach($model->audits as $audit){
                            if(!$audit->trail) continue;

                            if($audit->trail->enabled == '2') DB::connection($connection)->table($audit->table_name)->where('id',$audit->trail->id)->update(['enabled'=>1]);
                            elseif($audit->trail->enabled == '3') DB::connection($connection)->table($audit->table_name)->where('id',$audit->trail->id)->update(['enabled'=>0]);
                        }

                    }

                }

                if(static::$prevent_auth or static::$kill_auth) return;

                return true;
            }

            if(empty(static::$auth_action)){
                throw new ResponseException('missing_authorization_action');
            }
            else {

                $Authorization = self::getAuth($connection);

                $baseClass = class_basename($model);

                $audit = (new Audit())->setConnection($connection);
                $login = Util::login($connection);

                $audit->status = 3;
                $audit->action = "update";
                $audit->user_action = static::generateUserAction($audit->action,$baseClass);
                $audit->trail_type = get_class($model);
                $audit->trail_id = $model->id;
                $audit->ip = Util::getIp();
                $audit->rid = '';
                $audit->dependency = json_encode(static::$auth_dependency);
                $audit->before = static::exclusiveJsonAttributes($model->getOriginal());
                $audit->after = static::exclusiveJsonAttributes($model->getAttributes());
                $audit->table_name = $model->getTable();
                $audit->login_id = $login->id;
                $audit->authorization_id = $Authorization->id;
                $audit->user_name = $login->user->fullname." ($login->user_type_name)";
                $audit->task_route = Route::currentRouteName();

                self::checkDuplicate($audit);

                $audit->save();

                return false;
            }
        });

        static::deleting(function($model){

            $connection = $model->getConnectionName();

            if(static::$approving) return;

            if(static::isNotAllowed($model)) {

                if(get_class($model) == self::class){
                    if($model->status != "0") throw new ResponseException('authorize_discards_only_pending');

                    //we need to return all toggle states to its default
                    foreach($model->audits as $audit){
                        if(!$audit->trail) continue;

                        if($audit->trail->enabled == '2') DB::connection($connection)->table($audit->table_name)->where('id',$audit->trail->id)->update(['enabled'=>1]);
                        elseif($audit->trail->enabled == '3') DB::connection($connection)->table($audit->table_name)->where('id',$audit->trail->id)->update(['enabled'=>0]);
                    }

                    $model->audits()->delete();
                }

                if(static::$prevent_auth or static::$kill_auth) return;

                return true;
            }

            if(empty(static::$auth_action)){
                throw new ResponseException('missing_authorization_action');
            }
            else{

                $Authorization = self::getAuth($connection);

                $baseClass = class_basename($model);

                $audit = (new Audit())->setConnection($connection);
                $login = Util::login($connection);

                $audit->status = 3;
                $audit->action = "delete";
                $audit->user_action = static::generateUserAction($audit->action,$baseClass);
                $audit->trail_type = get_class($model);
                $audit->trail_id = $model->id;
                $audit->ip = Util::getIp();;
                $audit->rid =  '';
                $audit->dependency = json_encode(static::$auth_dependency);
                $audit->before = static::exclusiveJsonAttributes($model->getAttributes());
                $audit->table_name = $model->getTable();
                $audit->login_id = $login->id;
                $audit->authorization_id = $Authorization->id;
                $audit->user_name = $login->user->fullname." ($login->user_type_name)";
                $audit->task_route = Route::currentRouteName();

                self::checkDuplicate($audit);

                $audit->save();

                return false;
            }
        });

        parent::boot();

        static::created(function($model){
            if(get_class($model) == self::class) {

                event('on.screen.notification',$model);

                if($model->status == 1) event('on.forwarding.authorization',$model);
            }
        });

        static::updated(function($model){
            if(get_class($model) == self::class) {

                event('on.screen.notification',$model);

                if($model->status == 1) event('on.forwarding.authorization',$model);
            }
        });

        static::deleted(function($model){
            if(get_class($model) == self::class) {

                event('on.screen.notification',$model);

            }
        });

    }

    private static function mergeDependency($record,$predecessor,$dependencies){
        if($predecessor and $dependencies){
            foreach($dependencies as $key=>$dependency){

                $dependency = explode(".",$dependency);

                if(isset($dependency[1])){
                    $record[$key] = $predecessor[$dependency[0]]->{$dependency[1]};
                }
                else{
                    $v = end($predecessor);
                    $record[$key] = $v->$dependency[0];
                }
            }
        }

        return $record;
    }

    /**
     * @param $connection
     * @return Authorization
     * @throws ResponseException
     */
    protected static function getAuth($connection)
    {
        $rid = static::makeRid();

        $AuthorizationObject = (new Authorization())->setConnection($connection);

        $Authorization = $AuthorizationObject->whereRid($rid)->whereIn('status', ['0','1'])->first();

        if(!$Authorization){

            static::$auth_new = true;

            $task = DB::connection($connection)->table('tasks')->where('route',Route::currentRouteName())->first();

            if($task) $task = $task->id;

            $Authorization = $AuthorizationObject->create([
                'action'=>static::$auth_action,
                'task_id'=> $task,
                'rid'=>$rid,
                'status'=>((Util::setting('authorization_direct_forwarding') == "yes")?'1':'0')
            ]);
        }
        else{
            if(!static::$auth_new){
                if ($Authorization->status == '0') throw new ResponseException('duplicate_auth_request');
                elseif ($Authorization->status == '1') throw new ResponseException('duplicate_forwarded_auth_request');
            }
        }

        static::$auth_id = $Authorization->id;
        return $Authorization;
    }

    protected static function checkDuplicate(Model $audit){

        if($audit->dependency == "null"){

            $checkMatches = ['action', 'before', 'after', 'trail_type', 'table_name'];

            $v = $audit::with('authorization')->whereHas('authorization',function($q){
                $q->where(function($q){
                    $q->where('status',0)->orWhere('status',1);
                })->whereRaw((new Sql())->dateAdd("created_at",5,"MINUTE")." < '".Carbon::now()->toDateTimeString()."'");
            });

            foreach ($checkMatches as $checkMatch) {
                $v->where($checkMatch, $audit->{$checkMatch});
            }

            $v = $v->get();

            if (!$v->isEmpty()) {
                foreach ($v as $val) {
                    if ($val->authorization->status == '0'){
                        static::rollbackRequest($audit->getConnectionName());
                        throw new ResponseException('similar_auth_request');
                    }
                    elseif ($val->authorization->status == '1'){
                        static::rollbackRequest($audit->getConnectionName());
                        throw new ResponseException('similar_forwarded_auth_request');
                    }
                }
            }
        }


    }

    protected static function approving($id)
    {
        static::$approving = $id;
    }

    public static function setAuthAction($value){
        static::$auth_action = Util::normalCase($value);
    }

    public static function setDependency(array $array)
    {
        static::$auth_dependency = $array;
    }

    public static function isPreventingAuth(){
        return (static::$kill_auth or static::$prevent_auth or static::$prevent or static::$kill);
    }

    public static function killAuthorizing(){
        static::$kill_auth = true;
    }

    public static function preventAuthorizing(){
        static::$prevent_auth = true;
    }

    public static function allowAuthorizing(){
        static::$prevent_auth = false;
    }

    protected static function isNotAllowed($model)
    {
        $baseClass = class_basename($model);
        return in_array($baseClass,static::$excluded) or static::$kill or static::$kill_auth or static::$prevent or static::$prevent_auth;
    }

    protected static function rollbackRequest($connection)
    {
        if(static::$auth_id){
            if(static::$auth_new){
                DB::connection($connection)->table('audits')->where('authorization_id',static::$auth_id)->delete();
                DB::connection($connection)->table('authorizations')->where('id',static::$auth_id)->delete();
            }
        }
    }




    //Eloquent
    public function audits(){
        return $this->hasMany('App\Audit');
    }

    public function task(){
        $connection = $this->connection ? : "mysql";
        $namespace = config('ndexondeck.lauditor.connection_map.'.$connection,'App\\');
        return $this->belongsTo($namespace.'Task');
    }

    public function staff(){
        $connection = $this->connection ? : "mysql";
        $namespace = config('ndexondeck.lauditor.connection_map.'.$connection,'App\\');
        return $this->belongsTo($namespace.'Staff');
    }

    public function scopeStatus($q,$type){

        if("".intval($type) !== "$type"){
            $types = ['pending'=>'0','forwarded'=>'1','approved'=>'2','rejected'=>'3'];
            $type = isset($types[$type])?$types[$type]:null;
        }

        if($type !== null) $q->whereStatus($type);
    }

    public function scopePermitted($q,$staff){

        $q->whereHas('task',function($q) use ($staff){
            $q->whereHas('authorizers',function($q) use ($staff){
                $q->whereGroupId($staff->group_id);
            });
        })->myBranch($staff);
    }

    public function scopeMyBranch($q,$staff){

        $q->whereHas('audits',function($q) use ($staff){
            $q->whereHas('login',function($q) use ($staff){
                $q->branch($staff->branch_id);
            });
        });
    }

    public function scopeMe($q,$login_id=null){
        $q->whereHas('audits',function($q) use ($login_id){
            $q->where('login_id',($login_id)?$login_id:Util::getLoginId());
        });
    }

    public function scopeAfterNow($q){
        $now = date('Y-m-d H:i:s',time() - 10);
        $q->where('updated_at','>=',$now);
    }

    public function setEnabledAttribute($value){

        $this->attributes['enabled'] = ($value == "2")?"0":(($value == "3")?"1":$value);
    }

    public function getCreatedAttribute(){

        return $this->attributes['created_at'];
    }

    public function getCommitAttribute(){
        return substr($this->attributes['rid'],0,6);
    }
}
