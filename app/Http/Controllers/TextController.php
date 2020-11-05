<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use App\Model\StudentModel;
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
}
