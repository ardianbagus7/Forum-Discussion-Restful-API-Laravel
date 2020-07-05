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

    Route::group(['prefix' => 'user'], function () {

        Route::post('register', [
            'uses' => 'AuthController@store'
        ]);

        Route::post('signin', [
            'uses' => 'AuthController@signin'
        ]);

        Route::post('key', [
            'uses' => 'AuthController@key'
        ]);

        Route::post('profil', [
            'uses' => 'AuthController@profil'
        ]);

        Route::post('verifikasi', [
            'uses' => 'AuthController@verifikasi'
        ]);
        
        Route::post('verifikasi/cek', [
            'uses' => 'AuthController@cekverifikasi'
        ]);

        Route::get('detail', [
            'uses' => 'AuthController@detail'
        ]);

        Route::get('logout', [
            'uses' => 'AuthController@logout'
        ]);
    });
});
