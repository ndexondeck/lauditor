<?php

namespace Ndexondeck\Lauditor\Model;

use App\Ndexondeck\Lauditor\Traits\AuditTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use App\Ndexondeck\Lauditor\Util;
use App\BaseModel;
use Ndexondeck\Lauditor\Exceptions\ResponseException;

class Audit extends BaseModel
{
    //
    use AuditTrait;

    public static $audit;

    public static $temp_audit;

    public static $labels = ['title','name','label'];

    protected static $request_filters = [];

    public static $transformer;

    public static $excluded = ['Audit','Authorization'];

    protected static $user_action = [];

    protected static $kill = false;

    protected static $prevent = false;

    protected static $anonymity = false;

    protected static $model_exclusions = [];

    protected $hidden = ['authorization_id'];
    
    protected static $config_key = "audit_user";

    protected static $default_configs = [
        'column' => 'login_id',
        'model' => 'Login',
        'table' => 'logins',
    ];

    function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->hidden = array_merge($this->hidden,[static::getUserIdColumn()]);
    }

    protected static function getUserTable(){
        return config('ndexondeck.lauditor.'.static::$config_key.'.table',static::$default_configs['table']);
    }

    protected static function getUserIdColumn(){
        return config('ndexondeck.lauditor.'.static::$config_key.'.column',static::$default_configs['column']);
    }

    protected static function getUserModel(){
        return config('ndexondeck.lauditor.'.static::$config_key.'.model',static::$default_configs['model']);
    }

    public static function transformPaginatedCollection($toArray)
    {
        if(isset($toArray['list'][0]->object_key)){
            foreach ($toArray['list'] as $i=>$audit){
                $toArray['list'][$i]->object_key = Audit::transformObjectKey($audit->object_key);
            }
        }

        return $toArray;
    }

    public static function transformObjectKey($object_key)
    {
        $object_key = explode("<=>",$object_key);

        $model = json_decode($object_key[1]);

        if(isset($model->first_name) and isset($model->last_name)) $name = "$model->first_name $model->last_name";
        elseif(isset($model->name)) $name = $model->name;
        elseif(isset($model->label)) $name = $model->label;
        elseif(isset($model->title)) $name = $model->title;
        elseif(isset($model->key)) $name = Util::normalCase($model->key);

        if(!isset($name)) return Util::normalCase(str_replace("App\\","",$object_key[0]));

        return Util::normalCase(str_replace("App\\","",$object_key[0])).": $name";
    }

    /**
     * @param $key
     * @return mixed|null
     */
    public static function getTempAudit($key)
    {
        if(isset(static::$temp_audit[$key])) return static::$temp_audit[$key];
        return null;
    }

    /**
     * @param $audit
     * @internal param mixed $temp_audit
     */
    public static function setTempAudit($audit)
    {
        static::$temp_audit = [
            static::getUserIdColumn()=>$audit->user_id,
            'ip'=>$audit->ip,
            'user_name'=>$audit->user_name,
            'task_route'=>$audit->task_route,
        ];
    }

    protected static function boot()
    {
        static::creating(function($model){

            if(Audit::isNotAllowed($model)) return true;

            self::$audit = (new Audit())->setConnection($model->getConnectionName());;
            $audit = self::$audit;

            $audit->action = "create";
        });

        static::updating(function($model){


            if(Audit::isNotAllowed($model)) return true;

            self::$audit = (new Audit())->setConnection($model->getConnectionName());;
            $audit = self::$audit;

            $audit->before = static::exclusiveJsonAttributes($model->getOriginal());
            $audit->action = "update";
        });

        static::deleting(function($model){

            if(Audit::isNotAllowed($model)) return true;

            self::$audit = (new Audit())->setConnection($model->getConnectionName());;
            $audit = self::$audit;

            $audit->before = static::exclusiveJsonAttributes($model->getAttributes());
            $audit->action = "delete";
        });

        parent::boot();

        //common procedures
        static::created(function($model){

            if(Audit::isNotAllowed($model)) return true;

            $audit = self::$audit;

            if(isset(static::$approving)) {
                $audit->authorization_id = static::$approving;
                $audit->user_id = static::getTempAudit(static::getUserIdColumn());
                $audit->ip = static::getTempAudit('ip');
                $audit->user_name = static::getTempAudit('user_name');
                $audit->task_route = static::getTempAudit('task_route');
            }
            else{
                $user = Util::login($model->getConnectionName());
                $audit->user_id = $user->id;
                $audit->ip = Util::getIp();
                $audit->user_name = $user->fullname." ($user->user_type_name)";
                $audit->task_route = Route::currentRouteName();
            }

            $baseClass = class_basename($model);

            $audit->user_action = static::generateUserAction($audit->action,$baseClass);

            if(empty($audit->user_action)) return;

            $audit->ip = Util::getIp();
            $audit->after = static::exclusiveJsonAttributes($model->getAttributes());
            $audit->table_name = $model->getTable();
            $audit->rid = static::makeRid();
            $audit->trail_type = get_class($model);
            $audit->trail_id = $model->id;
            $audit->save();

        });

        static::updated(function($model){

            if(Audit::isNotAllowed($model)) return true;

            $audit = self::$audit;
            if(isset(static::$approving)) {
                $audit->authorization_id = static::$approving;
                $audit->user_id = static::getTempAudit(static::getUserIdColumn());
                $audit->ip = static::getTempAudit('ip');
                $audit->user_name = static::getTempAudit('user_name');
                $audit->task_route = static::getTempAudit('task_route');
            }
            else{
                $user = Util::login($model->getConnectionName());
                $audit->user_id = $user->id;
                $audit->ip = Util::getIp();
                $audit->user_name = $user->fullname." ($user->user_type_name)";
                $audit->task_route = Route::currentRouteName();
            }

            $baseClass = class_basename($model);

            $audit->user_action = static::generateUserAction($audit->action,$baseClass);

            if(empty($audit->user_action)) return;

            $audit->after = static::exclusiveJsonAttributes($model->getAttributes());
            $audit->table_name = $model->getTable();
            $audit->rid = static::makeRid();
            $audit->trail_type = get_class($model);
            $audit->trail_id = $model->id;
            $audit->save();

        });

        static::deleted(function($model){

            if(Audit::isNotAllowed($model)) return true;

            $audit = self::$audit;
            if(isset(static::$approving)) {
                $audit->authorization_id = static::$approving;
                $audit->user_id = static::getTempAudit(static::getUserIdColumn());
                $audit->ip = static::getTempAudit('ip');
                $audit->user_name = static::getTempAudit('user_name');
                $audit->task_route = static::getTempAudit('task_route');
            }
            else{
                $user = Util::login($model->getConnectionName());
                $audit->user_id = $user->id;
                $audit->ip = Util::getIp();
                $audit->user_name = $user->fullname." ($user->user_type_name)";
                $audit->task_route = Route::currentRouteName();
            }

            $baseClass = class_basename($model);

            $audit->user_action = static::generateUserAction($audit->action,$baseClass);

            if(empty($audit->user_action)) return;

            $audit->ip = Util::getIp();
            $audit->table_name = $model->getTable();
            $audit->rid = static::makeRid();
            $audit->trail_id = $model->id;
            $audit->trail_type = get_class($model);
            $audit->user_id = Util::getLoginId();
            $audit->save();
        });
    }

    protected static function exclusiveAttributes($object){
        return array_diff_key($object, array_flip(static::$model_exclusions));
    }

    protected static function exclusiveJsonAttributes($object){
        return json_encode(static::exclusiveAttributes($object));
    }

    protected static function makeRid(){

        if (defined("STDIN")) return sha1(time());

        if(!empty(static::$request_filters)){
            if(empty($key = config('ndexondeck.lauditor.request_key'))){
                $request = Request::all();
            }
            else{
                $request = Request::get($key);
            }

            if(is_array($request))
                return sha1(Request::url().serialize(array_intersect_key($request,array_flip(static::$request_filters))).date('Y-m'));
        }
        return sha1(Request::url().serialize(Request::input()).date('Y-m'));
    }

    protected static function isNotAllowed($model)
    {
        $baseClass = class_basename($model);
        return in_array($baseClass,static::$excluded) or static::$kill or static::$prevent;
    }

    protected static function hasUserAction($baseClass){
        return isset(static::$user_action[$baseClass]);
    }

    protected static function getUserAction($baseClass){
        return static::$user_action[$baseClass];
    }

    protected static function generateUserAction($action,$baseClass){
        return (static::hasUserAction($baseClass))?static::getUserAction($baseClass):ucwords($action."d ". Util::normalCase($baseClass));
    }

    protected static function justEnabled($model){
        return (isset($model->getDirty()['enabled']) && $model->getDirty()['enabled'] == '1');
    }

    protected static function justDisabled($model){
        return (isset($model->getDirty()['enabled']) && $model->getDirty()['enabled'] == '0');
    }

    protected static function report($collection,$verb,$revoked_action,$messages,$prefix=""){

        $now = Util::now();
        $logs = [];

        foreach($collection as $audit){
            $logs[] = [
                'user_action'=>"$verb commit $audit->rid: $audit->user_action",
                'action'=>"$verb commit $audit->rid",
                'rid'=>'Log',
                'ip'=>Util::getIp(),
                'status'=>'2',
                static::getUserIdColumn()=>Util::getLoginId(),
                'table_name'=>'audits',
                'trail_type'=>get_class($audit),
                'trail_id'=>$audit->id,
                'created_at'=> $now,
                'updated_at'=> $now
            ];
        }

        $connection = $audit->getConnectionName();

        if(!empty($logs)) DB::connection($connection)->table('audits')->insert($logs);

        $group_class =  Util::getNamespace($connection,'Group');

        $Group = $group_class::with('users')->where('name','like','administrator%')->first();

        if($Group and $Group->users)

            static::mail(Util::conjunct($messages),$Group->users->lists('fullname','email')->toArray(),$prefix.$revoked_action);

    }

    public static function getValue($connection,$key,$value){
        static::$transformer = config('ndexondeck.lauditor.transformer');

        if(!isset(static::$transformer[$key])) return $value;

        $transform = explode(":",static::$transformer[$key]);

        return DB::connection($connection)->table($transform[0])->where($transform[2],$value)->pluck($transform[1]);
    }

    public static function setAnonymity($value){
        static::$anonymity = $value;
    }

    public static function getAnonymity(){
        return static::$anonymity;
    }

    public static function isAnonymous(){
        return (static::$anonymity);
    }

    public static function preventDefault(){
        static::$prevent = true;
    }

    public static function allowDefault(){
        static::$prevent = false;
    }

    public static function killDefault(){
        static::$kill = true;
    }

    public static function setUserAction($baseClass,$value){
        $baseClass = str_replace("App\\","",$baseClass);
        static::$user_action[$baseClass] = Util::normalCase($value);
    }

    public static function revokeTrail($collection)
    {
        $messages = $revoked_actions = [];

        foreach ($collection as $audit) {

            $connection = $audit->getConnectionName();

            $record_tags = [];

            $type = $audit->audit_type;

            $record_label = $record = json_decode($audit->before,true);

            if (!$record_label) $record_label = json_decode($audit->after,true);

            foreach ($record_label as $k => $v) {
                foreach (static::$labels as $label) if (strstr($k, $label)) $record_tags[] = $v;
            }
            unset($record_label);

            $record_tag = "";
            if (!empty($record_tags)) $record_tag = implode(' ', $record_tags);

            if($record_tag == "" or strstr($audit->user_action,$record_tag))$record_tag = "";
            else $record_tag = "`$record_tag`";

            $message = "$audit->user_action" . "$record_tag by " . $audit->user_full_name . " has been revoked by auditor (" . Util::login($connection)->user->full_name . ")";

            if($audit->trail){

                $revoked_actions[] = $audit->user_action." action";

                switch ($audit->action) {

                    case "create":
                        DB::connection($connection)->table($audit->table_name)->where('id', $audit->trail_id)->delete();
                        $message .= ", $type has been moved to the trash";
                        break;

                    case "update":
                        foreach($record as $k=>$v){
                            if(!is_string($v) and !is_null($v)) $record[$k] = json_encode($v);
                        }
                        DB::connection($connection)->table($audit->table_name)->where('id', $audit->trail_id)->update($record);
                        $current = json_decode($audit->after,true);
                        foreach($current as $k=>$v){
                            if(!is_string($v) and !is_null($v)) $current[$k] = json_encode($v);
                        }
                        $diff = array_diff($record, $current);

                        $add_message = ", but no changes where made to $type";
                        if (!empty($diff)) {
                            foreach ($diff as $key => $value) {
                                if($key == "password" or !is_string($current[$key]))
                                    $arr[] = ucwords(str_replace("_", " ", $key)) . " was changed";
                                else $arr[] = ucwords(str_replace("_", " ", $key)) . " changed from `{$current[$key]}` back to `$value`";
                            }
                            $add_message = ". " . Util::conjunct($arr);
                        }
                        $message .= ", $type has been updated$add_message";
                        break;

                    default :
                        return "Unknown trail";
                }

                $messages[] = $message;
            }
            elseif($audit->action == "delete"){

                $revoked_actions[] = $audit->user_action;

                foreach($record as $k=>$v){
                    if(!is_string($v) and !is_null($v)) $record[$k] = json_encode($v);
                }
                DB::connection($connection)->table($audit->table_name)->insert($record);
                $message .= ", $type has been restored";

                $messages[] = $message;
            }

            $audit->status = 0;
            $audit->save();
        }

        if(!empty($revoked_actions)) {
            $revoked_action = Util::conjunct($revoked_actions)." has been Revoked";

            static::report($collection,"Revoked",$revoked_action,$messages,"Auditor: ");
        }
        else throw new ResponseException("no_audit_trail");

        return $revoked_action;
    }

    public static function restoreTrail($collection)
    {
        $messages = $restored_actions = [];

        foreach ($collection as $audit) {

            $connection = $audit->getConnectionName();

            $record_tags = [];

            $type = $audit->audit_type;

            $record_label = $current = json_decode($audit->after,true);

            if (!$record_label) $record_label = json_decode($audit->before,true);

            foreach ($record_label as $k => $v) {
                foreach (static::$labels as $label) if (strstr($k, $label)) $record_tags[] = $v;
            }
            unset($record_label);

            $record_tag = "";
            if (!empty($record_tags)) $record_tag = implode(' ', $record_tags);

            if($record_tag == "" or strstr($audit->user_action,$record_tag))$record_tag = "";
            else $record_tag = "`$record_tag`";

            $message = "$audit->user_action" . "$record_tag by " . $audit->user_full_name . " which was previously revoked, has been restored by auditor (" . Util::login($connection)->full_name . ")";

            if($audit->trail){

                $revoked_actions[] = $audit->user_action." action";

                switch ($audit->action) {

                    case "delete":
                        DB::connection($connection)->table($audit->table_name)->where('id', $audit->trail_id)->delete();
                        $message .= ", $type has been moved to the trash";
                        break;

                    case "update":
                        foreach($current as $k=>$v){
                            if(!is_string($v) and !is_null($v)) $current[$k] = json_encode($v);
                        }
                        DB::connection($connection)->table($audit->table_name)->where('id', $audit->trail_id)->update($current);
                        $record = json_decode($audit->before,true);
                        foreach($record as $k=>$v){
                            if(!is_string($v) and !is_null($v)) $record[$k] = json_encode($v);
                        }
                        $diff = array_diff($current, $record);

                        $add_message = ", but no changes where made to $type";
                        if (!empty($diff)) {
                            foreach ($diff as $key => $value) {
                                if($key == "password" or !is_string($record[$key]))
                                    $arr[] = ucwords(str_replace("_", " ", $key)) . " was changed";
                                else $arr[] = ucwords(str_replace("_", " ", $key)) . " changed from `{$record[$key]}` back to `$value`";
                            }
                            $add_message = ". " . Util::conjunct($arr);
                        }
                        $message .= ", $type has been updated$add_message";
                        break;

                    default :
                        return "Unknown trail";
                }

                $messages[] = $message;
            }
            elseif($audit->action == "create"){

                $revoked_actions[] = $audit->user_action;

                foreach($current as $k=>$v){
                    if(!is_string($v) and !is_null($v)) $current[$k] = json_encode($v);
                }
                DB::connection($connection)->table($audit->table_name)->insert($current);
                $message .= ", $type has been re-added";

                $messages[] = $message;
            }

            $audit->status = 1;
            $audit->save();
        }

        if(!empty($revoked_actions)) {
            $revoked_action = Util::conjunct($revoked_actions)." has been Restored";

            static::report($collection,"Restored",$revoked_action,$messages,"Auditor: ");
        }
        else throw new ResponseException("no_audit_trail");

        return $revoked_action;
    }

    public static function mail($message,$users,$subject,$view='audit'){

        if(empty($users)) return false;

        return Mail::send('emails.'.$view, ['msg'=>$message], function ($m) use ($users,$subject) {
            $m->from(env('APP_EMAIL'), env('APP_NAME'));
            foreach($users as $email=>$fullName)
                $m->to($email, $fullName)->subject($subject);
        });
    }

    //Eloquent
    public function trail(){
        return $this->morphTo();
    }

    public function authorization(){
        return $this->belongsTo('Ndexondeck\Lauditor\Model\Authorization');
    }

    public function user(){
        return $this->belongsTo(Util::getNamespace($this->connection,static::getUserModel()))->setEagerLoads([]);
    }

    public function scopeCommitted($q){
        $q->where('rid','!=','');
    }

    public function scopePending($q){
        $q->where('status','3');
    }

    public function scopeNotPending($q){
        $q->where('status','1');
    }

    public function scopeToday($q,$table_prefix=null, $date_type = 'created_at'){
        $table_prefix .= (!empty($table_prefix))?".":"";

        $q->where($table_prefix.$date_type, '>=', date('Y-m-d 00:00:00'))
            ->where($table_prefix.$date_type, '<=', date('Y-m-d 23:59:59'));
    }

    public function setTaskRouteAttribute($value){
        $this->attributes['task_route'] = is_null($value)?"":$value;
    }

    public function getIsEnabledAttribute(){
        return ($this->enabled == "1" or $this->enabled == "2");
    }

    public function getAuditCreatedAttribute(){

        return $this->attributes['created_at'];
    }

    public function getAuditUserFullNameAttribute(){

        if($this->user) return $this->user->fullname." (".$this->type.")";

        return $this->type;
    }

    public function getAuditTypeAttribute(){

        $v = explode("\\",$this->attributes['trail_type']);
        return end($v);
    }

    public function getCommitAttribute(){
        return substr($this->attributes['rid'],0,6);
    }

    public function getUserIdAttribute(){
        return $this->{static::getUserIdColumn()};
    }

    public function setUserIdAttribute($value){
        $this->attributes[static::getUserIdColumn()] = $value;
    }
    
}
