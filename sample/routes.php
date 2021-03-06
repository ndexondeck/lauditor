<?php

//Administration Module
Route::group(['prefix' => 'administration', 'as' => 'administration.', 'middleware' => ['authenticate.staff', 'task.permit'], 'namespace' => 'App\Http\Controllers\Administration'], function() {

    //Permission Sub Module
    Route::group(['prefix'=>'permissions','as' =>'permission.'],function(){
        Route::get('',['as' =>'index','uses'=>'PermissionController@index']);
        Route::get('{group_id}',['as' =>'show','uses'=>'PermissionController@show']);
        Route::put('{group_id}',['as' =>'update','uses'=>'PermissionController@update']);
    });

    //AuthorizerPermission Sub Module
    Route::group(['prefix'=>'authorizers','as' =>'authorizer.'],function(){
        Route::get('',['as' =>'index','uses'=>'AuthorizerPermissionController@index']);
        Route::get('{group_id}',['as' =>'show','uses'=>'AuthorizerPermissionController@show']);
        Route::put('{group_id}',['as' =>'update','uses'=>'AuthorizerPermissionController@update']);
    });

    //Authorization Sub Module
    Route::group(['prefix'=>'authorize','as' =>'authorize.'],function(){
        Route::get('',['as' =>'index','uses'=>'AuthorizationController@index']);
        Route::get('me/{type?}',['as' =>'me','uses'=>'AuthorizationController@me','middleware'=>['notification.listen']]);
        Route::get('all/{type?}',['as' =>'all','uses'=>'AuthorizationController@all','middleware'=>['notification.listen']]);
        Route::get('checked/{type?}',['as' =>'checked','uses'=>'AuthorizationController@checked']);
        Route::get('{id}',['as' =>'show','uses'=>'AuthorizationController@show']);
        Route::delete('{id}', ['as' => 'discard', 'uses' => 'AuthorizationController@destroy']);
        Route::put('{id}/forward', ['as' => 'forward', 'uses' => 'AuthorizationController@forward']);
        Route::put('forward', ['as' => 'forward.multiple', 'uses' => 'AuthorizationController@forwardMany']);
        Route::put('{id}/approve',['as' =>'approve','uses'=>'AuthorizationController@approve']);
        Route::put('{id}/reject',['as' =>'reject','uses'=>'AuthorizationController@reject']);
    });

    //Modules Sub Module
    Route::group(['prefix' => 'modules', 'as' => 'module.'], function() {
        Route::get('', ['as' => 'index', 'uses' => 'ModuleController@index']);
        Route::get('tasks', ['as' => 'tasks', 'uses' => 'ModuleController@tasks']);
        Route::post('', ['as' => 'store', 'uses' => 'ModuleController@store']);
        Route::get('{id}', ['as' => 'show', 'uses' => 'ModuleController@show']);
        Route::put('{id}', ['as' => 'update', 'uses' => 'ModuleController@update']);
        Route::delete('{id}', ['as' => 'destroy', 'uses' => 'ModuleController@destroy']);
    });

    //Tasks Sub Module
    Route::group(['prefix' => 'tasks', 'as' => 'task.'], function() {
        Route::get('', ['as' => 'index', 'uses' => 'TaskController@index']);
        Route::post('', ['as' => 'store', 'uses' => 'TaskController@store']);
        Route::get('{id}', ['as' => 'show', 'uses' => 'TaskController@show']);
        Route::put('{id}', ['as' => 'update', 'uses' => 'TaskController@update']);
        Route::delete('{id}', ['as' => 'destroy', 'uses' => 'TaskController@destroy']);
    });

    //Group Sub Module
    Route::group(['prefix' => 'groups', 'as' => 'group.'], function() {
        Route::get('', ['as' => 'index', 'uses' => 'GroupController@index']);
        Route::post('', ['as' => 'store', 'uses' => 'GroupController@store']);
        Route::get('{id}', ['as' => 'show', 'uses' => 'GroupController@show']);
        Route::put('{id}', ['as' => 'update', 'uses' => 'GroupController@update']);
        Route::get('{id}/staff', ['as' => 'staff', 'uses' => 'GroupController@staff']);
        Route::put('{id}/toggle', ['as' => 'toggle', 'uses' => 'GroupController@toggle']);
    });

});


