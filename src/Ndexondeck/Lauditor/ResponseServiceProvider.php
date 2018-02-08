<?php

//namespace App\Ndexondeck\Lauditor;


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

        $factory->macro('app', function ($status,$data,$code_name,$respond=true) use ($factory) {

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

}