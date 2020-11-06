<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
class WeiXinController extends Controller
{
    public function checkSignature(Request $request)
    {
        $echostr=$request->echostr;
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token = env('WX_TOKEN');
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );

        if( $tmpStr == $signature ){
            echo $echostr;
        }else{
            return false;
        }
    }

    /**
     * 处理推送事件
     */
    public function wxEvent(Request $request)
    {
        $echostr=$request->echostr;
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token = env('WX_TOKEN');
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );

        if( $tmpStr == $signature ){ //验证通过
            // 1 接收数据
            $xml_str = file_get_contents("php://input");
            // 记录日志
            file_put_contents('wx_event.log',$xml_str);
            echo "";
            die;
            // 2 把xml文本转换为php的对象或数组
            $data = simplexml_load_string($xml_str,'SimpleXMLElement',LTBXML_NOCDATA);
            // 3 获取接收到的数据信息
            $formUsername = $data->FormUserName;
            $toUsername = $data->ToUserName;
            $keyword = trim($data->Content);
            $time = time();
            // 4 使用PHP代码发送微信信息
            if(!empty($keyword)){
                $msgType = "text";
                $contentStr = "谢谢关注";// 回复内容
                $resultStr = sprintf($formUsername,$toUsername,$time,$msgType,$contentStr);
                echo $resultStr;
            }else{
                echo "Input something...";
            }
        }else{
            echo '';
        }
    }
    /**
     * 获取access_token
     */
    public function getAccessToken(){
        $key = 'wx:access_token';
        // 检测是否有token
        $token = Redis::get($key);
        if($token){
            echo '有缓存';echo '</br>';
        }else{
            echo '无缓存';
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".env('WX_APPID')."&secret=".env('WX_APPSECRET')."";
            $response = file_get_contents($url);
            $data = json_decode($response,true);
            $token = $data['access_token'];
            // 保存至redis中 时间未3600
            Redis::set($key,$token);
            Redis::expire($key,3600);
        }
        echo 'access_token'.$token;
    }
}
