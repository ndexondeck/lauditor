<?php

//namespace App\Http\Controllers\Administration;

use App\Authorization;
use App\Classes\Transformers\AuthorizationTransformer;
use App\Exceptions\BAPException;
use App\Http\Controllers\Controller;
use App\Ndexondeck\Lauditor\Util;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class AuthorizationController extends Controller
{
    function __construct()
    {
        $this->middleware('authorize.any', ['except' => ['me','all','destroy','forward','forwardMany']]);
    }

    function index(Request $request){
        if($request->get('can_authorize_any'))
            return Authorization::with('staff','task')->status($request->type)->orderBy('updated_at','desc')->paginate(Util::getPaginate())->toArray();

        return Authorization::with('staff','task')->permitted($request->get('staff'))->status($request->type)->orderBy('updated_at','desc')->paginate(Util::getPaginate())->toArray();
    }

    function me($type=null){
        return Authorization::with('staff','task')->me()->status($type)->orderBy('updated_at','desc')->paginate(Util::getPaginate())->toArray();
    }

    function checked(Request $request,$type=null){

        if(in_array($type,['0','1','pending','forwarded'])) throw new BAPException('checked_authorizations_only');

        return Authorization::with('staff','task')->status($type)
            ->whereStaffId($request->get('staff')->id)->orderBy('updated_at','desc')->paginate(Util::getPaginate())->toArray();
    }

    /**
     * @param null $type
     * @return mixed
     */
    function all($type=null){
        return Authorization::with('staff','task')->status($type)->orderBy('updated_at','desc')->paginate(Util::getPaginate())->toArray();
    }

    /**
     * Details of an authorization request
     * @param $id
     * @param Request $request
     * @return array
     */
    function show($id,Request $request){
        if($request->get('can_authorize_any')) $allow_authorize = true;
        else $allow_authorize = (Authorization::permitted($request->get('staff'))->orderBy('updated_at','desc')->find($id))?true:false;

        return [
            'details'=>AuthorizationTransformer::transform(Authorization::with('audits.login.user','staff','task')->orderBy('updated_at','desc')->findOrFail($id)),
            'allow_authorize'=>$allow_authorize
        ];
    }

    /**
     * This will discard a users authorize action request
     * Can only run when status = 0;
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     * @internal param Request $request
     */
    function destroy($id){
        return Authorization::me()->findOrFail($id)->delete();
    }

    /**
     * Forwards the request to an authorizer
     * Can only run when status = 0
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     * @internal param Request $request
     */
    function forward($id){
        return Authorization::me()->findOrFail($id)->update(['status'=>'1']);
    }

    /**
     * Forwards multiple request to an authorizer
     * Can only run when status = 0
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @internal param $id
     */
    function forwardMany(Request $request){

        $validator = Validator::make($request->data,['selected'=>'required|array'],$messages = [
            'required' => 'The :attribute fields are required.',
            'array' => 'The :attribute fields must be a array of values.',
        ]);

        if($validator->fails()){
            $errors = $validator->messages()->getMessages();
            return json_failure($errors,'validation_failure');
        }

        return Authorization::me()->whereIn('id',$request->data['selected'])->status('0')->update(['status'=>'1']);
    }

    /**
     * Approves a request and therefore executes the audit trails
     * Can only run when status = 1
     * @param $id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws BAPException
     */
    function approve($id,Request $request){

        $data = ['status'=>'2','staff_id'=>$request->get('staff')->id];

        try{
            if($request->get('can_authorize_any'))
                return Authorization::findOrFail($id)->update($data);

            return Authorization::permitted($request->get('staff'))->findOrFail($id)->update($data);
        }
        catch(QueryException $e){

            event('duplicate.resource.auth');
        }

    }

    /**
     * Rejects a request to execute an action
     * Can only run when status = 1
     * @param $id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    function reject($id,Request $request){

        $validator = Validator::make($request->data,  ['comment' => 'required|string']);

        if ( $validator->fails() ) {
            $errors = $validator->messages()->getMessages();
            return json_failure($errors,'validation_failure');
        }

        $data = ['status'=>'3', 'staff_id'=>$request->get('staff')->id, 'comment'=>$request->data['comment']];

        if($request->get('can_authorize_any'))
            return json_success(Authorization::findOrFail($id)->update($data));

        return json_success(Authorization::permitted($request->get('staff'))->findOrFail($id)->update($data));
    }
}
