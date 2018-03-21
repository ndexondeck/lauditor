<?php

//namespace App;

use App\Ndexondeck\Lauditor\Util;
use Carbon\Carbon;
use Ndexondeck\Lauditor\Contracts\AuditUser;
use Ndexondeck\Lauditor\Model\Authorization;
use Ndexondeck\Lauditor\Sql;

class Login extends Authorization implements AuditUser
{
    //

    private static $maxFailedAttempts = 3;

    protected $fillable = ['username','password','user_id','status','last_logged_in'];

    public $hidden = ['user_id','password'];

    public $with = ['user'];

    public static $stringPassword;

    public static function boot(){

        static::deleting(function($login){

            if(static::isPreventingAuth()){
                $login->trackers()->delete();
                $login->notifications()->delete();
            }
            else{
                if(!isset(static::$approving)) return;

                $login->trackers()->delete();
                $login->notifications()->delete();
            }

        });

        parent::boot();

        static::created(function($login){

            if($login->status == 3){
                event('on.screen.notification',$login,$login->id);
            }

        });

        static::updated(function($login){

            event('login.changed',$login);

        });

    }

    public static function failed($login, $request){

        static::preventDefault();
        $max_retry = Util::setting('max_log_retry');

        if($tracker = $login->trackers()->failing($max_retry)->first()){
            $tracker->failed_attempts++;

            if($tracker->failed_attempts == $max_retry && !static::isSysAdmin($login)) {
                $login->load('user');
                if($login != 'App\Staff') event('send.email',$login->user, "Locked Account Notification", ['login' => $login], 'emails.locked_account_notification');
                $login->update(['status' => 2]);
            }
            $tracker->save();
        }else{
            $login->trackers()->create([
                'ip_address'=>($request->ip_address) ? $request->ip_address : $request->ip(),
                'user_agent'=>$request->user_agent,
                'session_id'=>$request->session()->getId(),
                'failed_attempts' => 1
            ]);
        }
        static::allowDefault();

        event('invalid.login');
    }

    public static function isBlocked($login){

        if(!$login->tracker) return false;

        return ($login->tracker->failed_attempts >= static::$maxFailedAttempts);
    }

    public static function passwordVerify($model,$password){

        return password_verify($password,$model->password);
    }


    /**
     * @param $request
     * @param $login
     * @return Tracker
     */
    public static function track($request, $login)
    {
        static::preventDefault();
        $login->update(['last_logged_in'=>Carbon::now()->toDateTimeString()]);

        $tracker = $login->tracker()->create([
            'ip_address'=>($request->ip_address) ? $request->ip_address : $request->ip(),
            'user_agent'=>$request->user_agent,
            'session_id'=>$request->session()->getId(),
            'date_logged_in'=>Carbon::now()->toDateTimeString(),
            'logged_in'=>'1',
        ]);
        static::allowDefault();

        return $tracker;
    }

    public static function updatePassword($login,$new_password,$auth_action,$status = 1)
    {
        static::preventAuthorizing();
        $login->audits()
            ->whereIn('task_route', ['user.account.change-password-plain', 'user.account.change-password', 'user.account.reset-password.hash'])
            ->latest()->take(Util::setting('pw_cyc_threshold'))
            ->get()->each(function ($audit) use ($new_password) {
                $before = json_decode($audit->before);
                $after = json_decode($audit->after);
                if( ($before && password_verify($new_password, $before->password))
                    || ($after && password_verify($new_password, $after->password)) ) {
                    event('pwd.cycle.threshold.exceeded');
                }
            });

        static::setUserAction("Login",$auth_action.": $login->username");
        $result = $login->update([
            'password'=>$new_password,
            'status'=>$status,
        ]);
        static::allowAuthorizing();
        return $result;
    }

    public static function isSysAdmin($login,$group_id=null)
    {
        $group_id = is_null($group_id)?$login->user->group_id:$group_id;
        return ($login->id == 1 and $group_id == 1);
    }

    //Eloquent
    function user(){
        return $this->morphTo();
    }

    function trackers(){
        return $this->hasMany(Tracker::class);
    }

    function tracker(){
        return $this->hasOne(Tracker::class)->latest();
    }

    public function actions(){
        return $this->hasMany('Ndexondeck\Lauditor\Model\Audit', 'login_id');
    }

    public function audits(){
        return $this->morphMany('Ndexondeck\Lauditor\Model\Audit', 'trail');
    }

    public function scopeUserExists($query)
    {
        return $query->where(function ($query) {
                $query->where('user_type', Staff::class)->has('post');
            });
    }

    public function scopeDormant($query) {
        if(!$dormant_period = Util::setting('dormant_period')) return;

        $query->where(function ($query) use ($dormant_period) {
            $query->where('last_logged_in', '<=', Carbon::now()->subDay($dormant_period)->toDateTimeString())
                ->orWhere(function ($query) use ($dormant_period) {
                    $query->whereNull('last_logged_in')
                    ->where('created_at', '<=',Carbon::now()->subDay($dormant_period)->toDateTimeString())
                    ;
                });
        });
    }

    function scopeBranch($q,$branch_id){
        $sql = new Sql();
        $q->whereRaw($sql->escaped("(select count(*) from `staff` where `logins`.`user_type` = '".$sql->morph("App\\Staff")."' and `logins`.`user_id` = `staff`.`id` and `staff`.`branch_id` = $branch_id) >= 1"));
    }

    /**
     * @param $value
     */
    public function setPasswordAttribute($value){
        $this->attributes['password'] = password_hash($value, PASSWORD_BCRYPT, ['cost' => 12]);
        self::$stringPassword = $value;
    }

    public function getUserTypeNameAttribute(){
        $v = explode("\\",$this->attributes['user_type']);
        return end($v);
    }

    public function getOpenSessionAttribute(){
        return empty($this->trackers()->whereNull('date_logged_out')->first());
    }

    public function getFullnameAttribute(){
        return $this->user->fullname;
    }

}
