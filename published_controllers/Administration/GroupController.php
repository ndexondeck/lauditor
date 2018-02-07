<?php

//namespace App\Http\Controllers\Administration;

use App\Ndexondeck\Lauditor\Util;
use Ndexondeck\Lauditor\Exceptions\ResponseException;
use App\Group;
use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;

use App\Http\Requests;
use Illuminate\Support\Facades\Validator;

class GroupController extends BaseController
{
    private $rules = [
        'name' => 'required|string|unique:groups,name',
        'active_hour_id' => 'required|exists:active_hours,id',
        'holiday_login' => 'required|in:0,1',
        'weekend_login' => 'required|in:0,1',
    ];

    private $rules_update = [
        'name' => 'required|string|unique:groups,name',
        'active_hour_id' => 'sometimes|required|exists:active_hours,id',
        'holiday_login' => 'sometimes|required|in:0,1',
        'weekend_login' => 'sometimes|required|in:0,1',
    ];

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Support\Facades\Response
     */
    public function index()
    {
        return Util::jsonSuccess(Group::with('active_hour')->latest()->paginate(Util::getPaginate())->toArray());
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Support\Facades\Response
     * @throws ResponseException
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->data, $this->rules, ['unique'=>'Group already exist']);
        $this->validator($validator);

        //create section record
        Group::setAuthAction("Create User Group : ".$request->data['name']);
        return Util::jsonSuccess(Group::create($request->data)->toArray());
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Support\Facades\Response
     */
    public function show($id)
    {
        return Util::jsonSuccess(Group::with('tasks','authorizer_tasks')->findOrFail($id)->toArray());
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $id
     * @return \Illuminate\Support\Facades\Response
     * @throws ResponseException
     */
    public function update(Request $request, $id)
    {
        $this->rules_update['name'] .=  ",".$id;
        $validator = Validator::make($request->data, $this->rules_update, ['unique'=>'Group already exist']);
        $this->validator($validator);

        $group = Group::findOrFail($id);
        Group::setAuthAction("Update User Group : ".$group->name);
        return Util::jsonSuccess($group->update($request->data));
    }

    /**
     * Get tasks of a group
     * @param $id
     * @return \Illuminate\Support\Facades\Response
     */
    public function staff($id)
    {
        return Util::jsonSuccess(Group::findOrFail($id)->staff()->paginate(Util::getPaginate())->toArray());
    }

    /**
     * Get tasks of a group
     * @param $id
     *
     * @return \Illuminate\Support\Facades\Response
     */
    public function toggle($id)
    {
        return parent::toggleState(Group::findOrFail($id));
    }

}
