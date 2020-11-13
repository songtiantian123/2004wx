<?php

namespace App\Http\Controllers;

use App\Model\UserOfficialModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Model\UserModel;
use GuzzleHttp\Client;
use App\Model\MediaModel;
class WeiXinController extends Controller
{
    /**
     * 微信授权
     */
    public function index(){
        $redirect ='http://2004.liliqin.xyz/'.'wx/auth';
        $appId = "wxb5ccb15a85957e7b";
        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=$appId&redirect_uri=$redirect&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect";
        return redirect($url);
    }
    /**
     * 微信授权后登录
     * @return bool
     */
    public function jump(){
        $code = $_GET['code'];
        $appId = env("WX_APPID");
//            echo $appId;die;
        $secret = env('WX_APPSECRET');
//            echo $secret;die;
        $url="https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$appId."&secret=".$secret."&code=".$code."&grant_type=authorization_code";
//            echo $url;die;
        $xml = file_get_contents($url);
        $xml_code = json_decode($xml,true);
        if(isset($xml_code['errcode'])){
            if($xml_code['errcode']==40163){
                return "验证码已失效";
            }
        }
        $access_token = $xml_code['access_token'];
        $openid = $xml_code['openid'];
        // 拉取用户信息
        $api="https://api.weixin.qq.com/sns/userinfo?access_token=".$access_token."&openid=".$openid."&lang=zh_CN";
        $user = file_get_contents($api);
        $userInfo = json_decode($user,true);
//            dd($userInfo);
        if($userInfo){
            return redirect('/');
        }
    }
    // 验证请求是否来自微信
    private function check(){
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token = env('WX_TOKEN');
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
    // 处理推送事件
    public function wxEvent(Request $request){
        // 验签

//        if($this->check()==false){
//            // TODO 验签不通过
//            exit;
//        }
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token = env('WX_TOKEN');
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );

        // 获取到微信推送过来的post数据
        $xml_str = file_get_contents("php://input");
        // 记录日志
        file_put_contents('wx_event.log',$xml_str);
        // 2 把xml文本转换为php的对象或数组
        $data = simplexml_load_string($xml_str);
        if ($tmpStr == $signature) {
            $toUser = $data->FromUserName;
            $fromUser = $data->ToUserName;
            $token = $this->getAccessToken();
            //将用户的会话记录 入库
            if (!empty($data)) {
                $toUser = $data->FromUserName;
                $fromUser = $data->ToUserName;
                // 将记录存入库中
                $msg_type = $data->MsgType;
                switch ($msg_type) {
                    case 'video':// 视频
                        $this->videohandler($data);
                        break;
                    case 'voice':// 语音
                        $this->voiceheadler($data);
                        break;
                    case 'text':// 文本
                        $this->textheadler($data);
                        break;
                }
            }
            // 关注 并入库
            if (strtolower($data->MsgType) == "event") {
                // 关注
                if (strtolower($data->Event == "subscribe")) {
                    // 回复用户消息  纯文本格式
                    $toUser = $data->FromUserName;
                    $fromUser = $data->ToUserName;
                    $content = '欢迎关注微信公众号1';
                    // 获取用户信息
                    $token = $this->getAccessToken();
                    $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=" . $token . "&openid=" . $toUser . "&lang=zh_CN";
                    file_put_contents('logs.log', $url);
                    $user = file_get_contents($url);
                    $user = json_decode($user, true);
                    $subscribe = UserModel::where('openid', $user['openid'])->first();
                    // 关注后存入数据库 已经关注 提示欢迎回来
                    if (!empty($subscribe)) {
                        $content = '欢迎回来';
                    } else {
                        $userInfo = [
                            'nickname' => $user['nickname'],
                            'openid' => $user['openid'],
                            'sex' => $user['sex'],
                            'city' => $user['city'],
                            'province' => $user['province'],
                            'country' => $user['country'],
                            'headimgurl' => $user['headimgurl'],
                            'subscribe_time' => $user['subscribe_time'],
                        ];
                        UserModel::insert($userInfo);
                    }
                    // 发送消息
                    $result = $this->text($toUser, $fromUser, $content);
                    return $result;
                }
                // 取消关注
                if (strtolower($data->Event == 'unsubscribe')) {
                    // 清除用户信息
                }
            }
            // 被动回复用户文本
            if (strtolower($data->MsgType) == 'text') {
                $toUser = $data->FromUserName;
                $fromUser = $data->ToUserName;
                switch ($data->Content) {
                    case '签到':
                        $content = '签到成功';
                        $result = $this->text($toUser, $fromUser, $content);
                        return $result;
                        break;
                    case '时间':
                        $content = date('Y-m-d H:i:s', time());
                        $result = $this->text($toUser, $fromUser, $content);
                        return $result;
                        break;
                    case '照片':
                        $content = "Eexi1YJmQ9NYVn95CoIB1nHHNnjDs1mjBcs2xK7kPkrAS29rTL8d224U1lqzl1TQ"; // 目前 id 是死的
                        $result = $this->picture($toUser, $fromUser, $content);
                        return $result;
                        break;
                    case '语音':
                        $content = "CIYQ3MwBK3gXJVGVzRgsMgdy1rBjbJ11Krv41r37uQIbKfDmfI6WchQ-ByA0ITVO";
                        $result = $this->voice($toUser, $fromUser, $content);
                        return $result;
                        break;
                    case '视频':
                        $title = '视频测试';
                        $description = '暂无视频描述';
                        $content = "ANjOfBAbJi8U5VMB5Fep2e4CuT4cXD88JlEnEAAMCh1uQZyBLuDy8R67jYUwhLkp";
                        $result = $this->video($toUser, $fromUser, $content, $title, $description);
                        return $result;
                        break;
                    case '音乐':
                        $title = '音乐测试';
                        $description = '暂无音乐描述';
                        $musicurl = 'https://wx.wyxxx.xyz/%E5%B0%8F.mp3';
                        $content = "Eexi1YJmQ9NYVn95CoIB1nHHNnjDs1mjBcs2xK7kPkrAS29rTL8d224U1lqzl1TQ";
                        $result = $this->music($toUser, $fromUser, $title, $description, $musicurl, $content);
                        return $result;
                        break;
                    case '图文':
                        $title = '图文测试';
                        $description = '暂无图文描述';
                        $content = "Eexi1YJmQ9NYVn95CoIB1nHHNnjDs1mjBcs2xK7kPkrAS29rTL8d224U1lqzl1TQ";
                        $url = 'https://www.baidu.com';
                        $result = $this->image_text($toUser, $fromUser, $title, $description, $content, $url);
                        return $result;
                        break;
                    case '天气':
                        $key = 'd570bea572fd4f728f81686371ebbb2b';
                        $uri = "https://devapi.qweather.com/v7/weather/now?location=101010100&key=" . $key . "&gzip=n";
                        $api = file_get_contents($uri);
                        $api = json_decode($api, true);
                        $content = "天气状态：" . $api['now']['text'] . '
                        风向：' . $api['now']['windDir'];
                        $result = $this->text($toUser, $fromUser, $content);
                        return $result;
                        break;
                    default:
                        $content = "我表示听不懂";
                        $result = $this->text($toUser, $fromUser, $content);
                        return $result;
                        break;
                }
            }
            //将素材存入数据库
            if (strtolower($data->MsgType) == 'image') {
                // 下载素材
                $token = $this->getAccessToken();
                $media_id = $data->MediaId;
                $url = 'https://api.weixin.qq.com/cgi-bin/media/get?access_token=' . $token . '&media_id=' . $media_id;
                $img = file_get_contents($url);
                $media_path = 'image/2.jpg';
                $res = file_put_contents($media_path,$img);
                if ($res) {
                    $media = MediaModel::where('media_url', $data->PicUrl)->first();
                    if (empty($media)) {
                        $res = [
                            'media_url' => $data->PicUrl,
                            'media_type' => (string)$data->MsgType,
                            'add_time' => time(),
                            'openid' => $data->FromUserName,
                            'msg_id' => (string)$data->MsgId,
                            'media_id' => $data->MediaId,
                            'media_path' => $media_path,
                        ];
                        MediaModel::insert($res);
                        $content = '已记录素材库中';
                    } else {
                        $content = '素材库已存在';
                    }
                    // 发送消息
                    $result = $this->text($toUser, $fromUser, $content);
                    return $result;
                }
            }
            // 点击一级菜单
            if($data->Event=='CLICK'){
                // 天气
                if($data->EventKey=='HEBEI_WEATHER'){
                    $key = 'd570bea572fd4f728f81686371ebbb2b';
                    $url = "https://devapi.qweather.com/v7/weather/now?location=101010100&key=".$key."&gzip=n";
                    $callback = file_get_contents($url.'/wx/turing?info=weather');
                    $result = $this->text($toUser,$fromUser,$callback);
                    Log::info($result);
                    return $result;
                }
                // 签到
                if($data->EventKey=='sign'){
                    $key = 'sign'.date('Y-m-d',time());
                    $content = '签到成功';
                    $user_sign = Redis::zrange($key,0,-1);
                    if(in_array((string)$toUser,$user_sign)){
                        $content = '已签到';
                    }else{
                        Redis::zadd($key,time(),(string)$toUser);
                    }
                    $result = $this->text($toUser,$fromUser,$content);
                    return $result;
                }
            }
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

    // 新增临时素材
    public function media_insert(Request $request){
        // 类型
        $type = $request->type;
        // 获取access_token
        $token = $this->getAccessToken();
        // 接口
        $api = "https://api.weixin.qq.com/cgi-bin/media/upload?access_token=".$token."&type=".$type;
        // 素材
        $fileurl = $request->fileurl;
        $this->media_add($api,$fileurl);
    }
    // 调用接口临时素材
    private function media_add($api,$fileurl){
        $curl = curl_init();
        curl_setopt($curl,CURLOPT_SAFE_UPLOAD,true);

        $data = ['media'    => new \CURLFile($fileurl)];

        curl_setopt($curl,CURLOPT_URL,$api);
        // curl_setopt($curl, CURLOPT_SSL_VERIFYPEER,false);
        // curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,false);
        curl_setopt($curl,CURLOPT_POST,1);
        curl_setopt($curl,CURLOPT_POSTFIELDS,$data);
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($curl,CURLOPT_USERAGENT,"TEST");
        $result = curl_exec($curl);
        print_r(json_decode($result,true));
    }
    /**
     * guzzle get请求
     * 获取access_token
     */
    public function getAccessToken()
    {
//        echo __METHOD__;die;
        $key = 'wx:access_token';
        // 检测是否有token
        $token = Redis::get($key);
        if ($token)
        {

        } else {
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . env('WX_APPID') . "&secret=" . env('WX_APPSECRET') . "";
            // 使用guzzle发起get请求
            $client = new Client();// 实例化 客户端
            $response = $client->request('GET',$url,['verify'=>false]);// 发起请求闭关响应
            $json_str = $response->getBody(); // 服务器的响应数据
            $data = json_decode($json_str, true);
            $token = $data['access_token'];
            // 保存至redis中 时间未3600
            Redis::set($key, $token);
            Redis::expire($key, 3600);
        }
        return $token;
    }

    /**
     * 上传素材 post
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function guzzle2(){
        $access_token = $this->getAccessToken();
//        echo $access_token;die;
        $type = 'image';
        $url = "https://api.weixin.qq.com/cgi-bin/media/upload?access_token=".$access_token."&type=".$type;
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

    /**
     * 创建菜单
     */
    public function createMenu(){
        $access_token = $this->getAccessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".$access_token;
        $menu = [
            "button"=>[
                [
                    'type' => 'view',
                    'name'=> '商城',
                    'url'=> 'https://tt.liliqin.xyz/',
                ],
                [
                    'name'=>'菜单',
                    'sub_button'=> [
                        [
                            'type'=>'view',
                            'name'=>'百度',
                            'url'=> 'https://www.baidu.com/',
                        ],
                        [
                        'type'=>'click',
                        'name'=>'签到',
                        'key'=> 'sign',
                    ],
                        [
                            'type' => 'click',
                            'name'=> '天气',
                            'key'=> 'HEBEI_WEATHER',
                        ],
                    ]
                ]
            ]
        ];
//        $a=json_encode($menu,JSON_UNESCAPED_UNICODE);
//        dd($a);
        // 使用guzzle发起post请求
        $client = new Client();// 实例化客户端
        $response = $client->request('POST',$url,[
            'verify'=>false,
            'body'=>json_encode($menu,JSON_UNESCAPED_UNICODE),
        ]);// 发起请求闭关响应
        $data = $response->getBody();
        echo $data;
    }
    /**
     * 视频
     */
    protected function videohandler($data){
        $toUser = $data->FromUserName;
        $fromUser = $data->ToUserName;
        // 下载
        $token = $this->getAccessToken();
        $media_id = $data->MediaId;
        $url="https://api.weixin.qq.com/cgi-bin/media/get?access_token=".$token."&media_id=".$media_id;
        $image = file_get_contents($url);
        $path = "video/jin.mp4";
        $res = file_put_contents($path,$image);
        if($res){
            $video=MediaModel::where('media_id',$data->MedisId)->first();
            if(empty($video)){
                // 入库
                $data=[
                    'media_url' => $data->PicUrl,
                    'add_time'=>$data->CreateTime,
                    'media_type'=>$data->MsgType,
                    'openid' => $data->FromUserName,
                    'media_id'=>$data->MediaId,
                    'msg_id'=>$data->MsgId,
                    'media_path'=>$path,
                ];
                MediaModel::insert($data);
                $content = "视频已入库";
            }else{
                $content = '此视频已存在';
            }
            $result = $this->text($toUser,$fromUser,$content);
            return $result;
        }
    }
    /**
     * 音频
     */
    protected function voiceheadler($data){
        $toUser= $data->FromUserName;
        $fromUser = $data->ToUserName;
        $token = $this->getAccessToken();
        $media_id = $data->MediaId;
        $url="https://api.weixin.qq.com/cgi-bin/media/get?access_token=".$token."&media_id=".$media_id;
        $image = file_get_contents($url);
        $path = "voice/2.mp3";
        $res = file_put_contents($path,$image);
        if($res){
            $voice=MediaModel::where('media_id',$data->MedisId)->first();
            if(empty($voice)){
                $data=[
                    'media_url' => $data->PicUrl,
                    'add_time'=>$data->CreateTime,
                    'media_type'=>$data->MsgType,
                    'openid' => $data->FromUserName,
                    'media_id'=>$data->MediaId,
                    'msg_id'=>$data->MsgId,
                    'media_path'=>$path,
                ];
                MediaModel::insert($data);
                $content = "音频已入库";
            }else{
                $content = "此音频已存在";
            }
            $result = $this->text($toUser,$fromUser,$content);
            return $result;
        }
    }
    /**
     * 记录text文本
     */
    protected function textheadler($data){
        $data=[
            'add_time'=>$data->CreateTime,
            'media_type'=>$data->MsgType,
            'openid' => $data->FromUserName,
            'media_id'=>$data->MediaId,
            'msg_id'=>$data->MsgId,
        ];
        MediaModel::insert($data);
    }
    /**
     * 图片
     */
//    public function imageheadler($data){
//        // 下载素材
//        $token = $this->getAccessToken();
//        $media_id = $data->MediaId;
//        $url = 'https://api.weixin.qq.com/cgi-bin/media/get?access_token='.$token.'&media_id='.$media_id;
//        $img = file_get_contents($url);
////        $rand = mt_rand('Y-m-d H:i:s').'jpg';
//        $media_path = 'image/2.jpg';
//        $res = file_put_contents($media_path,$img);
//        if($res){
//            // TODO 保存成功
//        }else{
//            // TODO 保存失败
//        }
//        // 入库
//        $info = [
//            'media_id'=>$media_id,
//            'openid'=>$data->FromUserName,
//            'media_type'=>$data->MsgType,
//            'msg_id'=>$data->MsgId,
//            'add_time'=>$data->CreateTime,
//            'media_path'=>$media_path,
//        ];
//        MediaModel::insertGetId($info);
//    }
    /**
     * 和风天气
     */
    public function weather(){
        $url = 'http://api.k780.com:88/?app=weather.future&weaid=heze&&appkey=10003&sign=b59bc3ef6191eb9f747dd4e83c99f2a4&format=json';
        $weather = file_get_contents($url);
        $weather = json_decode($weather,true);
        if($weather['success']){
            $content = "";
            foreach($weather['result']as $v){
                $content .='日期'.$v['days'].$v['week'].'当日温度：'.$v['temperature'].'天气：'.$v['weather'].'风向：'.$v['wind'];
            }
        }
        Log::info('==='.$content);
        return $content;
    }
    /**
     * 扫码关注
     */
    public function subscribe(){}
    /**
     * 本地下载多媒体素材图片
     */
    public function dlMedia(){
        $token = $this->getAccessToken();
        $media_id = '0rvcLix_Dow95KZubfe2gmtClwG5P9hRXWeDNU6Kvl-_Ah4TJyUAMkxU4sOpC02Z';
        $url = 'https://api.weixin.qq.com/cgi-bin/media/get?access_token='.$token.'&media_id='.$media_id;
        $img = file_get_contents($url);
        $path = 'image/2.jpg';
        $res = file_put_contents($path,$img);
        var_dump($res);
    }
    /**
     * 本地下载音频
     */
    public function vic(){
        // 下载
        $token = $this->getAccessToken();
        $media_id = "1vUtnqbL3CX26jfeHVx1r2ZmgJAxZzaD6oxZj-sf5URHATyLNUUd48OLZQmnS9TY";
        $url="https://api.weixin.qq.com/cgi-bin/media/get?access_token=".$token."&media_id=".$media_id;
        $image = file_get_contents($url);
        $path = "voice/2.mp4";
        $res = file_put_contents($path,$image);
        var_dump($res);

    }
    /**
     * 本地视频
     */
    public function vid(){
        $token = $this->getAccessToken();
        $media_id = "1vUtnqbL3CX26jfeHVx1r2ZmgJAxZzaD6oxZj-sf5URHATyLNUUd48OLZQmnS9TY";
        $url="https://api.weixin.qq.com/cgi-bin/media/get?access_token=".$token."&media_id=".$media_id;
        $image = file_get_contents($url);
        $path = "video/2.mp4";
        $res = file_put_contents($path,$image);
        dd($res);
    }

    /**
     * 微信网页授权
     * @return \Illuminate\Auth\Access\Response|void
     */

//    public function index(Request $request){
//        $code = $request->code;
//        // appid公众号
//        $appid = env('WX_APPID');
//        // secret
//        $secret = env('WX_APPSECRET');
//        // 通过code环球网页授权access_token
//        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$appid."&secret=".$secret."&code=".$code."&grant_type=authorization_code";
//        echo $url;die;
//        $url_xml = file_get_contents($url);
//        $xml = json_decode($url_xml,true);
//        if(isset($xm['errcode'])){
//            if($xml['errcode']==40163){
//                return "验证码已失效";
//            }
//            $access_toekn = $xml['access_token'];
//            $openid = $xml['openid'];
//            // 拉取用户信息
//            $api = "https://api.weixin.qq.com/sns/userinfo?access_token=".$access_toekn."&openid=".$openid."&lang=zh_CN";
//            $user_info_obj = file_get_contents($api);
//            $user = json_decode($user_info_obj,true);
//        }
//    }
}

