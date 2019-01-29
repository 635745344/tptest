<?php
namespace app\admin\controller;

use app\common\controller\Base;
use think\Request;
use think\Db;

class Advertisement2 extends Base
{
    public function __construct(){
        parent::__construct();
        $this->M = new \app\common\model\Advertisement;
    }
    //主页
    public function index()
    {
        $data=Db::name('Advertisement')->field('id,img_info,stop_time,status')->find();
        $group_list = Db::name('equipment_group')->where(['status'=>1])->field('id,group_name')->select();
        return view('',['data'=>$data,'group_list'=>json_encode($group_list)]);
    }
    //修改信息
    public function editInfo()
    {

        $params=input();
        $opt = input('opt');

        if(empty($opt))
        {
            return json(['status'=>0,'info'=>'请选择']);
        }

        $data['opt'] = $opt;
        $data['status'] = 1;
        if($opt==1)
        {
            $data['img_info'] = input('img_info');
            $data['stop_time'] = input('stop_time');
        }else if($opt==2){
            $data['video_url'] = input('video_url');
        }


        $data['update_time'] = time();

        $check = Db::name('Advertisement')->where(['group_id'=>$params['group_id']])->find();
        if(empty($check)) //不存在添加
        {
            $data['create_time'] = time();
            $data['group_id'] = input('group_id');
            $result=Db::name('Advertisement')->insert($data);
        }
        else
        {
            //存在修改
            $result = Db::name('Advertisement')->where(['group_id'=>$params['group_id']])->update($data);
        }

//        return json(['status'=>1,'info'=>'修改成功']);

        $group_eq_list = Db::name('equipment')->where(['group_id'=>$params['group_id']])->select();


        if(is_array($group_eq_list)) foreach ($group_eq_list as $key => $value)
        {
            if(strpos(config('app_old_push.lis'),$value['code']) !==false) {
                $chuyanji = new \app\common\service\ChuyanjiService();
                $XingeRest = new \app\common\service\XingeRest();
            }else{
                $chuyanji = new \app\common\service\ChuyanjiServiceNew();
                $XingeRest = new \app\common\service\XingeRestNew();
            }

            $msg_id = time();
            $url = 'http://gzh.zonma.ne/home/reveal/index?eq_code='.$value['code'];
//            $content=json_encode( ['type'=>2,'imgs'=>"$str",'stop_time'=>$params['stop_time']*1000,'msg_id'=>$msg_id] );
            $content=json_encode( ['type'=>2,'url'=>"$url",'msg_id'=>$msg_id] );
            $registration_id = $value['registration_id'];
            $tx_push_id = $value['tx_push_id'];
            if(!empty($registration_id)){
                $chuyanji->pushImgInfoGroup($content,$registration_id,$msg_id);
            }
            if(!empty($tx_push_id)){
                $XingeRest->pushImgSave($content,$tx_push_id,$msg_id);
            }
        }
        P($content);
        return json(['status'=>1,'info'=>'修改成功']);



    }

//    public function test(){
//
//        $group_eq_list = Db::name('equipment')->where(['group_id'=>1])->select();
//        $content=json_encode( ['type'=>2,'imgs'=>'ts','stop_time'=>800 ]);
//
//        $chuyanji = new \app\common\service\ChuyanjiService();
//
//        if(is_array($group_eq_list)) foreach ($group_eq_list as $key => $value){
//            $registration_id = $value['registration_id'];
//            if(!empty($registration_id)){
//                $chuyanji->pushImgInfoGroup($content,'13065ffa4e218ec7b62');
//            }
//        }
//        return json(['status'=>1,'info'=>'修改成功']);
//    }

    //上传图片
//    public function upload()
//    {
//        $request = Request::instance();
//        $file = request()->file('image');
//
////        'size'=>20480, ->validate(['ext'=>'jpg,jpeg,png'])
//        $info = $file->move( ROOT_PATH .'public/upload/advertisement' ,true,false);
//        if($info){
//            $server_name = 'http://'.$_SERVER['SERVER_NAME'];
//
//            return r(1,'',['data'=>$server_name.'/upload/advertisement/'.str_replace('\\','/',$info->getSaveName())]);
//        }else{
//            return r(0,$file->getError());
//        }
//    }
    //上传图片
    public function upload()
    {
        $base_img = input('image');
        if(empty($base_img))
        {
            return json(['status'=>0]);
        }

        $path = $_SERVER['DOCUMENT_ROOT'].'/upload/advert/';

        $base_img = str_replace('data:image/jpeg;base64,', '', $base_img);

        //  设置文件路径和文件前缀名称
        $p = "/upload/advert/";
        $prefix='nx_';
        $output_file = $prefix.time().rand(100,999).'.jpeg';
        $path = $path.$output_file;

        //  创建将数据流文件写入我们创建的文件内容中
        $ifp = fopen($path,"w+");  //地址要绝对路径不然会报错
        fwrite($ifp, base64_decode($base_img) );
        fclose($ifp );
        $img = 'http://'.$_SERVER['SERVER_NAME'].$p.$output_file;
        return json(['status'=>1,'data'=>$img]);

//        $request = Request::instance();
//        $file = request()->file('image');
////        'size'=>20480, ->validate(['ext'=>'jpg,jpeg,png'])
//        $info = $file->move( ROOT_PATH .'public/upload/advertisement' ,true,false);
//        if($info){
//            $server_name = 'http://'.$_SERVER['SERVER_NAME'];
////                        var_dump($server_name);exit;
//
//            return r(1,'',['data'=>$server_name.'/upload/advertisement/'.str_replace('\\','/',$info->getSaveName())]);
//        }else{
//            return r(0,$file->getError());
//        }
    }

    //根据组id查询对应广告图
    public function get_group_banner(){
        $group_id = trim(Input('get.group_id'));

        if(empty($group_id)) return json(['status'=>0,'info'=>'参数异常']);

        $info = Db::name('Advertisement')->where(['group_id'=>$group_id])->find();
        if($info){
            return json(['status'=>1,'info'=>'成功','data'=>$info]);
        }else{
            return json(['status'=>0,'info'=>'无数据']);
        }
    }

    public function test(){
        return view();
    }


}