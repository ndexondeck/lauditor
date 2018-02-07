<?php

namespace Ndexondeck\Lauditor\Contracts;

Interface UtilContract{

    /**
     * Glues an array with and as the last glue
     * @param $array
     * @param bool $reverse
     * @param string $last_glue
     * @return mixed
     */
    public static function conjunct($array, $reverse=false, $last_glue = "and");

    /**
     * Converts a camel, pashcal or snake_case to normal text
     * @param string $str
     * @return string $str
     */
    public static function normalCase($str);

    /**
     * Gets datetime plus or minus the given seconds
     * @param string $format
     * @param int $signed_seconds
     * @return string
     */
    public static function now($format='Y-m-d H:i:s', $signed_seconds=0);

    /**
     * Returns carbon object
     * @param $format
     * @param $time
     * @return \Carbon\Carbon
     */
    public static function carbonFromFormat($format, $time);

    /**
     * Gets the IP of the current user request
     * @return string
     */
    public static function getIp();

    /**
     * Gets the Login ID of the current user request
     * @return mixed
     */
    public static function getLoginId();

    /**
     * Gets a global setting value
     * @param $key
     * @return mixed
     */
    public static function setting($key);

    /**
     * returns the Login object
     * @return \App\Login
     */
    public static function login();

    /**
     * @return mixed
     */
    public static function getPaginate();


}