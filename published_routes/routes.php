<?php


Route::group(['prefix'=>'lauditor', 'namespace'=>'App\Http\Controllers\Api'],function(){

    Route::get('', 'ApiController@index');

    Route::get('api', 'ApiController@lists');
});



Route::group(['middleware' => ['handshake']], function() {

    //Administration Module for Staff with permission only
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

    });

});


