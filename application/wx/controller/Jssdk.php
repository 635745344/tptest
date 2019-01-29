<?php
/**
 * Created by PhpStorm.
 * User: Colin
 * Date: 2018/7/16
 * Time: 13:38
 */

namespace app\wx\controller;

use think\Controller;
use common\service\WxService;
use think\Db;
use think\Request;
use think\Log;
use Wechat\WechatReceive;

class Jssdk extends Controller
{
    public function getjssdk()
    {
        header('Access-Control-Allow-Origin:*');
        $request = Request::instance();
        // 创建SDK实例
        $script = &  load_wechat('Script');
        $get_url = input('url');
        P(['jssdk',$get_url]);
        // 获取JsApi使用签名，通常这里只需要传 $ur l参数
        $domain=$request->domain();
        $domain_parts=explode(':',$domain);
        if($domain_parts[count($domain_parts)-1]==80){
            $domain=substr($domain,0,count($domain)-4);
        }
        $url=$domain.'/'.$request->path();
        $options = $script->getJsSign($get_url);
//        $result='<script type="text/javascript" src="http://res.wx.qq.com/open/js/jweixin-1.2.0.js"></script><script  type="text/javascript" >wx.config('.json_encode($options).');</script>';
//        var_dump(json_encode($options)); exit;
//        return $result;
//        echo "\n<script>\n";
//        echo "window.edit_address=function(callback){\ntry{\nWeixinJSBridge.invoke('editAddress'," . json_encode($options) . ",callback);\n}catch(e){callback(e)}\n}\n";
//        echo "</script>\n";
        return json_encode($options);
    }
}