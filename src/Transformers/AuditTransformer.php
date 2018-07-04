<?php

namespace Ndexondeck\Lauditor\Transformers;

use App\Ndexondeck\Lauditor\Traits\AuditDecoder;
use App\Ndexondeck\Lauditor\Util;
use Ndexondeck\Lauditor\Model\Audit;
use Illuminate\Database\Eloquent\Model;
use Themsaid\Transformers\AbstractTransformer;

class AuditTransformer extends AbstractTransformer
{
    use AuditDecoder;

	private static $store = [];

	private static $audit = [];

	public function transformModel(Model $audit)
	{
		$before = $after = null;

		static::$audit = $audit;

		if($audit->before){
			$before= [];
			foreach(json_decode($audit->before,true) as $key=>$value){
				if(in_array($key,static::$excludes)) continue;

				$result = static::decode($key,$value);
				$before[Util::normalCase($result[0])] = $result[1];
			}
			$before = json_encode($before);
		}

		if($audit->after){
			$after = [];
			foreach(json_decode($audit->after,true) as $key=>$value){
				if(in_array($key,static::$excludes)) continue;

				$result = static::decode($key,$value);
				$after[Util::normalCase($result[0])] = $result[1];
			}
			$after = json_encode($after);
		}

		return ([
				'id' => $audit->id,
				'user_action'=> $audit->user_action,
				'action'=> $audit->action,
				'ip'=> $audit->ip,
				'status'=>$audit->status,
				'before'=>$before,
				'after'=>$after,
				'object_key'=>Audit::transformObjectKey($audit->trail_type."<=>".($audit->before?$audit->before:$audit->after)),
				'created_at'=>$audit->created_at->toDateTimeString(),
				'updated_at'=>$audit->updated_at->toDateTimeString(),
		]);

	}


}