<?php

namespace Ndexondeck\Lauditor\Transformers;

use App\Audit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Themsaid\Transformers\AbstractTransformer;

class AuditTransformer extends AbstractTransformer
{
	private static $excludes = ['id','created_at','updated_at','password','key'];

	private static $morphes = ['user_id','endpoint_id'];

	private static $store = [];

	private static $audit = [];

	private static function decode($key, $value)
	{
		$table = static::$audit->table_name;

		if(in_array($table,['settings','biller_settings','consumer_settings']) == "settings" and $key=="value"){
			$x = explode("_settings",$table);
			$config = ((count($x) > 1)?$x[0]:"core");

			$Indexer = new \App\Classes\Indexer($config);

			$set = empty(static::$audit->after)?json_decode(static::$audit->before,true):json_decode(static::$audit->after,true);

			if($setting = $Indexer->findByKey($set['key'])) {

				$valueIni = $value;

				try{
					if($setting['options']){
						if(is_string($setting['options'])){
							if(isset($set[$config.'_id'])) $foreign_key_value = $set[$config.'_id'];
							eval('$list = '.$setting['options'].';');
							$value = $list[$value];
						}
						$value = $setting['options'][$value];
					}
				}catch (\Exception $e){
					$value = $valueIni;
				};
			}
			return [$key, $value];
		}

		if(isset(Audit::$transformer['.'.$key])) return [$key,"$value ".str_plural(Audit::$transformer['.'.$key],$value)];

		if(isset(Audit::$transformer[$table.'.'.$key])){
			$value = isset(Audit::$transformer[$table.'.'.$key][$value])?Audit::$transformer[$table.'.'.$key][$value]:$value;
			return [$key,$value];
		}

		if(in_array($key, static::$morphes)){
			$morph = str_replace("_id","",$key);
			if($value){
				$trail = static::$audit->trail;
				if($trail){

					if(!($trail->$morph and $value == $trail->$morph->id)){

						$trail->$key = $value;
						$after = json_decode(static::$audit->after,true);
						if(!isset($after[$morph."_type"])) return [$morph,null];

						$trail->{$morph."_type"} = $after[$morph."_type"];

						$trail->load([$morph]);
					}

					if(!$name = $trail->$morph->fullname) $name = $trail->$morph->name;

					return [$morph,$name];
				}
			}

			return [$morph,$value];
		}

		if(!isset(Audit::$transformer[$key]) or is_array($value)) return [$key,$value];

		if(is_array(Audit::$transformer[$key])){
			$value = isset(Audit::$transformer[$key][$value])?Audit::$transformer[$key][$value]:$value;
			return [$key,$value];
		}

		$transformer = explode(":",Audit::$transformer[$key]);

		if(!isset(static::$store[$transformer[0]])) static::$store[$transformer[0]] = DB::table($transformer[0])->selectRaw("$transformer[1] aliased,$transformer[2]")->lists('aliased',$transformer[2]);

        if($value and isset(static::$store[$transformer[0]][$value])) return [str_singular($transformer[0]),static::$store[$transformer[0]][$value]];
        elseif($value) {
            static::$audit->delete();
            return [str_singular($transformer[0]),$value.' - is a deleted resource!!!'];
        }
        else return [str_singular($transformer[0]),null];
	}

	public function transformModel(Model $audit)
	{
		$before = $after = null;

		static::$audit = $audit;

		if($audit->before){
			$before= [];
			foreach(json_decode($audit->before,true) as $key=>$value){
				if(in_array($key,static::$excludes)) continue;

				$result = static::decode($key,$value);
				$before[normal_case($result[0])] = $result[1];
			}
			$before = json_encode($before);
		}

		if($audit->after){
			$after = [];
			foreach(json_decode($audit->after,true) as $key=>$value){
				if(in_array($key,static::$excludes)) continue;

				$result = static::decode($key,$value);
				$after[normal_case($result[0])] = $result[1];
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