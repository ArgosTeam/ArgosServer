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
    Route::get('/user/{userId}', 'UserController@profileRequests');

    // TEMPORARY SINCE I DONT HAVE ACCESS TO DB
    Route::get('/user/setemail', 'UserController@setEmailRequests');


    Route::get('/photo/{id}',       'PhotoController@fetchPhotos');
    Route::post('/photo/upload',    'PhotoController@uploadPhoto');


    /*
    ** Routes regarding events actions
    */
    //Route::get('/event/{id}',       'EventController@fetch');
    Route::post('/event/add',           'EventController@add');
    Route::post('/event/join', 'EventController@join');
    Route::post('/event/accept', 'EventController@accept');
//Route::post'/event/refuse', 'EventController@refuse');

    Route::get('/search',           'SearchController@selectData');

    /*
    ** Routes regarding search actions
    */
    Route::get('/search/contacts', 'SearchController@contacts');


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
});