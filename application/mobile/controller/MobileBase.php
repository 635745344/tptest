<?php
/**
 * Created by PhpStorm.
 * User: Colin
 * Date: 2018/4/18
 * Time: 13:34
 */

namespace app\mobile\controller;

use think\Controller;
use think\Session;
use think\Db;
use think\Request;
use app\common\service\WxService;
use think\Cache;

class MobileBase  extends Controller
{
    protected function _initialize()
    {
        $request = Request::instance();
        //判断是否关注双喜公众号
//        $shuangxi_is_subscribe=session('shuangxi_is_subscribe');
//        $shuangxi_openid=session('shuangxi_openid');
//        if(empty($shuangxi_openid))
//        {
//            $shuangxi_openid=input('openid');
//            $originalToken=input('originalToken');
//            if(!empty($originalToken) && $originalToken==session('originalToken'))
//            {
//                session('shuangxi_openid',$shuangxi_openid);
//                session('shuangxi_is_subscribe',input('subscribe'));
//                if(empty($shuangxi_is_subscribe)){
//                    $this->redirect('/mobile/Common/wxFollow');
//                }
//            }else{
//                $WxService = new WxService();
//                $WxService->getOpenid();
//            }
//        }

//        $myfile = fopen("/opt/lampp/htdocs/chuyanjiwebserver/1.txt", "w");
//        fwrite($myfile,'sdf');


//        $userInfo = Db::name('user')->where('id=187')->find();
//        $openid = $userInfo['openid'];
//        session('openid',$openid);
//        session('user',$userInfo);

        $openid=session('openid');
        $user=session('user');

        if(empty($openid)||empty($user)){

            $eq_code = input('eq_code');

            $eq = DB::name('equipment')->where(['code'=>$eq_code])->find();
            $group_set = Db::name('equipment_group')->where(['id'=>$eq['group_id']])->find();
            //2018-11-01 某几部要先关注公众号

            Cache::set('set_follow',$eq_code,60);
            //获取众码通云防伪平台微信openid
            $state = md5(uniqid().time());
            $WxService=new WxService();

            //follow = 1 关注
            if($group_set['follow']==1)
            {
                $WxService->startOAuth($state,'snsapi_base'); //需要关注
            }else{
                $WxService->startOAuth2($state,'snsapi_base'); //不关注直接获取openid
            }

        }else{
            session('openid',$openid);
        }

    }
}