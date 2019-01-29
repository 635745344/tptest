<?php
namespace app\member\controller;

use think\Controller;
use app\common\service\WxService;
use think\Db;
use think\Request;
use think\Log;
use Wechat\WechatReceive;

class Webauth extends Controller
{
    protected function _initialize()
    {
        $openid=session('openid');

        if(empty($openid)){
            //获取众码通云防伪平台微信openid
            $WxService=new WxService();
            $WxService->startOAuth2( md5(uniqid().time()),'snsapi_base');
        }
        $this->openid=$openid;
    }

    public function index()
    {
        /*
        if(!$this->check()){
            echo 'noallow';exit();
        }
        */

        $md5_date = date('Ymd', time());
        $md5_date = $md5_date . 'SHUANGXI';
        $authkey = input('get.authkey');

        $originalToken = urldecode( input('get.originalToken') );
        $EncryptionToken = urlencode(md5($originalToken . 'Qf4Kgk$eiWJf73'));
        $returnURL = htmlspecialchars_decode( input('get.returnURL') );

        $domain = strpos($returnURL, '?');
        if (!empty($domain)) {
            $lianjie = '&';
        } else {
            $lianjie = '?';
        }

        if (md5($md5_date) === $authkey)
        {
            $openid = $this->openid;
            $info = $this->_getmemberInfo($openid);

            if (!is_bool($info) && $info['subscribe'] == 1 || $info['subscribe'] == 2) {
                $returnURL = $returnURL . $lianjie . 'openid=' . $openid . '&subscribe=1&status=success&authkey=' . md5($md5_date) . '&originalToken=' . urlencode($originalToken) . '&EncryptionToken=' . $EncryptionToken;
                echo "<script language='javascript' type='text/javascript'>";
                echo "window.location.href='$returnURL'";
                echo "</script>";
            } elseif (!is_bool($info) && $info['subscribe'] == 0) {
                $returnURL = $returnURL . $lianjie . 'openid=' . $openid . '&subscribe=0&status=success&authkey=' . md5($md5_date) . '&originalToken=' . urlencode($originalToken) . '&EncryptionToken=' . $EncryptionToken;
                echo "<script language='javascript' type='text/javascript'>";
                echo "window.location.href='$returnURL'";
                echo "</script>";
            } else {            	
                $returnURL = $returnURL . $lianjie . 'openid=0&subscribe=0&status=error1&originalToken=' . urlencode($originalToken) . '&EncryptionToken=' . $EncryptionToken;                
                P(date('YmdHis').$returnURL);
                P($info);
                echo "<script language='javascript' type='text/javascript'>";
                echo "window.location.href='$returnURL'";
                echo "</script>";
            }
        } else {        	
            $returnURL = $returnURL . $lianjie . 'openid=0&subscribe=0&status=error2&originalToken=' . urlencode($originalToken) . '&EncryptionToken=' . $EncryptionToken;
//             P(date('YmdHis').$returnURL);
//             P(date('authkey').$authkey);
            echo "<script language='javascript' type='text/javascript'>";
            echo "window.location.href='$returnURL'";
            echo "</script>";
        }

    }


    protected function _getmemberInfo($openid)
    {
        // 实例微信粉丝接口
        $user = & load_wechat('User');

        // 读取微信粉丝列表
        $wxInfo = $user->getUserInfo($openid);

        return $wxInfo;
    }

    public function check(){
        //$this->ajaxReturn($_SERVER,'json');
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        //$ips=C('ips');
        $ips=array('58.62.203.122','127.0.0.1');
        if(in_array($ip, $ips)){
            return true;
        }
        return false;
        
    }

}
