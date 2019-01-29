<?php
/**
 * Created by PhpStorm.
 * User: Colin
 * Date: 2018/7/4
 * Time: 11:17
 */

namespace app\wx\controller;

use think\Controller;
use think\Db;
use think\Request;
use think\Log;

class Login extends Controller
{
    private $key='b564548249d135fb0075787bbf87bb2b';
    //登录二维码
    public function officialLoginQR()
    {
        // 微信其它工具接口
        $extends = & load_wechat('Extends');
        $scene_id='scan_weixinpclogin_'.md5(uniqid().rand(1000000000,9999999999).rand(1000000000,9999999999));

        cache($scene_id,0);
        $getQRCodeResult = $extends->getQRCode($scene_id,3,3600);
        $url='';
        if(!empty($getQRCodeResult)){
            $url=$getQRCodeResult['url'];
        }
        else{
            return json(['status'=>0,'info'=>'微信请求获取二维码失败！']);
        }
        return json(['status'=>1,'url'=>$url,'account'=>'众码通防伪查询平台','scene_id'=>$scene_id]);
    }
    //是否登录
    public function isLogin($scene_id)
    {
        //检查是否登录
        $isLogin=cache($scene_id);
        if(!empty($isLogin))
        {
            //通知对方服务器登录授权成功
            $userInfo=cache($scene_id.'_user');

            $random=getMd5();
            $timestamp=time();
            $key='b564548249d135fb0075787bbf87bb2b';
//            $userInfo=json_encode($userInfo);
            $token=md5(json_encode($userInfo).$random.$timestamp.$key);

            $data=[
                'random'=>$random,
                'timestamp'=>$timestamp,
                'token'=>$token,
                'userInfo'=>$userInfo
            ];
            $data=urlencode(json_encode($data));
            cache($scene_id,null);
            cache($scene_id.'_user',null);
            return json(['status'=>1,'info'=>'登录成功！','data'=>$data]);
        }else{
            return json(['status'=>0,'info'=>'未登录！']);
        }
    }
    //登录通知接收
    public function loginNotice($openid,$random,$timestamp,$token)
    {
        $validateToken=md5($openid.$random.$timestamp.$this->key);
        if($validateToken!=$token)
        {
            return json(['status'=>0,'info'=>'token验证失败']);
        }

        if($timestamp<time()-3600)
        {
            return json(['status'=>0,'info'=>'登录超时，请重新登录！']);
        }
        return json(['status'=>1,'info'=>'登录成功！']);
    }
    //展示数据
    public function showData($data)
    {
        try{
            $data=json_decode( urldecode($data),true);
            $isValidated=cache('officialLoginQR_'.$data['token']);
            if(!empty($isValidated)){
                return json(['status'=>0,'info'=>'token值已验证过，不可重复使用！']);
            }
        }
        catch (\Exception $e){
            return json(['status'=>0,'info'=>'参数data格式有误！']);
        }

        $validateToken=md5(json_encode($data['userInfo']).$data['random'].$data['timestamp'].$this->key);
        if($validateToken!=$data['token'])
        {
            return json(['status'=>0,'info'=>'token验证失败']);
        }

        if($data['timestamp']<time()-3600)
        {
            return json(['status'=>0,'info'=>'登录超时，请重新登录！']);
        }

        cache('officialLoginQR_'.$data['token'],1);

        return json( $data );
    }
}