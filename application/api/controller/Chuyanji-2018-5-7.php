<?php
namespace app\api\controller;

use think\Db;
//use Library\Util\MemcacheInstance;
use app\api\controller\Base;
use JPush\Client as JPush;
use think\Request;
use think\Cache;

class Chuyanji extends Base
{
    protected function _initialize()
    {
        parent::_initialize();
//        $this->mem = MemcacheInstance::getInstance (); // 采用单例模式调用Memcached
    }
    //初始化设备
    public function initEquipment()
    {
        $registration_id=input('registration_id');

        //检查极光推送注册id是否存在
        $equipment=Db::name('equipment')->where(['registration_id'=>$registration_id])->find();
        if(!empty($equipment)){
            return json(['status'=>0,'info'=>'极光推送注册id已经注册','code'=>$equipment['code']]);
        }
        
        //开始事物
        Db::startTrans();

        $last_code=Db::name('equipment')->order('create_time desc')->find()['code'];
        $new_code='';
        if(empty($last_code)){
            $new_code='100000';
        }else{
            $new_code = substr($last_code,0,6)+1;
        }

        $eq_config=Db::name('equipment_default')->find();

        //添加设备信息
        $data_equipment=[
            'code'=>$new_code,
            'groove1'=>0,
            'groove2'=>0,
            'groove3'=>0,
            'groove4'=>0,
            'groove5'=>0,
            'groove6'=>0,
            'groove7'=>0,
            'groove_good1'=>$eq_config['groove_good1'],
            'groove_good2'=>$eq_config['groove_good2'],
            'groove_good3'=>$eq_config['groove_good3'],
            'groove_good4'=>$eq_config['groove_good4'],
            'groove_good5'=>$eq_config['groove_good5'],
            'groove_good6'=>$eq_config['groove_good6'],
            'groove_good7'=>$eq_config['groove_good7'],
            'registration_id'=>$registration_id,
            'run_time'=>0,
            'activation_time'=>time(),
            'update_time'=>time(),
            'status'=>0,
            'create_time'=>time(),
        ];

        Db::name('equipment')->insert($data_equipment);

        Db::commit();

        return json(['status'=>1,'info'=>'初始化成功!','code'=>$new_code]);
    }
    //获取广告
    public function getAdvertisement()
    {
        $imgs=[];
        $data=Db::name('advertisement')->where(['status'=>1])->find();

        foreach (json_decode($data['img_info'],true) as $k=>$v ){
            if($v['status']==1 && !empty($v['url']))
            {
                $imgs[]=$v['url'];
            }
        }

        return json(['status'=>1,'info'=>'','imgs'=>implode(',',$imgs),'stop_time'=>$data['stop_time']*1000 ]);
    }
    //设备库存改变
    public function stockChange()
    {
        $params=input('post.');
        $data=[
            'groove1'=>$params['groove1'],
            'groove2'=>$params['groove2'],
            'groove3'=>$params['groove3'],
            'groove4'=>$params['groove4'],
            'groove5'=>$params['groove5'],
            'groove6'=>$params['groove6'],
            'groove7'=>$params['groove7'],
        ];
        //设备
        Db::name('equipment')->where(['code'=>$params['code']])->save($data);
        return json(['status'=>1,'info'=>'修改成功！']);
    }
//    //推送图片
//    public function pushImgInfo()
//    {
//        $imgs=[];
//        $data=Db::name('advertisement')->where(['status'=>1])->find();
//
//        foreach (json_decode($data['img_info'],true)  as $k=>$v ){
//            if($v['status']==1 && !empty($v['url']))
//            {
//                $imgs[]=$v['url'];
//            }
//        }
//        $content=json_encode( ['imgs'=>implode(',',$imgs),'stop_time'=>$data['stop_time'] ]);
//
//        $chuyanji = new \app\common\service\ChuyanjiService();
//        $result = $chuyanji->pushImgInfo($content);
//
//        return json($result);
//    }

    //检查设备在线状态
    public function isOnLine($eq_code)
    {
        $cycle=30; //周期时间30秒
        //修改设备状态
        $last_update_time=Cache::get('1525395254_'.$eq_code);
        $is_update=true;
        if($last_update_time===false)
        {
            Cache::set('1525395254_'.$eq_code,time());
        }else{
            if($last_update_time+20>time()){
                $is_update=false;
            }
        }
        if($is_update){
            Db::execute("update sp_equipment set run_time = run_time+".$cycle.", status=1 where code='".$eq_code."' ");
        }

        Cache::set('1525395254_'.$eq_code,time());
        $fp = fopen(ROOT_PATH."runtime/lock/isOnLine.txt", "w+");

        //开启文件锁
        if(flock($fp, LOCK_EX)){ // 进行排它型锁定

            $is_execute=true;
            $last_visit_time = Cache::get('1525396412_last_visit_time');

            if($last_visit_time===false){
                $time=time();
                Cache::set('1525396412_last_visit_time',$time);
                $last_visit_time=$time;
            }else
            {
                if($last_visit_time+30 >= time()){
                    $is_execute=false;
                }
            }

            //检查是否检测离线设备
            if($is_execute){
                $this->_updateAllEq();
            }

            flock($fp, LOCK_UN); // 释放锁定
        }

        fclose($fp);

        return json(['status'=>1,'info'=>'在线状态更新成功！']);

    }
    //检查其他设备是否在线
    private function _updateAllEq()
    {
//        距离上次更新时间
        $overtime=40; //单位秒s
        $eqs = Db::name('equipment')->field('id,code,status')->select();

        $is_lx=[];
        foreach ($eqs as $k=>$v)
        {
            $update_time=Cache::get('1525395254_'.$v['code']);
            $is_add_lx=false;
            if( is_numeric($update_time)  )
            {
                if( $update_time+$overtime<time() )
                {
                    $is_add_lx=true;
                }
            }
            else
            {
                $is_add_lx=true;
            }
            if($v['status']!==0 && $is_add_lx){
                $is_lx[]=$v['id'];
            }
        }

        Cache::set('1525396412_last_visit_time',time());
        //修改设备状态
        Db::name('equipment')->where(['id'=>['in',$is_lx]])->update(['status'=>0]);
    }


//    //获取推送结果
//    public function getPush()
//    {
//        $chuyanji = new \app\common\service\ChuyanjiService();
//        $result = $chuyanji->getPush(3969884556,['1507bfd3f7e7e3acf2b','160a3797c825b908db3']);

//        return json($result);
//    }

}