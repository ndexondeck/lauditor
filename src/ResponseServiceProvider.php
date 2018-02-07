<?php

namespace Ndexondeck\Lauditor;

use App\Task;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Routing\ResponseFactory;

class ResponseServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @param  ResponseFactory  $factory
     * @return void
     */
    public function boot(ResponseFactory $factory)
    {
        $eventLogger = $this->makeLog();

        $factory->macro(config('ndexondeck.lauditor.response','app'), function ($status,$data,$code_name,$respond=true) use ($factory,$eventLogger) {

            if(is_array($code_name)){
                $statuses = $code_name;
            }
            else{
                if(!$statuses = config("ndexondeck.statuses.".$code_name)) $statuses = config("ndexondeck.statuses.unknown");
            }

            $response = [
                'status'=>$status,
                'status_code'=>$statuses[0],
                'message'=>$statuses[1],
            ];

            if($status){
                if(is_bool($data)) $data = ($data)?"true":"false";

                $response['data'] = $data;
                $response['validation'] = null;

                $eventLogger($status,$statuses[1],$data);

                return $factory->make($response);
            }

            if(!is_null($data) and is_array($data)){
                foreach($data as $k=>$v){
                    if(!is_array($v)) continue;
                    $data[$k] = reset($v);
                }
            }

            $response['data'] = null;
            $response['validation'] = $data;

            $eventLogger($status,$statuses[1],$data);

            if($respond) return $factory->make($response);

            return $response;

        });
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }


    function makeLog(){
        return function ($status,$message,$data){

            if($logEvent = Request::get('logEvent')){

                extract($logEvent);

                /**Extraction of variables
                 *
                 * @var $type
                 * @var $category
                 * @var $user_action
                 * @var $login
                 * @var $custom_request
                 *
                 */

                if($type == "successful" and !$status) return false;
                if($type == "failed" and $status) return false;

                try {
                    $request_data = (!empty($custom_request))?$custom_request:Request::get('data');

                    $affected = [];

                    $details = "";

                    foreach($request_data as $key=>$value){
                        if(ends_with($key,'_id')){
                            $model_name = studly_case(str_replace("_id","",$key));
                            $class = "App\\$model_name";
                            if(class_exists($class)){
                                if($model = $class::find($value)){
                                    if($model->fullname) $affected[] = normal_case($model_name).": ".$model->fullname;
                                    elseif($model->name) $affected[] = normal_case($model_name).": ".$model->name;
                                    elseif($model->label) $affected[] = normal_case($model_name).": ".$model->label;
                                    elseif($model->title) $affected[] = normal_case($model_name).": ".$model->title;

                                }
                            }
                        }
                    }

                    if(!empty($affected)) $details = conjunct($affected);

                    $message = ['message' => $message];

                    if(!$status and $data) $message = array_merge($message,$data);

                    $task = Task::whereRoute(Route::currentRouteName())->first();
                    $task_route = $task_name = "";
                    if($task){
                        $task_route = $task->route;
                        $task_name = $task->name;
                    }

                    $login = ($login)?$login:loginSoft();

                    if($login){
                        $user = str_replace("App\\","",$login->user_type)." - ".$login->username;
                        if(!$user_action and $task) $user_action = "$user ({$task->name})";
                        elseif(!$user_action) $user_action = $user;

                        LogEvent::create([
                            'task_route' => $task_route,
                            'task_name' => $task_name,
                            'login' => $login->username,
                            'user_name' => $login->user->fullname,
                            'user_action' => $user_action,
                            'type' => $category,
                            'status' => ($status) ? "1" : "0",
                            'request_data' => $request_data ,
                            'response_data' => $message,
                            'details' => $details,
                            'uri' => Request::url(),
                            'ip_address' => getIp()
                        ]);
                    }

                    if($category == "fraud"){
                        event(new FraudAlert('Fraud Alert: '.$task_name,((strlen($user_action) < 50)?$user_action.'. Details: '.$details:$user_action)));
                    }
                }catch (QueryException $e){}
            }
        };
    }

//    function strictResponseTypeMapping(){
//
//        //We will limit the scope to staff because thats where these kind of request can come from
//        //at least for now
//        return function($data){
////            if(Request::get('staff')){
////                if(preg_match('/biller\..*.\.store/',Route::currentRouteName())){
////                    if(is_object($data)) return "true";
////                    if(is_string($data)) return $data;
////                }
////            }
//
//            return $data;
//        };
//    }

}