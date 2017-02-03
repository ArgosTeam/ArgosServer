<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

//Route::get('/user', function (Request $request) {
//    return $request->user();
//})->middleware('scope:check-status,place-orders');


Route::group(['middleware' => 'auth:api'], function () {

    Route::get('/fetch',            'FetchController@fetch');

    // TEMPORARY SINCE I DONT HAVE ACCESS TO DB
    Route::get('/user/setemail', 'UserController@setEmailRequests');


    Route::get('/photo/{id}',       'PhotoController@fetchPhotos');
    Route::get('/search',           'SearchController@selectData');


    /*
    ** Routes regarding photos functions
    */
    Route::post('/photo/upload',    'PhotoController@upload');

    
    /*
    ** Routes regarding users functions
    */
    Route::get('/user/infos', 'UserController@infos');

    /*
    ** Routes regarding events actions
    */
    Route::post('/event/add',           'EventController@add');
    Route::post('/event/join', 'EventController@join');
    Route::post('/event/accept', 'EventController@accept');
//Route::post'/event/refuse', 'EventController@refuse');

    /*
    ** Routes regarding search actions
    */
    Route::get('/search/contacts', 'SearchController@contacts');
    Route::get('/search/events', 'SearchController@events');


    /*
    ** Route regarding friends actions
    */
    Route::post('/friend/add', 'FriendController@add');
    Route::post('/friend/accept', 'FriendController@accept');
    Route::post('/friend/refuse', 'FriendController@refuse');
    Route::post('/friend/delete', 'FriendController@delete');


    /*
    ** Route regarding groups actions
    */
    Route::post('/group/add', 'GroupController@add');
    Route::post('/group/join', 'GroupController@join');
    Route::post('/group/accept', 'GroupController@accept');
    Route::get('/group/infos', 'GroupController@infos');
});