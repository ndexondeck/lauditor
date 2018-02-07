<?php

//namespace App\Http\Controllers\Administration;

use App\Group;
use App\GroupTask;
use App\Http\Controllers\Controller;
use App\Ndexondeck\Lauditor\Util;
use App\PermissionAuthorizer;
use App\Task;
use Illuminate\Http\Request;

use App\Http\Requests;
use Illuminate\Support\Facades\Validator;

class AuthorizerPermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Support\Facades\Response
     */
    public function index()
    {
        //
        return Util::jsonSuccess(Group::enabled()->with('authorizer_tasks')->latest()->paginate(Util::getPaginate())->toArray());
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Support\Facades\Response
     */
    public function show($id)
    {
        //
        return Util::jsonSuccess(Group::enabled()->with('authorizer_tasks')->findOrFail($id)->toArray());
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
        $validator = Validator::make($request->data, ['authorizer_tasks'=>'required|array']);

        if ( $validator->fails() ) {
            $errors = $validator->messages()->getMessages();
            return Util::jsonFailure($errors,'validation_failure');
        }

        $group = Group::enabled()->with('authorizers.task')->findOrFail($id);

        $existing_tasks = [];

        //Since we want to authorize this action it means we wont like to use the normal
        //delete all and then insert, style to add permissions, so lets get all
        //existing permissions first and then compare with the new task

        PermissionAuthorizer::setAuthAction("Change authorizer permission for group : `$group->name`");

        if(!$group->authorizers->isEmpty()){

            foreach($group->authorizers as $authorizer){

                if(in_array($authorizer->task_id,$request->data['authorizer_tasks'])){
                    $existing_tasks[] = $authorizer->task_id;
                    continue;
                }

                $authorizer::setUserAction("PermissionAuthorizer","Deleted Authorizer : `{$authorizer->task->name}`");
                $authorizer->delete();
            }
        }

        $new_tasks = array_diff($request->data['authorizer_tasks'],$existing_tasks);

        $taskList = Task::whereIn('id',$new_tasks)->lists('name','id');

        foreach($new_tasks as $task_id){
            PermissionAuthorizer::setUserAction("PermissionAuthorizer","Added Authorizer Permission : `{$taskList[$task_id]}`");
            $group->authorizers()->create(['task_id'=>$task_id]);
        }

        return Util::jsonSuccess(!empty($new_tasks) ? PermissionAuthorizer::isPreventingAuth() : true);
    }

}
