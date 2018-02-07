<?php

//namespace App\Http\Controllers\Administration;

use App\Group;
use App\Http\Controllers\Controller;
use App\Ndexondeck\Lauditor\Util;
use App\Permission;
use App\Task;
use Illuminate\Http\Request;

use App\Http\Requests;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Support\Facades\Response
     */
    public function index()
    {
        //
        return Util::jsonSuccess(Group::enabled()->with('tasks')->latest()->paginate(Util::getPaginate())->toArray());
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
        return Util::jsonSuccess(Group::enabled()->with('tasks')->findOrFail($id)->toArray());
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
        $validator = Validator::make($request->data, ['tasks'=>'required|array']);

        if ( $validator->fails() ) {
            $errors = $validator->messages()->getMessages();
            return Util::jsonFailure($errors,'validation_failure');
        }

        $group = Group::enabled()->with('permissions.task')->findOrFail($id);

        $existing_tasks = [];

        //Since we want to authorize this action it means we wont like to use the normal
        //delete all and then insert, style to add permissions, so lets get all
        //existing permissions first and then compare with the new task

        Permission::setAuthAction("Change permission for group : `$group->name`");

        $request_tasks = $request->data['tasks'];

        //get tasks dependencies and append to request
        $routes = Task::whereIn('id',$request_tasks)->lists('route')->toArray();

        if(!Cache::has('permission:dependencies')) Artisan::call('dependency:generate');

        $dependencies = json_decode(Cache::get('permission:dependencies'),true);

        $matches = array_intersect($routes,array_keys($dependencies));

        foreach ($matches as $match){
            $request_tasks = array_merge($request_tasks, Task::whereIn('route',$dependencies[$match])->lists('id')->toArray());
        }


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

        return Util::jsonSuccess(!empty($new_tasks) ? Permission::isPreventingAuth() : true);
    }

}
