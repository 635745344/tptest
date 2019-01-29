<?php
namespace app\group\controller;

use app\common\controller\Base ;
use think\Request;
use think\Db;

class Advertisement extends Check
{
    public function __construct()
    {
        $this->group_id = session('group_id');
        $this->power_id = session('power_id');
        $this->group_user_id = session('group_user.id');
        parent::__construct();
    }
    //主页
    public function index()
    {
        $data=Db::name('Advertisement')->field('id,img_info,stop_time,status')->where(['group_id'=>$this->group_id])->find();
        $group_list = Db::name('equipment_group')->where(['status'=>1])->field('id,group_name')->select();
        return view('',['data'=>$data,'group_list'=>json_encode($group_list)]);
    }
    //修改信息
    public function editInfo()
    {
        $params=input();

        $data['img_info'] = $params['img_info'];
        $data['stop_time'] = $params['stop_time'];
        $data['status'] = 1;
        $data['update_time'] = time();

        $check = Db::name('Advertisement')->where(['group_id'=>$this->group_id])->find();
        if(empty($check)) //不存在添加
        {
            $data['create_time'] = time();
            $data['group_id'] =  $this->group_id;
            $result=Db::name('Advertisement')->insert($data);
        }
        else
        {
            //存在修改
            $result =Db::name('Advertisement')->where(['group_id'=>$this->group_id])->update($data);
        }
        //推送广告
        foreach (json_decode($params['img_info'],true)  as $k=>$v )
        {
            if($v['status']==1 && !empty($v['url']))
            {
                $v['url'] = str_replace('http://gzh.zonma.net:80','http://gzh.zonma.net',$v['url']);
                $imgs[]=$v['url'];
            }
        }
//        var_dump($imgs);exit;
        $group_eq_list = Db::name('equipment')->where(['group_id'=>$this->group_id])->select();

        $chuyanji = new \app\common\service\ChuyanjiService();
        $XingeRest = new \app\common\service\XingeRest();
        $str = implode(',',$imgs);
//        var_dump($imgs);exit;
        if(is_array($group_eq_list)) foreach ($group_eq_list as $key => $value)
        {
            $msg_id = time();
            $content=json_encode( ['type'=>2,'imgs'=>"$str",'stop_time'=>$params['stop_time']*1000,'msg_id'=>$msg_id] );
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


    //上传图片
    public function upload()
    {
        $request = Request::instance();
        $file = request()->file('image');
//        'size'=>20480, ->validate(['ext'=>'jpg,jpeg,png'])
        $info = $file->move( ROOT_PATH .'public/upload/advertisement' ,true,false);
        if($info){
            $server_name = 'http://'.$_SERVER['SERVER_NAME'];
//                        var_dump($server_name);exit;

            return r(1,'',['data'=>$server_name.'/upload/advertisement/'.str_replace('\\','/',$info->getSaveName())]);
        }else{
            return r(0,$file->getError());
        }
    }

    //根据组id查询对应广告图
    public function get_group_banner()
    {
        $info = Db::name('Advertisement')->where(['group_id'=>$this->group_id])->find();
        if($info){
            return json(['status'=>1,'info'=>'成功','data'=>$info]);
        }else{
            return json(['status'=>0,'info'=>'无数据']);
        }
    }


}