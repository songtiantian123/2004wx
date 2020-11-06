<?php

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
/**laravel首页 */
Route::get('/', function () {
    return view('welcome');
});
/**php首页 */
Route::get('/info', function () {
    echo phpinfo();
});
Route::get('/hello', function () {
    echo 'hello wx';
});
Route::get('/text','TextController@text');// redis测试
Route::get('/text1','TextController@text1');// 测试1
Route::get('/wx','WeiXinController@checkSignature');// 微信接口
