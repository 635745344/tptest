<?php
/**
 * Created by PhpStorm.
 * User: Colin
 * Date: 2018/4/20
 * Time: 17:10
 */

namespace app\wx\controller;

use think\Controller;
use common\service\WxService;
use think\Db;
use think\Request;
use think\Log;
use Wechat\WechatReceive;

class OAuth  extends Controller
{
    //获取微信code（获取用户详细资料,记录添加到数据库）
    public function getCode(){
        $request = Request::instance();
        P(input('state'));
        // SDK实例对象
        $oauth = & load_wechat('Oauth');

//        $myfile = fopen("/opt/lampp/htdocs/chuyanjiwebserver/1.txt", "w");
//        fwrite($myfile,'我是getCode');

        // 执行接口操作
        $oauthAccessToken = $oauth->getOauthAccessToken();

        if(empty($oauthAccessToken)){
            exit;
        }

        $openid=$oauthAccessToken['openid'];
        $access_token=$oauthAccessToken['access_token'];

        session('openid',$openid);
        // 获取授权后的用户资料

        // 实例微信粉丝接口
        $user = & load_wechat('User');

        // 读取微信粉丝列表
        $wxInfo = $user->getUserInfo($openid);

        if(empty($wxInfo) || empty($wxInfo['subscribe'])){
            $this->redirect('/mobile/common/zmtyfwptFollow');
        }
        else if(!empty($wxInfo['subscribe']))
        {
            if(!empty($wxInfo))
            {
                $data_user=[
                    'nickname'=>$wxInfo['nickname'],
                    'openid'=>$wxInfo['openid'],
                    'unionid'=>empty($wxInfo['unionid'])?'':$wxInfo['unionid'],
                    'headimgurl'=>$wxInfo['headimgurl'],
                    'subscribe'=>1,
                    'sex'=>$wxInfo['sex'],
                    'province'=>$wxInfo['province'],
                    'city'=>$wxInfo['city'],
                    'country'=>$wxInfo['country'],
                    'last_login_ip'=>$request->ip(),
                    'last_login_time'=>time(),
                    'subscribe_time'=>$wxInfo['subscribe_time'],
                    'update_time'=>time(),
                ];

                $old_user=Db::name('user')->where(['openid'=>$openid])->find();

                if(empty($old_user)) //不存在添加
                {
                    //将数据添加
                    $data_user['login_times']=1;
                    $data_user['status']=1;
                    $data_user['create_time']=time();
                    $user_id=Db::name('user')->insert($data_user);
                }else{   //存在修改
                    $data_user['login_times']=$old_user['login_times']+1;
                    $data_user['subscribe']=1;
                    $user_id=$old_user['id'];
                    Db::name('user')->where(['id'=>$old_user['id']])->update($data_user);
                }
                $user=Db::name('user')->where(['id'=>$user_id])->find();

                session('user',$user);
            }
        }
        $before_url=session('before_url');
        session('before_url',null);
        $this->redirect($before_url);
    }

    //获取微信code（获取用户详细资料,记录不添加到数据库）
    public function getCode2(){
        $request = Request::instance();

        // SDK实例对象
        $oauth = & load_wechat('Oauth');

        // 执行接口操作
        $oauthAccessToken = $oauth->getOauthAccessToken();

        $openid=$oauthAccessToken['openid'];

//        $access_token=$oauthAccessToken['access_token'];
//
//        $wxInfo=[];
//        if(!empty($oauthAccessToken) && $oauthAccessToken['scope']=='snsapi_userinfo')
//        {
//            // 执行接口操作
//            $wxInfo = $oauth->getOauthUserinfo($access_token, $openid);
//        }
//
//        session('wxUserInfo',$openid);

        session('openid',$openid);

        $before_url=session('before_url');

        session('before_url',null);
        $this->redirect($before_url);

    }



}