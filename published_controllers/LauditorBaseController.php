<?php

//namespace App\Http\Controllers;

use App\Ndexondeck\Lauditor\Util;
use Ndexondeck\Lauditor\Exceptions\ResponseException;

use Illuminate\Support\Facades\DB;

class LauditorBaseController extends Controller
{
    /**
     * Show the form for editing the specified resource.
     *
     * @param $model
     * @param string $disable_value
     * @return \Illuminate\Support\Facades\Response
     * @throws ResponseException
     */
    protected function toggleState($model,$disable_value="0")
    {
        //
        if($model->enabled == 1 or $model->enabled == 2) return $this->disable($model,$disable_value);

        return $this->enable($model);
    }

    /**
     * @param $model
     * @return \Illuminate\Support\Facades\Response
     * @throws ResponseException
     */
    protected function enable($model){

        $login = \Illuminate\Support\Facades\Request::get('login');

        if(!$login) throw new ResponseException('missing_account');

        $authorize = false;

        if(method_exists($model,"setAuthAction")) {

            $authorize = true;

            $baseClass = class_basename($model);

            if($login->user_type == "App\\Staff"){
                DB::table($model->getTable())->where('id', $model->id)->update(['enabled' => '3']);

                $model::setAuthAction('Enable ' . $baseClass.$this->getNarration($model));
                $model::setUserAction($baseClass, 'Enabled ' . $baseClass);
            }
            else{
                if($model->enabled == 4) throw new ResponseException('enables_only_by_staff');

                $model::preventAuthorizing();
                $model::setUserAction($baseClass, 'Enabled ' . $baseClass.$this->getNarration($model));
            }
        }

        $model->enabled = '1';

        $status = $model->save();

        if($authorize) $model::allowAuthorizing();

        return Util::jsonSuccess(($status)?"active":"inactive");
    }

    protected function disable($model,$disable_value){

        $login = \Illuminate\Support\Facades\Request::get('login');

        if(!$login) throw new ResponseException('missing_account');

        if($login->user_type != "App\\Staff") $disable_value = "0";

        $authorize = false;

        if(method_exists($model,"setAuthAction")) {

            $authorize = true;

            $baseClass = class_basename($model);

            if($login->user_type == "App\\Staff"){
                DB::table($model->getTable())->where('id', $model->id)->update(['enabled' => '2']);

                $model::setAuthAction('Disable ' . $baseClass.$this->getNarration($model));
                $model::setUserAction($baseClass, 'Disabled ' . $baseClass);
            }
            else{
                $model::preventAuthorizing();
                $model::setUserAction($baseClass, 'Disabled ' . $baseClass.$this->getNarration($model));
            }
        }

        $model->enabled = "$disable_value";

        $status = $model->save();

        if($authorize) $model::allowAuthorizing();

        return Util::jsonSuccess(($status)?"inactive":"active");
    }

    private function getNarration($model)
    {
        if(!$name = $model->audit_narration) $name = $model->fullname;
        if(!$name) $name = $model->name;
        if(!$name) return "";

        return (strstr($name,":")?" $name":" : $name");
    }

    protected function validator($validator)
    {
        if ($validator->fails()) {
            throw new ResponseException('validation_failure', $validator->messages()->getMessages());
        }
    }

}
