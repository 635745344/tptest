<?php
/**
 * Created by PhpStorm.
 * User: Colin
 * Date: 2018/4/28
 * Time: 16:05
 */

namespace app\common\controller;
use think\Controller;
//缓存文件 a4b0321fafd1d9c66da634f58e24b2
//执行定时任务
class Task extends Controller
{
//    //检查是否在线
//    public static function isOnLine()
//    {
////        $isExecisOnLine = cache('isExec_isOnLine_1524645578');
//////        var_dump(cache('isExec_isOnLine_1524645578'));
////        if(empty($isExecisOnLine)){
////            cache('isExec_isOnLine_1524645578',true,0);
//////            return json(['status'=>1,'info'=>'任务成功运行']);
////        }else{
//////            return json(['status'=>0,'info'=>'任务已运行，不可重复执行']);
////        }
//
//        ignore_user_abort(false);//当用户关闭页面时服务停止
//        set_time_limit(0);  //设置执行时间，单位是秒。0表示不限制。
//        date_default_timezone_set('Asia/Shanghai');//设置时区
//
//        while(TRUE)
//        {
//            $stop_time=1; //暂停时间（单位为秒）
//            $ChuyanjiService = new \app\common\service\ChuyanjiService();
//            $ChuyanjiService->isOnLine($stop_time);
//
////$myfile = fopen("E:/data/zmh/chuyangji/trunk/1.txt", "w");
////fwrite($myfile,date('Y-m-d H:i:s'));
//
//            //这里是需要定时执行的任务
//            sleep($stop_time);
//        }
//    }
    //检查是否在线
    public static function isOnLine($cycle)
    {
//      $stop_time=30; //暂停时间（单位为秒）
        $ChuyanjiService = new \app\common\service\ChuyanjiService();
        $result= $ChuyanjiService->isOnLine($cycle);
        if($result==true){
            return json(['status'=>1,'info'=>'调用成功']);
        }else{
            return json(['status'=>0,'info'=>'调用失败']);
        }
    }

}