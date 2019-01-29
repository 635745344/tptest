<?php

namespace app\mobile\controller;

use think\Controller;
use think\Session;
use think\Db;
use think\Request;
use app\mobile\controller\MobileBase;

class Common extends Controller
{
    //展示关注二维码
    public function wxFollow()
    {
        $shop_id=input('shop_id');
        if(empty($shop_id)){
            $shop_id=0;
        }
        $timestamp=time();
        $sign = md5(config('join_shop_key').$timestamp);

        $src=urlencode( url('/mobile/RetailerManage/joinShop?shop_id='.$shop_id.'&timestamp='.$timestamp.'&sign='.$sign,'',false,true));

        return view('',['followQC'=>'/mobile/RetailerManage/getQR?src='.$src]);
    }
    //众码通云防伪平台微信关注页
    public function zmtyfwptFollow()
    {
        return view();
    }

    //信息提示页
    public function prompt()
    {
        $status=input('status');
        return view('',['status'=>$status]);
    }

    //信息提示页
    public function prompt2()
    {
        $msg=input('msg');
        return view('',['msg'=>$msg]);
    }

}