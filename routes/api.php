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

Route::group(['middleware' => 'auth:api'], function () {

    /*
    ** Main request view rating and last
    */
    Route::get('/fetch',            'FetchController@fetch');

    /*
    ** Route regarding Followers functions
    */
    Route::post('/follow', 'UserController@follow');
    
    /*
    ** Routes regarding photos functions
    */
    Route::post('/photo/upload',    'PhotoController@uploadUserImage');
    Route::get('/photo/macro', 'PhotoController@macro');
    Route::post('/photo/comment', 'PhotoController@comment');

    
    /*
    ** Routes regarding users functions
    */
    Route::get('/user/infos', 'UserController@infos');
    Route::post('/user/profile_pic', 'UserController@profile_pic');
    Route::get('/user/photos', 'UserController@photos');
    Route::get('/user/session', 'UserController@session');

    /*
    ** Routes regarding events actions
    */
    Route::post('/event/add',           'EventController@add');
    Route::post('/event/join', 'EventController@join');
    Route::post('/event/accept_join', 'EventController@accept_join');
    //Route::post'/event/refuse', 'EventController@refuse');
    Route::get('/event/infos', 'EventController@infos');
    Route::post('/event/comment', 'EventController@comment');
    Route::post('/event/profile_pic', 'EventController@profile_pic');
    Route::post('/event/photo/link', 'EventController@link_photo');
    Route::get('/event/photos', 'EventController@photos');
    Route::post('/event/invite', 'EventController@invite');
    Route::post('/event/accept_invite', 'EventController@accept_invite');
    Route::post('/event/groups/link', 'EventController@link_groups');

    /*
    ** Routes regarding search actions
    */
    Route::get('/search/contacts', 'SearchController@contacts');
    Route::get('/search/events', 'SearchController@events');
    Route::get('/search/photos', 'SearchController@photos');


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
    Route::post('/group/accept_join', 'GroupController@accept_join');
    Route::get('/group/infos', 'GroupController@infos');
    Route::post('/group/profile_pic', 'GroupController@profile_pic');
    Route::post('/group/photo/link', 'GroupController@link_photo');
    Route::get('/group/photos', 'GroupController@photos');
    Route::post('/group/invite', 'GroupController@invite');
    Route::post('/group/accept_invite', 'GroupController@accept_invite');
    Route::post('/group/comment', 'GroupController@comment');
    Route::post('/group/groups/link', 'GroupController@link_groups');

    /*
    ** Route regarding Notifications
    */
    Route::get('/notifs', 'NotificationController@getNotifications');
    Route::post('/notif/mark_read', 'NotificationController@markAsRead');
});