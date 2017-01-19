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

    Route::get('/fetch',            "FetchController@fetch");

    Route::get('/user/{userId}', "UserController@profileRequests");

    // TEMPORARY SINCE I DONT HAVE ACCESS TO DB
    Route::get('/user/setemail', "UserController@setEmailRequests");


    Route::get('/photo/{id}',       "PhotoController@fetchPhotos");
    Route::post('/photo/upload',    "PhotoController@uploadPhoto");


    Route::get('/event/{id}',       "EventController@fetch");
    Route::post('/event',           "EventController@create");

    Route::post('/searchhash',           "SearchController@process");

    Route::get('/search',           "SearchController@selectData");
    
    Route::get('/searchrelatives/{search}', "SearchController@searchRelatives");

    Route::get('/friend/{userId}/{includePending?}', "FriendController@fetchRequests");
    Route::post('/friend/request/create', "FriendController@createRequest");
    Route::post('/friend/request/accept', "FriendController@acceptRequest");
    Route::post('/friend/request/decline', "FriendController@declineRequest");


    Route::get('/group/{groupId}', "GroupController@fetchGroups");
    Route::get('/group/user/{userId}', "GroupController@fetchUsersGroups");
    Route::post('/group/create', "GroupController@createGroup");
    Route::post('/group/request/create', "GroupController@inviteCreate");
    Route::post('/group/request/accept', "GroupController@inviteAccept");
    Route::post('/group/request/decline', "GroupController@inviteDecline");
});