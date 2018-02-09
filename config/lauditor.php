<?php

return [
    'transformer' =>  [

        //from foreign_key
        'group_id'=>"groups:name:id",
        'staff_id'=>'staff:fullname:id',
        'active_hour_id'=>"active_hours:name:id",
        'task_id'=>'tasks:name:id',

        //from array mapping
        'enabled'=>[
            0=>'no',
            1=>'yes',
            2=>'pending disable',
            3=>'pending enable',
        ],
        'user_type'=>[
            'App\Staff'=>'Staff',
        ],
        'holiday_login'=>['0'=>'No','1'=>'Yes'],
        'weekend_login'=>['0'=>'No','1'=>'Yes'],

        //from specific array mapping
        'logins.status'=>[
            0=>'Disabled',
            1=>'Enabled',
            2=>'Locked',
            3=>'Reset is required',
        ],

        //from plain to suffixed
        '.cycle'=>'day',
    ],

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

    'request_key' => ''

];