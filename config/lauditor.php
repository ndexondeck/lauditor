<?php

return [

    'task-generator' => [
        'excluded_modules' => [],
        'middleware_scopes' => [],
        'base_namespace' => ''
    ],

//    'connections' => []

    'connection_map'=>[
        'mysql'=>'App\\',
        'mysql_school'=>'App\School\\'
    ],

    //Model namespace will be based on connection map default is App
    'audit_user'=> [
        'column' => 'login_id',
        'model' => 'Login',
        'table' => 'logins',
    ],
    'authorization_user'=> [
        'column' => 'staff_id',
        'model' => 'Staff',
        'table' => 'staff',
    ],

    'request_key' => ''

];