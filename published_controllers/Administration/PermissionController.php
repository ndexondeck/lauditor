<?php

//namespace App\Http\Controllers\Administration;

use App\Group;
use App\Http\Controllers\Controller;
use App\Ndexondeck\Lauditor\Util;
use App\Permission;
use App\Task;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        return Group::enabled()->with('tasks')->latest()->paginate(Util::getPaginate())->toArray();
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
        return Group::enabled()->with('tasks')->findOrFail($id)->toArray();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
        $validator = Validator::make($request->data, ['tasks'=>'required|array']);

        if ( $validator->fails() ) {
            $errors = $validator->messages()->getMessages();
            return $errors;
        }

        $group = Group::enabled()->with('permissions.task')->findOrFail($id);

        $existing_tasks = [];

        //Since we want to authorize this action it means we wont like to use the normal
        //delete all and then insert, style to add permissions, so lets get all
        //existing permissions first and then compare with the new task

        Permission::setAuthAction("Change permission for group : `$group->name`");

        $request_tasks = $request->data['tasks'];

        if(!$group->permissions->isEmpty()){

            foreach($group->permissions as $permission){

                if(in_array($permission->task_id,$request_tasks)){
                    $existing_tasks[] = $permission->task_id;
                    continue;
                }

                $permission::setUserAction("Permission","Deleted Permission : `{$permission->task->name}`");
                $permission->delete();
            }
        }

        $new_tasks = array_diff($request_tasks,$existing_tasks);

        $taskList = Task::whereIn('id',$new_tasks)->lists('name','id');

        foreach($new_tasks as $task_id){
            Permission::setUserAction("Permission","Added Permission : `{$taskList[$task_id]}`");
            $group->permissions()->create(['task_id'=>$task_id]);
        }

        return !empty($new_tasks) ? Permission::isPreventingAuth() : true;
    }

}
