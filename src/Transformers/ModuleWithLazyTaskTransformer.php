<?php

namespace Ndexondeck\Lauditor\Transformers;

use Illuminate\Database\Eloquent\Model;
use Themsaid\Transformers\AbstractTransformer;

class ModuleWithLazyTaskTransformer extends AbstractTransformer
{

	public function transformModel(Model $module)
	{
		$structure = $module->toArray();
		$structure['tasks'] = $structure['lazy_tasks'];
		unset($structure['lazy_tasks']);

		return $structure;
	}


}