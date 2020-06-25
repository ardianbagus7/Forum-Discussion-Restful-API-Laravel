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

Route::group(['prefix' => 'v1','middleware'=>'cors'], function () {
    Route::resource('meeting', 'PostController', [
        'except' => ['create', 'edit']
    ]);

    Route::resource('meeting/regristation', 'RegisterController', [
        'only' => ['store', 'destroy']
    ]);

    Route::resource('komentar', 'KomentarController', [
        'except' => ['create', 'edit']
    ]);

    Route::post('user/register', [
        'uses' => 'AuthController@store'
    ]);

    Route::post('user/signin', [
        'uses' => 'AuthController@signin'
    ]);
});
