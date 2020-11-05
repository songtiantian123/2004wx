<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WeiXinController extends Controller
{
    public function checkSignature()
{
    $signature = $_GET["signature"];
    $timestamp = $_GET["timestamp"];
    $nonce = $_GET["nonce"];
	
    $token = 123;
    $tmpArr = array($token, $timestamp, $nonce);
    sort($tmpArr, SORT_STRING);
    $tmpStr = implode( $tmpArr );
    $tmpStr = sha1( $tmpStr );
    
    if( $tmpStr == $signature ){
        return true;
    }else{
        return false;
    }
}
    public function wx(){
        $token = request()->get('echostr');
        if(!empty($token) && $this->checkSignature){
            echo $token;
        }
    }
}
