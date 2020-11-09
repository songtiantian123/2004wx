<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use App\Model\UserModel;
class WeiXinController extends Controller
{
    public function wxEvent(Request $request){
        $echostr = $request->echostr;
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token = env('WX_TOKEN');
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);

        if ($tmpStr == $signature) { //验证通过
            // 1 接收数据
            $xml_str = file_get_contents("php://input");
            // 记录日志
            file_put_contents('wx_event.log',$xml_str);
//            echo "";
//            die;
            // 2 把xml文本转换为php的对象或数组
            $data = simplexml_load_string($xml_str);
            // 判断该数据包是否是订阅的事件推送
            if (strtolower($data->MsgType) == "event") {
                // 关注
                if (strtolower($data->Event == "subscribe")) {
                    // 回复用户消息  纯文本格式
                    $toUser = $data->FromUserName;
                    $fromUser = $data->ToUserName;
                    $content = '欢迎关注微信公众号1';
                    // 获取用户信息
                    $token = $this->getAccessToken();
                    $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$token."&openid=".$toUser."&lang=zh_CN";
                    file_put_contents('logs.log',$url);
                    $user = file_get_contents($url);
                    $user = json_decode($user,true);
                    $userInfo = [
                        'nickname'=>$user['nickname'],
                        'openid'=>$user['openid'],
                        'sex'=>$user['sex'],
                        'city'=>$user['city'],
                        'province'=>$user['province'],
                        'country'=>$user['country'],
//                        'headimgurl'=>$user['headimgurl'],
                        'subscribe_time'=>$user['subscribe_time'],
                    ];
                    UserModel::insert($userInfo);
                    // 发送消息
                    $result = $this->text($toUser,$fromUser,$content);
                    return $result;
//                    $template = "<xml>
//                            <ToUserName><![CDATA[%s]]></ToUserName>
//                            <FromUserName><![CDATA[%s]]></FromUserName>
//                            <CreateTime>%s</CreateTime>
//                            <MsgType><![CDATA[%s]]></MsgType>
//                            <Content><![CDATA[%s]]></Content>
//                            </xml>";
//                    $info = sprintf($template, $toUser, $fromUser, time(), 'text', $content);
//                    echo $info;
//                    return $info;
                    }
                    // 取消关注
                    if (strtolower($data->Event == 'unsubscribe')) {
                        // 清除用户信息
                    }
                }
            // 被动回复用户文本
            if(strtolower($data->MsgType)=='text'){
                $toUser = $data->FromUserName;
                $fromUser = $data->ToUserName;
                switch ($data->Content){
                    case '签到':
                        $content = '签到成功';
                        $result = $this->text($toUser,$fromUser,$content);
                        return $result;
                        break;
                    case '时间':
                        $content = date('Y-m-d H:i:s',time());
                        $result = $this->text($toUser,$fromUser,$content);
                        return $result;
                        break;
                    case '照片':
                        $content  = "Eexi1YJmQ9NYVn95CoIB1nHHNnjDs1mjBcs2xK7kPkrAS29rTL8d224U1lqzl1TQ"; // 目前 id 是死的
                        $result = $this->picture($toUser,$fromUser,$content);
                        return $result;
                        break;
                    case '语音':
                        $content  = "CIYQ3MwBK3gXJVGVzRgsMgdy1rBjbJ11Krv41r37uQIbKfDmfI6WchQ-ByA0ITVO";
                        $result = $this->voice($toUser,$fromUser,$content);
                        return $result;
                        break;
                    case '视频':
                        $title = '视频测试';
                        $description = '暂无视频描述';
                        $content  = "ANjOfBAbJi8U5VMB5Fep2e4CuT4cXD88JlEnEAAMCh1uQZyBLuDy8R67jYUwhLkp";
                        $result = $this->video($toUser,$fromUser,$content,$title,$description);
                        return $result;
                        break;
                    case '音乐':
                        $title = '音乐测试';
                        $description = '暂无音乐描述';
                        $musicurl = 'https://wx.wyxxx.xyz/%E5%B0%8F.mp3';
                        $content  = "Eexi1YJmQ9NYVn95CoIB1nHHNnjDs1mjBcs2xK7kPkrAS29rTL8d224U1lqzl1TQ";
                        $result = $this->music($toUser,$fromUser,$title,$description,$musicurl,$content);
                        return $result;
                        break;
                    case '图文':
                        $title = '图文测试';
                        $description = '暂无图文描述';
                        $content  = "Eexi1YJmQ9NYVn95CoIB1nHHNnjDs1mjBcs2xK7kPkrAS29rTL8d224U1lqzl1TQ";
                        $url = 'https://www.baidu.com';
                        $result = $this->image_text($toUser,$fromUser,$title,$description,$content,$url);
                        return $result;
                        break;
                    case '天气':
                        $key = 'd570bea572fd4f728f81686371ebbb2b';
                        $uri = "https://devapi.qweather.com/v7/weather/now?location=101010100&key=".$key."&gzip=n";
                        $api = file_get_contents($uri);
                        $api = json_decode($api,true);
                        $content = "天气状态：".$api['now']['text'].'
                        风向：'.$api['now']['windDir'];
                        $result = $this->text($toUser,$fromUser,$content);
                        return $result;
                        break;
                    default:
                        $content = "我表示听不懂";
                        $result = $this->text($toUser,$fromUser,$content);
                        return $result;
                        break;
                }
            }
//            echo '';
        } else {
            return false;
        }
    }
    // 1 回复文本消息
    private function text($toUser,$fromUser,$content){
        $template = "<xml>
                            <ToUserName><![CDATA[%s]]></ToUserName>
                            <FromUserName><![CDATA[%s]]></FromUserName>
                            <CreateTime>%s</CreateTime>
                            <MsgType><![CDATA[%s]]></MsgType>
                            <Content><![CDATA[%s]]></Content>
                            </xml>";
        $info = sprintf($template, $toUser, $fromUser, time(), 'text', $content);
        return $info;
    }
    // 2 回复图片消息
    private function picture($toUser,$fromUser,$content){
        $template = "<xml>
                          <ToUserName><![CDATA[%s]]></ToUserName>
                          <FromUserName><![CDATA[%s]]></FromUserName>
                          <CreateTime>%s</CreateTime>
                          <MsgType><![CDATA[%s]]></MsgType>
                          <Image>
                            <MediaId><![CDATA[%s]]></MediaId>
                          </Image>
                        </xml>";
        $info = sprintf($template, $toUser, $fromUser, time(), 'image', $content);
        return $info;
    }
    // 3 回复语音消息
    private function voice($toUser,$fromUser,$content){
        $template = "<xml>
                          <ToUserName><![CDATA[%s]]></ToUserName>
                          <FromUserName><![CDATA[%s]]></FromUserName>
                          <CreateTime>%s</CreateTime>
                          <MsgType><![CDATA[%s]]></MsgType>
                          <Voice>
                            <MediaId><![CDATA[%s]]></MediaId>
                          </Voice>
                        </xml>";
        $info = sprintf($template, $toUser, $fromUser, time(), 'voice', $content);
        return $info;
    }
    // 4 回复视频消息
    private function video($toUser,$fromUser,$content,$title,$description){
        $template = "<xml>
                              <ToUserName><![CDATA[%s]]></ToUserName>
                              <FromUserName><![CDATA[%s]]></FromUserName>
                              <CreateTime><![CDATA[%s]]></CreateTime>
                              <MsgType><![CDATA[%s]]></MsgType>
                              <Video>
                                <MediaId><![CDATA[%s]]></MediaId>
                                <Title><![CDATA[%s]]></Title>
                                <Description><![CDATA[%s]]></Description>
                              </Video>
                            </xml>";
        $info = sprintf($template, $toUser, $fromUser, time(), 'video', $content,$title,$description);
        return $info;
    }
    // 5 回复音乐消息
    private function music($toUser,$fromUser,$title,$description,$content,$musicurl){
        $template = "<xml>
                  <ToUserName><![CDATA[%s]]></ToUserName>
                  <FromUserName><![CDATA[%s]]></FromUserName>
                  <CreateTime><![CDATA[%s]]></CreateTime>
                  <MsgType><![CDATA[%s]]></MsgType>
                  <Music>
                    <Title><![CDATA[%s]]></Title>
                    <Description><![CDATA[%s]]></Description>
                    <MusicUrl><![CDATA[%s]]></MusicUrl>
                    <HQMusicUrl><![CDATA[%s]]></HQMusicUrl>
                    <ThumbMediaId><![CDATA[%s]]></ThumbMediaId>
                  </Music>
                </xml>";
        $info = sprintf($template, $toUser, $fromUser, time(), 'music', $title,$description,$musicurl,$musicurl,$content);
        return $info;
    }
    // 6 回复图文消息
    private function image_text($toUser,$fromUser,$title,$description,$content,$url){
        $template = "<xml>
                              <ToUserName><![CDATA[%s]]></ToUserName>
                              <FromUserName><![CDATA[%s]]></FromUserName>
                              <CreateTime>%s</CreateTime>
                              <MsgType><![CDATA[%s]]></MsgType>
                              <ArticleCount><![CDATA[%s]]></ArticleCount>
                              <Articles>
                                <item>
                                  <Title><![CDATA[%s]]></Title>
                                  <Description><![CDATA[%s]]></Description>
                                  <PicUrl><![CDATA[%s]]></PicUrl>
                                  <Url><![CDATA[%s]]></Url>
                                </item>
                              </Articles>
                            </xml>";
        $info = sprintf($template, $toUser, $fromUser, time(), 'news', 1 ,$title,$description,$content,$url);
        return $info;
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
        return $token;
    }
}

