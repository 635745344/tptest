<?php

namespace app\member\controller;

use think\Controller;
use app\common\service\WxService;
use think\Db;
use think\Request;
use think\Log;
use Wechat\WechatReceive;

class Infoauth extends Controller
{
    public function index()
    {
        $this->openid=input('openid');
        if(empty($this->openid)){
            return json(['status'=>0,'info'=>'参数openid不能为空']);
        }
        // 实例微信粉丝接口
        $user = & load_wechat('User');

        // 读取微信粉丝列表
        $info = $user->getUserInfo($this->openid);

        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];

        if ($info['subscribe'] == 1 || $info['subscribe'] == 2) {
            $data = array();
            $data['subscribe'] = $info['subscribe']; //是否关注
            $data['nickname'] = $info['nickname']; //昵称
            $data['sex'] = $info['sex']; //性别
            $data['language'] = $info['language']; //语言
            $data['province'] = $info['province']; //省份
            $data['country'] = $info['country']; //国家
            $data['city'] = $info['city']; //城市
            $data['subscribe_time'] = $info['subscribe_time']; //关注时间
            $data['groupid'] = $info['groupid']; //群组
            $data['headimgurl'] = $info['headimgurl']; //头像
            $userinfo = Db::name('user')->where(['openid' => $this->openid])->find();
            if (!empty($userinfo)) {
                $data['realname'] = $userinfo['name']; //真实名字
                $data['phone'] = $userinfo['phone']; //电话
                $data['address'] = $userinfo['address']; //电话
            }
            return json($data);
        } elseif ($info['subscribe'] == 0) {
            $data = array();
            $info2 = Db::name('user')->where(['openid' => $this->openid])->find();
            if (!empty($info)) {
                $data['nickname'] = $info2['nickname']; //昵称
                $data['sex'] = $info2['sex']; //性别
                $data['language'] = $info2['language']; //语言
                $data['province'] = $info2['province']; //省份
                $data['country'] = $info2['country']; //国家
                $data['city'] = $info2['city']; //城市
                $data['subscribe_time'] = $info2['subscribe_time']; //关注时间
                $data['groupid'] = $info2['groupid']; //群组
                $data['headimgurl'] = $info2['headimgurl']; //头像
                $data['realname'] = $info2['name']; //真实名字
                $data['phone'] = $info2['phone']; //电话
                $data['address'] = $info2['address']; //电话
            }
            $data['subscribe'] = $info['subscribe']; //是否关注
            $data['openid'] = $info['openid']; //是否关注 
            return json($data);
        } else {
            $data = array();
            $data['status'] = 0; //未知错误
            $data['info'] = '未知错误'; //消息提示
            return json($data);
        }

    }

}
