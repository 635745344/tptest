<?php

namespace app\member\controller;

use think\Controller;
use think\Session;
use think\Db;
use think\Request;

class Ticketauth extends Controller {

    public function index() {
        $wechat = $this->getInstanceWechat();
        $info = $wechat->getJsTicket();
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
//         P('ip:'.$ip);
        if (empty($info)) {
            $data = array();
            $data['status'] = 0; //获取ticket失败
            $data['info'] = '获取ticket失败'; //消息提示
            $this->ajaxReturn($data);
        } else {
            $data = array();
            $data['ticket'] = $info; //ticket
            $data['status'] = 1; //成功
            $this->ajaxReturn($data);
        }
    }

}
