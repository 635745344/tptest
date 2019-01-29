<?php
/**
 * Created by PhpStorm.
 * User: Colin
 * Date: 2018/6/13
 * Time: 17:21
 */

namespace app\api\controller;

use think\Controller;
use app\common\service\WxService;

class Test extends Controller
{
    //创建微信url
    public function createWxUrl($returnURL='',$scope='snsapi_base',$authkey='',$originalToken='')
    {

        $oauth = & load_wechat('Oauth');

        $result = $oauth->getOauthRedirect('http://gzh.zonma.net/api/test/getUserinfo?callback='.urlencode($returnURL), time(), $scope);

        if($result===FALSE){
            return 'false_createWxUrl';
        }else{
            $this->redirect($result);
        }

    }

    //创建微信url
    public function getUserinfo($returnURL)
    {
        $returnURL=urldecode($returnURL);

        $oauth = & load_wechat('Oauth');

        $result = $oauth->getOauthAccessToken();

        $openid=$result['openid'];
        $access_token=$result['access_token'];

        if(!empty($result) && $result['scope']=='snsapi_userinfo'){
            $result = $oauth->getOauthUserinfo($access_token, $openid);
        }
        
        if($result===FALSE){
            return 'false_getUserinfo';
        }else{
            return json($result);
        }
    }

    //判断没有关注获取用户信息结果
    public function noFollowUser()
    {
        // 实例微信粉丝接口
        $user = & load_wechat('User');

        // 读取微信粉丝列表oRD28w3O430Mi0GBWjf6-_zqrItg
        $info = $user->getUserInfo('oRD28w54QGONgXIh6pKL7p-liIro');

        var_dump($info);
    }

    public function sleeptime()
    {
        $start_time=time();
        sleep(5);
        echo time()-$start_time;
    }

    public function logout(){
        session('openid',null);
        session('user',null);

    }

}