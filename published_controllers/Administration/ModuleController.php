<?php

//namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Module;
use App\Ndexondeck\Lauditor\Util;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;
use Ndexondeck\Lauditor\Transformers\ModuleWithLazyTaskTransformer;

class ModuleController extends Controller
{
    private $rules = [
        'name' => 'required|unique:modules',
        'description' => 'required',
        'order' => 'required|integer',
        'icon' => 'required',
    ];

    private $rules_update = [
        'name' => 'sometimes|required|unique:modules',
        'description' => 'sometimes|required',
        'visibility' => 'sometimes|required|integer',
        'order' => 'sometimes|required|integer',
        'icon' => 'sometimes|required',
    ];

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Support\Facades\Response
     */
    public function index()
    {
        //
        return Util::jsonSuccess(Module::latest()->paginate(Util::getPaginate())->toArray());
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
        $validator = Validator::make($request->data, $this->rules,['unique'=>'Module :attribute already exist']);

        if ( $validator->fails() ) {
            $errors = $validator->messages()->getMessages();
            return Util::jsonFailure($errors,'validation_failure');
        }

        //create section record
        $module = new Module();

        return Util::jsonSuccess($module->create($request->data)->toArray());
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
        return Util::jsonSuccess(Module::with('tasks')->findOrFail($id)->toArray());
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
        $validator = Validator::make($request->data, $this->rules_update,['unique'=>'Module :attribute already exist']);

        if ( $validator->fails() ) {
            $errors = $validator->messages()->getMessages();
            return Util::jsonFailure($errors,'validation_failure');
        }

        return Util::jsonSuccess(Module::findOrFail($id)->update($request->data));
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
        return Util::jsonSuccess(Module::findOrFail($id)->delete());
    }


    /**
     * Returns modules with tasks
     *
     * @return mixed
     */
    public function tasks()
    {
        //
        return Util::jsonSuccess(Util::paginate(ModuleWithLazyTaskTransformer::transform(Module::with('lazy_tasks')->get())));
    }
}
