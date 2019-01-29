<?php
/**
 * Created by PhpStorm.
 * User: Colin
 * Date: 2018/5/7
 * Time: 13:22
 */

namespace app\api\controller;
use think\Db;
//use Library\Util\MemcacheInstance;
use app\api\controller\Base;
use JPush\Client as JPush;
use think\Request;
use think\Controller;
use think\Cache;

class IsOnLine extends Base
{
    //检查设备离线情况
    public function checkAllEqLx()
    {
        $eq_code=input('eq_code');
        //是否停止进程
       $path=ROOT_PATH."runtime/switch/isOnLineIsExit.txt";

        ignore_user_abort(false);//当用户关闭页面时服务停止
        set_time_limit(0);  //设置执行时间，单位是秒。0表示不限制。
        date_default_timezone_set('Asia/Shanghai');//设置时区

        //修改设备状态
        Db::name('equipment')->where(['code'=>$eq_code])->update(['status'=>0]);
        Cache::set('1525395254_'.$eq_code,time());

        $is_exit = file_get_contents($path);
        $myfile = fopen($path, "w") or die("Unable to open file!");
        $txt = '0';
        fwrite($myfile, $txt);
        fclose($myfile);

        while(true)
        {
            if(is_null($is_exit) ){
                return json(['status'=>1,'info'=>'请求成功']);
            }

            //这里是需要定时执行的任务
            $this->_updateAllEq();
            sleep(30); //暂停时间（单位为秒）
        }
//        $this->_updateAllEq();
//        return json(['status'=>1,'info'=>'请求成功']);
    }
    //检查其他设备是否在线
    private function _updateAllEq()
    {
//        距离上次更新时间
        $overtime=61; //单位秒s
        $eqs = Db::name('equipment')->field('id,code,status')->select();

        $is_lx=[];
        foreach ($eqs as $k=>$v)
        {
            $update_time=Cache::get('1525395254_'.$v['code']);
            $is_add_lx=false;
            if( is_numeric($update_time)  )
            {
                if( $update_time+$overtime>time() )
                {
                    $is_add_lx=true;
                }
            }
            else
            {
//                $is_add_lx=true;
                  $is_add_lx=false;
            }

            if($v['status']!==0 && $is_add_lx == false){
                $is_lx[]=$v['id'];
            }
        }
        Cache::set('1525396412_last_visit_time',time());

        //修改设备状态
        Db::name('equipment')->where(['id'=>['in',$is_lx]])->update(['status'=>0]);

    }

    //检查其他设备是否在线
    public function test()
    {
//        距离上次更新时间
        $overtime=61; //单位秒s
        $eqs = Db::name('equipment')->field('id,code,status')->select();


        $is_lx=[];
        foreach ($eqs as $k=>$v)
        {
            $update_time=Cache::get('1525395254_'.$v['code']);
            $is_add_lx=false;
            if( is_numeric($update_time)  )
            {
                if( $update_time+$overtime>time() )
                {
                    $is_add_lx=true;
                }
            }
            else
            {
                $is_add_lx=true; //10-10
            }

            if($v['status']!==0 && $is_add_lx == false){
                $is_lx[]=$v['id'];
            }
        }

           print_r($is_lx);

//        Cache::set('1525396412_last_visit_time',time());

    }

    //检查是否执行检查离线程序
    private function _isExecCheckLx($is_default_time=false)
    {
        $is_exec=true;

        $last_visit_time=Cache::get('1525396412_last_visit_time');

        if( $last_visit_time===false  ){
            if($is_default_time){
                Cache::set('1525396412_last_visit_time',time(),0);
            }else{
                $is_exec=false;
            }
        }else{
            if( $last_visit_time + 60 > time() ){
                $is_exec=false;
            }
        }

        return $is_exec;
    }

}