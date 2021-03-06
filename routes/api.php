<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::group(['prefix' => 'v1', 'middleware' => 'cors'], function () {

    Route::get('notif/{id}', [
        'uses' => 'AuthController@notif'
    ]);
    // UPDATE READ 
    Route::post('notif/{id}', [
        'uses' => 'AuthController@notifread'
    ]);

    Route::get('notifall', [
        'uses' => 'AuthController@notifall'
    ]);

    Route::get('post/komentar/{id}', [
        'uses' => 'PostController@komentar'
    ]);

    Route::resource('post', 'PostController', [
        'except' => ['create', 'edit']
    ]);

    Route::post('post/search', [
        'uses' => 'PostController@search'
    ]);

    Route::post('post/filter', [
        'uses' => 'PostController@filter'
    ]);

    Route::resource('post/regristation', 'RegisterController', [
        'only' => ['store', 'destroy']
    ]);

    Route::resource('komentar', 'KomentarController', [
        'except' => ['create', 'edit']
    ]);

    // STORY
    Route::resource('story', 'StoryController', [
        'except' => ['create', 'edit']
    ]);
    
    Route::get('story/komentar/{id}', [
        'uses' => 'StoryController@komentar'
    ]);


    Route::group(['prefix' => 'user'], function () {
        Route::post('register', [
            'uses' => 'AuthController@store'
        ]);

        Route::post('signin', [
            'uses' => 'AuthController@signin'
        ]);


        Route::post('profil', [
            'uses' => 'AuthController@profil'
        ]);


        Route::get('detail', [
            'uses' => 'AuthController@detail'
        ]);

        Route::get('detail/{id}', [
            'uses' => 'AuthController@profilUserLain'
        ]);


        Route::get('logout', [
            'uses' => 'AuthController@logout'
        ]);

        //* ADMIN
        Route::get('/', [
            'uses' => 'AdminController@allUser'
        ]);

        Route::get('key', [
            'uses' => 'AdminController@key'
        ]);

        Route::get('key/all', [
            'uses' => 'AdminController@allKey'
        ]);

        Route::post('verifikasi', [
            'uses' => 'AdminController@verifikasi'
        ]);

        Route::post('verifikasi/cek', [
            'uses' => 'AdminController@cekverifikasi'
        ]);

        Route::post('/{id}', [
            'uses' => 'AdminController@destroy'
        ]);

        //ROLE ADMIN
        Route::get('admin/role', [
            'uses' => 'AdminController@filterUser'
        ]);

        Route::post('admin/role', [
            'uses' => 'AdminController@addAdmin'
        ]);

        Route::get('admin/search', [
            'uses' => 'AdminController@searchUser'
        ]);

        Route::post('admin/feedback', [
            'uses' => 'AdminController@addFeedback'
        ]);

        Route::get('admin/feedback', [
            'uses' => 'AdminController@viewFeedback'
        ]);

        Route::post('admin/bug', [
            'uses' => 'BugController@store'
        ]);

        Route::get('admin/bug', [
            'uses' => 'BugController@index'
        ]);

        Route::post('admin/form', [
            'uses' => 'FormController@store'
        ]);

        Route::get('admin/form', [
            'uses' => 'FormController@index'
        ]);
    });
});
