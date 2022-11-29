<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/



Route::get('/email', function () {
    return view('email.register-school');
});

Route::get('/usuario/thinkific', 'UserThinkificController@getUsers');
Route::post('/usuario/thinkific', 'UserThinkificController@storeUser');
Route::put('/usuario/thinkific/{userid}', 'UserThinkificController@editUser');
Route::delete('/usuario/thinkific/{userid}', 'UserThinkificController@deleteUser');

Route::post('/sync/usuario/', 'UserThinkificController@syncUser');








