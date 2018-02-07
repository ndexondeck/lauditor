<?php

namespace Ndexondeck\Lauditor\Transformers;

use Illuminate\Database\Eloquent\Model;
use Themsaid\Transformers\AbstractTransformer;

class AuthorizationTransformer extends AbstractTransformer
{

	public function transformModel(Model $authorization)
	{
		$structure = $authorization->toArray();

		if(isset($structure['audits']) and !empty($structure['audits'])){
			$structure['audits'] = AuditTransformer::transform($authorization->audits);
		}

		return $structure;
	}


}