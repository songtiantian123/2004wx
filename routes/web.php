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
///**php首页 */
Route::get('/info', function () {
    echo phpinfo();
});
Route::get('/hello', function () {
    echo 'hello wx';
});
Route::get('/text','TextController@text');// redis测试
Route::get('/text1','TextController@text1');// 测试1
Route::get('/text2','TextController@text2');// 测试2
Route::post('/text3','TextController@text3');// 测试3


// 微信
//Route::post('/wx','WeiXinController@checkSignature');// 微信接口
Route::match(['get','post'],'/wx','WeiXinController@wxEvent');// 接收事件推送
Route::get('wx/token','WeiXinController@getAccessToken');// 获取access_token
Route::get('/wx/create_menu','WeiXinController@createMenu');// 创建菜单
Route::get('/wx/check','WeiXinController@check');// 验证签名
Route::get('/wx/label','WeiXinController@label');// 创面标签
Route::get('/wx/authorize','WeiXinController@index');// 微信网页授权
Route::get('/wx/auth','WeiXinController@jump');// 微信网页授权

// text 路由分组
Route::prefix('/text')->group(function(){
    Route::get('/guzzle1','TextController@guzzle1');// guzzle get请求
    Route::get('/guzzle2','WeiXinController@guzzle2');// guzzle post请求
    Route::get('/media','WeiXinController@dlMedia');// 下载素材图片
    Route::get('/voice','WeiXinController@vic');// 下载素材音频
    Route::get('/video','WeiXinController@vid');// 下载素材视频
});
