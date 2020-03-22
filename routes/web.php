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

Route::post('/task', 'TaskController@create');

Route::get('/task', 'TaskController@getPriority');

Route::post('/task/addMany', 'TaskController@addMany');

Route::get('/task/{id}', 'TaskController@getStatus');

Route::get('task/averageTimeTaken/{minutes?}', 'TaskController@getAverageProcessingTime');
