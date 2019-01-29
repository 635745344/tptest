<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件

use \think\Request;
use think\Db;
use Wechat\Loader;

//数据结果返回
function r($status, $msg, $other = array())
{
    $result = ['status' => $status, 'info' => $msg];
    if ($other) {
        foreach ($other as $key => $value) {
            $result[$key] = $value;
        }
    }
    return $result;
}

//分页数据返回
function r_page($status, $data, $count = 0)
{
    $result = ['status' => $status, 'data' => $data, 'count' => $count];
    return json($result);
}

/**
 * 基础增删改操作结果返回
 * @return [type] [description]
 */
function r_oper($data)
{
    $data = $data == 0 ? 1 : 1;
    $request = Request::instance();
    switch ($request->action()) {
        case 'add':
            $msg = '添加';
            break;
        case 'edit':
            $msg = '编辑';
            break;
        case 'del':
        case 'dels':
            $msg = '删除';
            break;
        case 'status':
            if ($request->get('status')) {
                $msg = '启用';
            } else {
                $msg = '禁用';
            }
            break;
        default:
            $msg = '操作';
            break;
    }
    if ($data) {
        $status = 1;
        $msg .= '成功！';
    } else {
        $status = 0;
        $msg .= '失败！';
    }
    return r($status, $msg);
}

//返回查询数据集合
function select_result($data)
{
    if ($data) {
        return json_encode(array('status' => 1, 'msg' => '', 'data' => $data));
    } else {
        return json_encode(array('status' => 0, 'msg' => '亲，已经没有数据了！', 'data' => array()));
    }
}

//返回对应js文件
function get_js()
{
    $request = Request::instance();
    $src = strtolower('static/js/' . $request->module() . '/' . $request->controller() . '/' . $request->action() . '.js');
    $update_time = filemtime($src);
    $src = $src . '?v=' . $update_time;
    echo "<script type='text/javascript' src='/$src' ></script>";
}

/*
 * HTTP GET Request
 */
function get($url, $param = null)
{
    if ($param != null) {
        $query = http_build_query($param);
        $url = $url . '?' . $query;
    }
    $ch = curl_init();
    if (stripos($url, "https://") !== false) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    }

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $content = curl_exec($ch);
    $status = curl_getinfo($ch);
    curl_close($ch);
    if (intval($status["http_code"]) == 200) {
        return $content;
    } else {
//        echo $status["http_code"];
        return false;
    }
}

/*
 * HTTP POST Request
 */
function post($url, $params)
{
    $ch = curl_init();
    if (stripos($url, "https://") !== false) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    }

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    $content = curl_exec($ch);
    $status = curl_getinfo($ch);
    curl_close($ch);
    if (intval($status["http_code"]) == 200) {
        return $content;
    } else {
        echo $status["http_code"];
        return false;
    }
}

/**
 * 获取微信操作对象
 * @param string $type
 * @return \Wechat\WechatMedia|\Wechat\WechatMenu|\Wechat\WechatOauth|\Wechat\WechatPay|\Wechat\WechatReceive|\Wechat\WechatScript|\Wechat\WechatUser|\Wechat\WechatExtends|\Wechat\WechatMessage
 * @throws Exception
 */
function & load_wechat($type = '', $appid = '')
{
    vendor('wechat-php-sdk.include');
    static $wechat = [];
    $index = md5(strtolower($type));
    if (!isset($wechat[$index])) {
        $wx_config = Db::name('wx_config')->where(['id' => 3])->find();

        $config = [
            'token' => $wx_config['token'],
            'appid' => $wx_config['appid'],
            'appsecret' => $wx_config['appsecret'],
            'encodingaeskey' => $wx_config['encodingaeskey'],
//            'mch_id'         => '',
            'partnerkey' => $wx_config['partnerkey'],
            'ssl_cer' => $wx_config['ssl_cer'],
            'ssl_key' => $wx_config['ssl_key'],
            'cachepath' => CACHE_PATH . 'wxpay' . DS,
        ];

        $wechat[$index] = \Wechat\Loader::get($type, $config);
    }
    return $wechat[$index];
}

function model_http_curl_get($url, $userAgent = "")
{
    $userAgent = $userAgent ? $userAgent : 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.2)';
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_TIMEOUT, 5);
    curl_setopt($curl, CURLOPT_USERAGENT, $userAgent);
    $result = curl_exec($curl);
    curl_close($curl);
    return $result;
}

/**
 * 打印输出数据到文件
 * @param type $data 需要打印的数据
 * @param type $replace 是否要替换打印
 * @param string $pathname 打印输出文件位置
 * @author zoujingli <zoujingli@qq.com>
 */
function p($data, $replace = false, $pathname = NULL)
{
    is_null($pathname) && $pathname = RUNTIME_PATH . date('Ymd') . '_print.txt';
    $str = (is_string($data) ? $data : (is_array($data) || is_object($data)) ? print_r($data, TRUE) : var_export($data, TRUE)) . "\n";
    $replace ? file_put_contents($pathname, $str) : file_put_contents($pathname, $str, FILE_APPEND);
}

/*
 *
 *返回字符串的毫秒数时间戳
 */
function get_total_millisecond()
{
    $time = explode(" ", microtime());
    $time = $time [1] . ($time [0] * 1000);
    $time2 = explode(".", $time);
    $time = $time2 [0];
    return $time;
}

//获取MD5唯一值
function getMd5()
{
    return md5(uniqid() . rand(1000000000, 9999999999) . rand(1000000000, 9999999999) . rand(1000000000, 9999999999));
}

function _getUrlKey($qe_code)
{
    $key = md5(uniqid() . rand(100000, 999999) . $qe_code);
    return $key;
}

//生成随即字母
function create_password($pw_length = '')
{
    $randpwd = '';
    for ($i = 0; $i < $pw_length; $i++) {
        $randpwd .= chr(mt_rand(97, 122));
    }
    return $randpwd;
}

function get_client_ip($type = 0, $adv = false)
{
    $type = $type ? 1 : 0;
    static $ip = NULL;
    if ($ip !== NULL) return $ip[$type];
    if ($adv) {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $pos = array_search('unknown', $arr);
            if (false !== $pos) unset($arr[$pos]);
            $ip = trim($arr[0]);
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    // IP地址合法验证
    $long = sprintf("%u", ip2long($ip));
    $ip = $long ? array($ip, $long) : array('0.0.0.0', 0);
    return $ip[$type];
}