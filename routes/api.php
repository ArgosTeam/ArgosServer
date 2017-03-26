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

    /* POST */
    Route::post('/photo/upload',    'PhotoController@uploadUserImage');
    Route::post('/photo/comment', 'PhotoController@comment');

    /* GET */
    Route::get('/photo/infos', 'PhotoController@infos');
    Route::get('/photo/contacts', 'PhotoController@contacts');
    
    
    /*
    ** Routes regarding users functions
    */
    
    /* POST */
    Route::post('/user/profile_pic', 'UserController@profile_pic');
    
    /* GET */
    Route::get('/user/infos', 'UserController@infos');
    Route::get('/user/photos', 'UserController@photos');
    Route::get('/user/session', 'UserController@session');
    Route::get('/user/contacts', 'UserController@contacts');
    Route::get('/user/events', 'UserController@events');

    /*
    ** Routes regarding events actions
    */

    /* POST */
    Route::post('/event/add', 'EventController@add');
    Route::post('/event/join', 'EventController@join');
    Route::post('/event/accept_join', 'EventController@accept_join');
    Route::post('/event/comment', 'EventController@comment');
    Route::post('/event/profile_pic', 'EventController@profile_pic');
    Route::post('/event/photo/link', 'EventController@link_photo');
    Route::post('/event/invite', 'EventController@invite');
    Route::post('/event/accept_invite', 'EventController@accept_invite');
    Route::post('/event/quit', 'EventController@quit');
    Route::post('/event/edit', 'EventController@edit');
    Route::post('/event/refuse_invite', 'EventController@refuse_invite');

    /* GET */
    Route::get('/event/contacts', 'EventController@contacts');
    Route::get('/event/infos', 'EventController@infos');
    Route::get('/event/photos', 'EventController@photos');

    
    /*
    ** Routes regarding search actions
    */

    /* GET */
    Route::get('/search/events', 'SearchController@events');
    Route::get('/search/photos', 'SearchController@photos');
    Route::get('/search/global', 'SearchController@search');


    /*
    ** Route regarding friends actions
    */

    /* POST */
    Route::post('/friend/add', 'FriendController@add');
    Route::post('/friend/accept', 'FriendController@accept');
    Route::post('/friend/refuse', 'FriendController@refuse');
    Route::post('/friend/delete', 'FriendController@delete');


    /*
    ** Route regarding groups actions
    */

    /* POST */
    Route::post('/group/add', 'GroupController@add');
    Route::post('/group/join', 'GroupController@join');
    Route::post('/group/accept_join', 'GroupController@accept_join');
    Route::post('/group/profile_pic', 'GroupController@profile_pic');
    Route::post('/group/photo/link', 'GroupController@link_photo');
    Route::post('/group/invite', 'GroupController@invite');
    Route::post('/group/accept_invite', 'GroupController@accept_invite');
    Route::post('/group/refuse_invite', 'GroupController@refuse_invite');
    Route::post('/group/comment', 'GroupController@comment');
    Route::post('/group/quit', 'GroupController@quit');
    Route::post('/group/edit', 'GroupController@edit');

    /* GET */
    Route::get('/group/infos', 'GroupController@infos');
    Route::get('/group/photos', 'GroupController@photos');
    Route::get('/group/contacts', 'GroupController@contacts');
    Route::get('/group/events', 'GroupController@events');

    /*
    ** Route regarding Notifications
    */

    /* POST */
    Route::post('/notif/mark_read', 'NotificationController@markAsRead');
    
    /* GET */
    Route::get('/notifs', 'NotificationController@getNotifications');


    /*
    ** Rating
    */
    Route::post('/rate', 'RatingController@rate');

    
    /*
    ** API dynamic tools
    */
    Route::get('/check/nickname', 'ToolsController@checkNickname');
    Route::get('/location/geocoding', 'LocationController@geocoding');

    /*
    ** Routes regarding comments
    */

    /* GET */
    Route::get('/comments/event', 'CommentController@event');
    Route::get('/comments/photo', 'CommentController@photo');
    Route::get('/comments/group', 'CommentController@group');
    
});