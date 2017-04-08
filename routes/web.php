<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of the routes that are handled
| by your application. Just tell Laravel the URIs it should respond
| to using a Closure or controller method. Build something great!
|
*/

Route::group(['middleware' => 'web'], function () {
    //
    Route::get('/', 'WelcomeController@index');
    
    Route::get('/home', 'HomeController@index');

    Route::post('/register', 'AuthController@registerManual');
    
    Route::get('/check/nickname', 'ToolsController@checkNickname');
});