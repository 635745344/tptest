<?php

//检查设备是否在线
function isOnLine()
{
//    ignore_user_abort(false);//当用户关闭页面时服务停止
//    set_time_limit(0);  //设置执行时间，单位是秒。0表示不限制。

//        $myfile = fopen("E:/data/zmh/chuyangji/trunk/1.txt", "w");
//        fwrite($myfile,'sdsfd');

    while(true)
    {
        $stop_time=3; //暂停时间（单位为秒）
        //这里是需要定时执行的任务
        $chuyanji = new \app\common\service\ChuyanjiService();
        $chuyanji->isOnLine($stop_time);
        sleep($stop_time);
    }
}

isOnLine();