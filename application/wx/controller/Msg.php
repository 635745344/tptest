<?php
/**
 * Created by PhpStorm.
 * User: Colin
 * Date: 2018/4/20
 * Time: 16:33
 */

namespace app\wx\controller;

use think\Controller;
use app\common\service\WxService;
use think\Db;
use think\Request;
use think\Log;
use Wechat\WechatReceive;
use think\Session;
use think\Cache;

class Msg extends Controller
{
    //微信openid
    protected $openid;

    //微信消息对象
    protected $wechat;

    //接收消息
    public function receiveMsg()
    {
        $this->wechat = &load_wechat('Receive');
        /* 验证接口 */
        if ($this->wechat->valid() === FALSE) {
            // 接口验证错误，记录错误日志
            // 退出程序
            exit($this->wechat->errMsg);
        }
        P($this->wechat->getRev());
        /* 获取粉丝的openid */
        $this->openid = $this->wechat->getRev()->getRevFrom();
        $this->WxService = new \app\common\service\WxService();

        /* 分别执行对应类型的操作 */
        switch ($this->wechat->getRev()->getRevType()) {
            // 文本类型处理
            case \Wechat\WechatReceive::MSGTYPE_TEXT:
                $keys = $this->wechat->getRevContent();
                return _keys($keys);
            // 事件类型处理
            case \Wechat\WechatReceive::MSGTYPE_EVENT:
                $event = $this->wechat->getRevEvent();
                return $this->_event(strtolower($event['event']));
            // 图片类型处理
            case \Wechat\WechatReceive::MSGTYPE_IMAGE:
                return _image();
            // 发送位置类的处理
            case \Wechat\WechatReceive::MSGTYPE_LOCATION:
                return _location();
            // 其它类型的处理，比如卡卷领取、卡卷转赠
            default:
                return _default();
        }
    }
    //事件推送
    private function _event($type)
    {
        if($type=='subscribe') //关注
        {
            $this->_subscribe();
        }
        else if($type=='unsubscribe') //取消关注
        {
            $old_user=Db::name('user')->where(['openid'=>$this->openid])->find();
            Db::name('user')->where(['id'=>$old_user['id']])->update(['subscribe'=>0]);
        }

        $this->_scan(); //带场景扫描二维码

    }
    //带场景扫描二维码
    private function _scan()
    {
        try
        {
            $sceneId = $this->wechat->getRevSceneId();
            $openid = $this->wechat->getRevFrom();

            if(!empty($sceneId))
            {
                $sceneIdParts=explode('_',$sceneId);
                if($sceneIdParts[0]=='scan')
                {
                    //微信PC端利用公众号登录二维码
                    if($sceneIdParts[1]=="weixinpclogin")
                    {
                        $userInfo=Db::name('user')->where(['openid'=>$openid])->field('openid,nickname,sex,province,city,country,headimgurl,unionid')->find();
                        cache($sceneId.'_user',$userInfo);
                        cache($sceneId,1);

                        $this->wechat->text('登录成功！');
                        exit($this->wechat->reply());
                    }
                }
            }

        }catch (\Exception $e){

        }
    }
    private function _subscribe()
    {
        $user = & load_wechat('User');
        $wxInfo = $user->getUserInfo($this->openid);

        if(!empty($wxInfo))
        {
            $request = Request::instance();

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

            $old_user=Db::name('user')->where(['openid'=>$this->openid])->find();
            if(empty($old_user)) //不存在添加
            {
                //将数据添加
                $data_user['login_times']=1;
                $data_user['status']=1;
                $data_user['create_time']=time();
                Db::name('user')->insert($data_user);
            }else{   //存在修改
                $data_user['login_times']=$old_user['login_times']+1;
                Db::name('user')->where(['id'=>$old_user['id']])->update($data_user);
            }
            //首次扫出烟机的码把所属出烟机推送
            $eq_code = Cache::get('set_follow');
            P(['eq_code'=>$eq_code,'k'=>'MSG']);
            if(!empty($eq_code)){
                $this->WxService->sendChuanjiUrl($eq_code,$wxInfo['openid']);
            }
        }
    }
    public function demo()
    {
        return view();
    }
}