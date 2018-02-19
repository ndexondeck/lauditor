<?php

namespace Ndexondeck\Lauditor\Middleware;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

class LogAfterRequest {

    public function handle($request, Closure $next)
    {
        return $next($request);
    }

    public function terminate($request, $response)
    {
        if(env('APP_ENV') == 'local'){
            if($route = Route::currentRouteName()){

                $persist = true;

                $code = $response->getStatusCode();

                $v = Cache::get('apr:'.$route,[]);

                if($v) $v = json_decode($v,true);

                if($persist){

                    $headers = [];
                    foreach ($request->header() as $key=>$header){
                        if(!in_array($key,['host','content-length'])) $headers[$key] = implode(";",$header);
                    }

                    $headers2 = [];
                    foreach ($response->headers as $key=>$header){
                        if(!in_array($key,['host','content-length','date','cache-control'])) $headers2[$key] = implode(";",$header);
                    }

                    $v[$code] = [
                        'url'=>$request->url(),
                        'request_header'=>$headers,
                        'request'=>$request->all(),
                        'response_header'=>$headers2,
                        'response'=>$response->getContent()
                    ];

                    Cache::forever('apr:'.$route,json_encode($v));
                }

            }
        }

    }

}