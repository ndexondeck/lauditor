<?php
namespace Ndexondeck\Lauditor\Exceptions;


use Illuminate\Support\Facades\Request;

class ResponseException extends \Exception
{
    public $errors = [];

    function __construct($code='unknown',$errors=null)
    {

        if($errors){

            $this->errors = $errors;

            Request::instance()->attributes->add(['errors' => $errors ]);
        }

        if(!$statuses = config("ndexondeck.statuses.".$code)) $statuses = config("statuses.unknown");

        parent::__construct($statuses[1],$statuses[0]);
    }

    /**
     * @return array|null
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @return bool
     */
    public function hasErrors()
    {
        return isset($this->errors);
    }

    /**
     * @return string
     */
    public function getFirstError()
    {
        return reset($this->errors);
    }

}