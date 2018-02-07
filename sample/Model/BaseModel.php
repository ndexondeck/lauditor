<?php

//namespace App;

use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    //

    /**
     * @return string date format for mssql
     */
    protected function getDateFormat()
    {
        return env('DATE_FORMAT','Y-m-d H:i:s');
    }
}
