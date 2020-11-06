<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WeiXinController extends Controller
{
    public function wx()
{
    $signature = $_GET["signature"];
    $timestamp = $_GET["timestamp"];
    $nonce = $_GET["nonce"];

    $token = env('WX_TOKEN');
    $tmpArr = array($token, $timestamp, $nonce);
    sort($tmpArr, SORT_STRING);
    $tmpStr = implode( $tmpArr );
    $tmpStr = sha1( $tmpStr );

    if( $tmpStr == $signature ){
        echo $_GET['echostr'];
    }else{
        echo '失败';
    }
}
//    public function wx(){
//        $token = request()->get('echostr','');
//        if(!empty($token) && $this->checkSignature()){
//            echo $token;
//        }
//    }
}
