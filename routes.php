<?php


Route::group(['prefix'=>'lauditor', 'namespace'=>'App\Http\Controllers\Api'],function(){

    Route::get('', 'ApiController@index');

    Route::get('api', 'ApiController@lists');
});
