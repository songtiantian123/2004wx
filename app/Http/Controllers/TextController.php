<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use App\Model\StudentModel;
use GuzzleHttp\Client;
class TextController extends Controller
{
    public function text(){
        // 用模型查询数据库
          $res = StudentModel::get();
        //用DB查询数据库
//        $student  = DB::table('student')->get();
//        dd($student);

//          $key = 'wx2004';
//          Redis::set($key,time());// redis设置
//          echo Redis::get($key);// redis获取
    }
    /** 测试1*/
    public function text1(){
        echo '测试1';
    }
    /** 测试2*/
    public function text2(){
        print_r($_GET);
    }
    /** 测试3*/
    public function text3(){
        echo '<pre>';print_r($_POST);echo '</pre>';
    }
    /**
     * guzzle get请求
     */
    public function guzzle1(){
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . env('WX_APPID') . "&secret=" . env('WX_APPSECRET') . "";
        // 使用guzzle发起get请求
        $client = new Client();// 实例化 客户端
        $response = $client->request('GET',$url,['verify'=>false]);// 发起请求闭关响应
        $json_str = $response->getBody(); // 服务器的响应数据
        echo $json_str;
    }
    /**
     * guzzle post请求
     */
    public function guzzle2(){
        $access_token = "";
        $type = 'image';
        $url = "https://api.weixin.qq.com/cgi-bin/media/upload?access_token='.$access_token.'&type=".$type;
        $client = new Client();// 实例化 客户端
        $response = $client->request('POST',$url,[
            'verify'=>false,
            'multipart'=>[
                [
                    'name'=>'media',
                    'contents'=>fopen('IMG_0156.JPG','r')
                    ],// 上传的文件路径
            ],
        ]);// 发起请求闭关响应
        $data = $response->getBody();
        echo $data;
    }
}
