<?php

//namespace App\Ndexondeck\Lauditor;

use Carbon\Carbon;
use Illuminate\Support\Facades\Request;
use Ndexondeck\Lauditor\Contracts\UtilContract;

class Util implements UtilContract {

    public static function conjunct($array, $reverse=false, $last_glue = "and"){
        if(count($array) > 0){
            $array = array_values($array);
            if($reverse){
                $cursi = array();
                foreach($array as $k=> $v){
                    array_push($cursi,$k);
                }
                $array = $cursi;
            }
            $gs = implode(", ",$array);
            $n = count($array) - 1;
            $gs  = str_replace(", $array[$n]"," $last_glue $array[$n]",$gs);
            return $gs;
        }
        else{
            return "none";
        }
    }

    public static function normalCase($str){

        $str = preg_replace(['/(App\\\)+/','/(:|-|_|\(|\))/'],['',' $1 '],$str);

        $strings = explode(" ",$str);
        $new_strings = [];
        foreach($strings as $string){

            if(strtoupper($string) != $string and ($sc = snake_case($string)) != strtolower($string)){
                $new_strings[] = trim(ucwords(str_replace("_"," ",$sc)));
                continue;
            }

            $new_strings[] = $string;
        }

        return ucfirst(preg_replace(['/ (:|-|_|\(|\)) /','/_/'],['$1',' '],implode(" ",$new_strings)));

    }

    public static function now($format='Y-m-d H:i:s',$signed_seconds=0){

        return date($format,((time() + (env('TIME_OFFSET_HOURS',0) * 60)) + $signed_seconds));

    }


    public static function carbonFromFormat($format, $time){
        //
        return Carbon::createFromFormat(explode(".",$format)[0], explode(".",$time)[0]);
    }

    public static function getIp()
    {
        return Request::get('ip_address',Request::ip());
    }

    public static function getLoginId()
    {

    }

    public static function setting($key)
    {
        //used keys
        //authorization_direct_forwarding [yes,no]
        //max_log_retry [integer]
        //pw_cyc_threshold [integer]
        //dormant_period [integer] in days

    }

    public static function login()
    {
        //
    }

    public static function getPaginate()
    {
        return Request::get('paginate');
    }

}