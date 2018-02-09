<?php

//namespace App\Ndexondeck\Lauditor;

use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Ndexondeck\Lauditor\Contracts\UtilContract;
use Ndexondeck\Lauditor\Exceptions\ResponseException;
use Ndexondeck\Lauditor\Model\Audit;

class Util implements UtilContract {

    /**
     * @param $array
     * @param bool $reverse
     * @param string $last_glue
     * @return mixed|string
     */
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

    /**
     * @param string $str
     * @return string
     */
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

    /**
     * @param string $format
     * @param int $signed_seconds
     * @return false|string
     */
    public static function now($format='Y-m-d H:i:s', $signed_seconds=0){

        return date($format,((time() + (env('TIME_OFFSET_HOURS',0) * 60)) + $signed_seconds));

    }


    /**
     * @param $format
     * @param $time
     * @return static
     */
    public static function carbonFromFormat($format, $time){
        //
        return Carbon::createFromFormat(explode(".",$format)[0], explode(".",$time)[0]);
    }

    /**
     * @return mixed
     */
    public static function getIp()
    {
        return Request::get('ip_address',Request::ip());
    }

    /**
     *
     */
    public static function getLoginId()
    {
        //return the login id of the current logged in user
        $login_id = false;

        if(!$login_id){
            if(Audit::isAnonymous()) return Audit::getAnonymity();
            throw new ResponseException('no_auth');
        }
    }

    /**
     * @param $key
     * @return mixed
     */
    public static function setting($key)
    {
        //used keys
        //authorization_direct_forwarding [yes,no]
        //max_log_retry [integer]
        //pw_cyc_threshold [integer]
        //dormant_period [integer] in days

        $array = [
            'authorization_direct_forwarding' => 'yes',
            'max_log_retry' => 5,
            'pw_cyc_threshold' => 3000,
            'dormant_period' => 300
        ];

        return (!empty($array[$key]))? $array[$key]: null;

    }

    /**
     * @param null $connection
     * @return \App\Login
     */
    public static function login($connection=null)
    {
        //

        if($login = Request::get('login')) return $login;

        $namespace = config('ndexondeck.lauditor.connection_map.'.$connection,'App');
        $login_class =  $namespace.'Login';

        return (new $login_class())->findOrFail(static::getLoginId());
    }

    /**
     * @return mixed
     */
    public static function getPaginate()
    {
        return Request::get('paginate');
    }

    /**
     * @param $data
     * @param $code_name
     * @return Response
     */
    public static function jsonFailure($data, $code_name)
    {
        return response()->app(false,$data,$code_name);
    }

    /**
     * @param $data
     * @return Response
     */
    public static function jsonSuccess($data)
    {
        return response()->app(true,$data,'success');
    }

    /**
     * @param $result
     * @param null $total
     * @return array
     */
    public static function paginate($result, $total=null)
    {
        if(!$total)$total = count($result);
        return (new LengthAwarePaginator($result,$total,static::getPaginate()))->toArray();
    }

}