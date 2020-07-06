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

        Route::get('/', [
            'uses' => 'AuthController@allUser'
        ]);

        Route::post('register', [
            'uses' => 'AuthController@store'
        ]);

        Route::post('signin', [
            'uses' => 'AuthController@signin'
        ]);

        Route::get('key', [
            'uses' => 'AuthController@key'
        ]);

        Route::get('key/all', [
            'uses' => 'AuthController@allKey'
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

        Route::post('/{id}', [
            'uses' => 'AuthController@destroy'
        ]);

        //ROLE ADMIN
        Route::get('admin', [
            'uses' => 'AuthController@allAdmin'
        ]);

        Route::post('admin/role', [
            'uses' => 'AuthController@addAdmin'
        ]);
    });
});
