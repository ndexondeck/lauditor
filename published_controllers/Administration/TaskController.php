<?php

//namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Ndexondeck\Lauditor\Util;
use App\Task;
use Illuminate\Http\Request;

use App\Http\Requests;
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller
{
    private $rules = [
        'name' => 'required',
        'route' => 'required|unique:tasks',
        'description' => 'required',
        'order' => 'required|integer',
        'module_id' => 'exists:modules,id',
        'parent_task_id' => 'exists:tasks,id',
        'task_type' => 'in:0,1,2,3'
    ];

    private $rules_update = [
        'name' => 'sometimes|required',
        'route' => 'sometimes|required',
        'description' => 'sometimes|required',
        'order' => 'sometimes|required|integer',
        'module_id' => 'exists:modules,id',
        'parent_task_id' => 'exists:tasks,id',
        'task_type' => 'in:0,1,2,3'
    ];

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Support\Facades\Response
     */
    public function index()
    {
        //
        return Util::jsonSuccess(Task::latest()->paginate(Util::getPaginate())->toArray());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Support\Facades\Response
     */
    public function store(Request $request)
    {
        //
        $validator = Validator::make($request->data, $this->rules,['unique'=>'Task :attribute already exist']);

        if ( $validator->fails() ) {
            $errors = $validator->messages()->getMessages();
            return Util::jsonFailure($errors,'validation_failure');
        }

        //create section record
        $task = new Task();

        return Util::jsonSuccess($task->create($request->data)->toArray());
    }

    /**
     * Show details of a module.
     *
     * @param $id
     * @return \Illuminate\Support\Facades\Response
     */
    public function show($id)
    {
        //
        return Util::jsonSuccess(Task::with('module')->findOrFail($id)->toArray());
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Support\Facades\Response
     */
    public function update(Request $request, $id)
    {
        //
        $validator = Validator::make($request->data, $this->rules_update,['unique'=>'Task :attribute already exist']);

        if ( $validator->fails() ) {
            $errors = $validator->messages()->getMessages();
            return Util::jsonFailure($errors,'validation_failure');
        }

        return Util::jsonSuccess(Task::findOrFail($id)->update($request->data));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Support\Facades\Response
     */
    public function destroy($id)
    {
        //
        return Util::jsonSuccess(Task::findOrFail($id)->delete());
    }
}
