<?php
/**
 * Created by PhpStorm.
 * User: Colin
 * Date: 2018/4/23
 * Time: 20:45
 */

namespace app\wx\widget;
use think\Controller;
use common\service\WxService;
use think\Db;
use think\Request;
use think\Log;
use Wechat\WechatReceive;

class Jssdk  extends Controller
{
    public function getJssdk()
    {
        $request = Request::instance();
        // 创建SDK实例
        $script = &  load_wechat('Script');

        // 获取JsApi使用签名，通常这里只需要传 $ur l参数
        $domain=$request->domain();
        $domain_parts=explode(':',$domain);
        if($domain_parts[count($domain_parts)-1]==80){
            $domain=substr($domain,0,count($domain)-4);
        }
        $url=$domain.'/'.$request->path();
        $options = $script->getJsSign($url);

        $result='<script type="text/javascript" src="http://res.wx.qq.com/open/js/jweixin-1.2.0.js"></script><script  type="text/javascript" >wx.config('.json_encode($options).');</script>';

        return $result;
    }
}