<?php

//namespace App\Ndexondeck\Lauditor\Traits;

Trait AuthorizationTrait{

    public static function unsetDependency(){
        static::$auth_dependency = null;
    }
}