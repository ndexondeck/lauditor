<?php

namespace Ndexondeck\Lauditor\Contracts;


use Illuminate\Database\Eloquent\Model;

interface User
{

    static function getUsername(Model $model);
}