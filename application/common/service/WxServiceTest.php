<?php

namespace app\common\service;
use think\Request;
use think\Db;
use think\Controller;
use Wechat\WechatMessage;
use think\Cache;
/**
 * 微信数据服务 extends Controller
 */
class WxServiceTest extends Controller
{

    //发送领烟地址
    public function SendTest($touser)
    {
        $eq_code = '100011';
        $new_key = md5(uniqid().rand(100000,999999).$eq_code);
        Cache::set($eq_code.'_key',$new_key,180);
        $url = "http://gzh.zonma.net/mobile/goods/index?eq_code=$eq_code&key=$new_key";
        $content = "出烟机 $eq_code 领烟地址：http://gzh.zonma.net/mobile/goods/index?eq_code=$eq_code&key=$new_key";
        $parame = array(
            "touser"  => $touser,
            "msgtype" => "news",
            "news"    => array(
                "articles" => array(
                    array(
                        "title"=>"点击领烟",
                        "description"=>"该信息3分钟内有效",
                        "url"=>$url,
                        "picurl"=>'',
                    ),
                )
            )
        );
        $Message = & load_wechat('Message');

        $result=$Message->sendCustomMessage($parame);
        var_dump($result);
    }

}
