<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
class WeiXinController extends Controller
{
    public function wxEvent(Request $request)
    {
        $echostr = $request->echostr;
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token = env('WX_TOKEN');
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);

        if ($tmpStr == $signature) {
            if ($tmpStr == $signature) { //验证通过
                // 1 接收数据
                $xml_str = file_get_contents("php://input");
                // 记录日志
//            file_put_contents('wx_event.log',$xml_str);
//            echo "";
//            die;
                // 2 把xml文本转换为php的对象或数组
                $data = simplexml_load_string($xml_str, 'SimpleXMLElement', LIBXML_NOCDATA);
                // 判断该数据包是否是订阅的事件推送
                if (strtolower($data->MsgType) == "event") {
                    // 关注
                    if (strtolower($data->Event == "subscribe")) {
                        // 回复用户消息  纯文本格式
                        $toUser = $data->FormUserName;
                        $formUser = $data->ToUserName;
                        $msgType = 'text';
                        $content = '欢迎关注微信公众号';
                        $template = "<xml>
                                    <ToUserName><![CDATA[%s]]></ToUserName>
                                    <FromUserName><![CDATA[%s]]></FromUserName>
                                    <CreateTime>%s</CreateTime>
                                    <MsgType><![CDATA[%s]]></MsgType>
                                    <Content><![CDATA[%s]]></Content>
                                    </xml>";
                            $info = sprintf($template, $toUser, $formUser, time(), $msgType, $content);
                            return $info;
                        }
                    }
                    // 取消关注
                    if (strtolower($data->Event == 'unsubscribe')) {
                        // 清除用户信息
                    }
                }
                /*
                // 天气
                if(strtolower($data->MsgType)=="text"){
                    switch ($data->Content){
                        case "天气":
                            $categoty = 1;
                            $key = "d570bea572fd4f728f81686371ebbb2b";
                            $url = "https://devapi.qweather.com/v7/weather/now?location101010100&key=".$key."&gzip=n";
                            $api = file_get_contents($url);
                            $api = json_decode($api,true);
                            $content = "天气状态:" .$api['now']['text'].'
                            风向：'.$api['now']['windDir'];
                            break;
                        default:
                            $categoty = 1;
                            $content = "和";
                            break;
                    }
                    $toUser = $data->FormUserName;
                    $formUser = $data->ToUserName;
                    if($categoty==1){
                        $template = "<xml>
                                        <ToUserName><![CDATA[%s]]></ToUserName>
                                        <FromUserName><![CDATA[%s]]></FromUserName>
                                        <CreateTime>%s</CreateTime>
                                        <MsgType><![CDATA[%s]]></MsgType>
                                        <Content><![CDATA[%s]]></Content>
                                        </xml>";
                        $info = sprintf($template,$toUser,$formUser,time(),$content);
                        return $info;
                    }
                }
                */
            } else {
                return false;
            }
        }
    }

    /**
     * 获取access_token
     */
    public function getAccessToken()
    {
        $key = 'wx:access_token';
        // 检测是否有token
        $token = Redis::get($key);
        if ($token) {
            echo '有缓存';
            echo '</br>';
        } else {
            echo '无缓存';
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . env('WX_APPID') . "&secret=" . env('WX_APPSECRET') . "";
            $response = file_get_contents($url);
            $data = json_decode($response, true);
            $token = $data['access_token'];
            // 保存至redis中 时间未3600
            Redis::set($key, $token);
            Redis::expire($key, 3600);
        }
        echo 'access_token' . $token;
    }
}

