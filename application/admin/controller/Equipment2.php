<?php
/**
 * Created by PhpStorm.
 * User: Colin
 * Date: 2018/4/10
 * Time: 10:52
 */

namespace app\admin\controller;
use think\Controller;
use app\library\AdminHelper;
use app\library\DbHelper;
use think\Db;
use think\Config;
use app\library\ExcelHelper;
use app\common\controller\Base;

class Equipment2 extends Base
{
    public function __construct(){
        parent::__construct();
        $this->M = new \app\common\model\Equipment2;
    }
    //首页
    public function index(){ return view(); }
    //初始化设备视图
    public function equipmentInit(){
        $goods_list=Db::name('goods')->alias('g')
            ->join('goods_brand gb','g.goods_brand_id=gb.id','left')
            ->field(" g.id,gb.name gb_name,g.name")
            ->where(['status'=>1])
            ->order('g.create_time desc')
            ->select();

        foreach ($goods_list as $k=>$v){
            if(empty($v['gb_name'])){
                $goods_list[$k]['name']=$v['name'];
            }else{
                $goods_list[$k]['name']=$v['name'];
            }
            unset($goods_list[$k]['gb_name']);
        }

        $equipment_default=Db::name('equipment_default')
            ->where(['id'=>1])
            ->field('id,groove_good1,groove_good2,groove_good3,groove_good4,groove_good5,groove_good6,groove_good7')
            ->find();

        $group_list = Db::name('equipment_group')->where(['status'=>1])->field('id,group_name')->select();

        return view('',['group_list'=>json_encode($group_list),'goods_list'=>json_encode($goods_list),'equipment_default'=>json_encode($equipment_default)]);
    }
    //修改设备初始化资料
    public function editInit()
    {
        $params=input('post.');
        $data=[
            'groove_good1'=>empty($params['groove_good1'])?0:$params['groove_good1'],
            'groove_good2'=>empty($params['groove_good2'])?0:$params['groove_good2'],
            'groove_good3'=>empty($params['groove_good3'])?0:$params['groove_good3'],
            'groove_good4'=>empty($params['groove_good4'])?0:$params['groove_good4'],
            'groove_good5'=>empty($params['groove_good5'])?0:$params['groove_good5'],
            'groove_good6'=>empty($params['groove_good6'])?0:$params['groove_good6'],
            'groove_good7'=>empty($params['groove_good7'])?0:$params['groove_good7'],
            'update_time'=>time(),
        ];
        if(empty($params['id']))
        {
            return json(['status'=>0,'info'=>'请输入id！']);
        }else
        {
            Db::name('equipment')->where(['create_time'=>['>',957412797]])->update($data);
            Db::name('equipment_default')->where(['id'=>$params['id']])->update($data);
            return json(['status'=>1,'info'=>'操作成功']);
        }
    }

//
//    //添加设备视图
//    public function addView(){return view();}
//
    //查询
    public function lists($page=1,$limit=10)
    {
        $where=[];
        if($params=input('post.')){
            if(!empty($params['code']) || ( isset($params['code']) && $params['code']==0) ){
                $where['e.code']=['like','%'.$params['code'].'%'];
            }
            if(isset($params['status']) && $params['status']<>''){
                $where['e.status']=$params['status'];
            }
            if(!empty($params['name'])){
                $where['s.name']=['like','%'.$params['name'].'%'];
            }
            if(!empty($params['province'])){
                $where['s.province']=$params['province'];
            }
            if(!empty($params['city'])){
                $where['s.city']=$params['city'];
            }
            if(!empty($params['district'])){
                $where['s.district']=$params['district'];
            }
        }
        $data=Db::name('equipment')->alias('e')
            ->join('sp_shop s',' s.id=e.shop_id ','left')
            ->where($where)
            ->field('e.id,e.code,e.groove1,e.groove2,e.groove3,e.groove4,e.groove5,e.groove6,e.groove7,e.status,s.province,s.city,s.district,s.address')
            ->order('e.create_time desc')
            ->page($page,$limit)
            ->select();

        $count=Db::name('equipment')->alias('e')
                    ->join('sp_shop s',' s.id=e.shop_id ','left')
                    ->where($where)
                    ->count();

        return r_page(1,$data,$count);
    }
    //导出
    public function export()
    {
        $where=[];
        $data=Db::name('equipment')->alias('e')
            ->join('sp_shop s',' s.id=e.shop_id ','left')
            ->where($where)
            ->field('e.code,e.groove1,e.groove2,e.groove3,e.groove4,e.groove5,e.groove6,e.groove7,e.status,s.province,s.city,s.district,s.address')
            ->order('e.create_time desc')
            ->select();

        foreach ($data as $k=>$v)
        {
            if($v['status']==0){
                $data[$k]['status']='离线';
            }else if($v['status']==1){
                $data[$k]['status']='在线';
            }else{
                $data[$k]['status']='未知';
            }
        }

        $Execl=new ExcelHelper();
        $Execl->exportExcel('设备列表'.'_'.date('Ymd'),['设备编码','槽1库存数','槽2库存数','槽3库存数',
            '槽4库存数','槽5库存数','槽6库存数','槽7库存数','状态','省','市','区县','详细地址'],$data);
        return;
    }

    //实时监控
    public function monitor()
    {
        return view();
    }

    //设备监控列表
    public function monitorlist($eq_code='',$goods_name='',$page=1,$limit=10)
    {
        $where=[];
        if(!empty($eq_code)){
            $where['eq_code']=['like','%'.$eq_code.'%'];
        }
        $where2=[];
        if(!empty($goods_name)){
            $where2=" goods_id in (select id from sp_goods where name like '%".$goods_name."%')";
        }

        $data = Db::name('receive_record')->alias('rr')
            ->join('sp_goods g','rr.goods_id=g.id')
            ->join('sp_user u','rr.openid=u.openid')
            ->where($where)
            ->where($where2)
            ->page($page,$limit)
            ->order('rr.create_time desc')
            ->field('rr.id,rr.eq_code,u.nickname,rr.groove_num,g.name goods_name,FROM_UNIXTIME(rr.create_time) create_time')
            ->select();

        $count=Db::name('receive_record')->alias('rr')
            ->join('sp_goods g','rr.goods_id=g.id')
            ->join('sp_user u','rr.openid=u.openid')
            ->where($where)
            ->where($where2)
            ->count();

        return json(['status'=>1,'info'=>'','data'=>$data,'count'=>$count]);

    }
//    public function cs(){
//        $data = Db::name('receive_record')->select();
////        $data_goods = Db::name('sp_goods')->select();
//        foreach ($data as $k=>$v)
//        {
//            $groove_num=0;
//            $eq=Db::name('equipment')->where(['code'=>$v['eq_code']])->find();
//            if($eq['groove_good1']==$v['goods_id']){
//                $groove_num=1;
//            }else if($eq['groove_good2']==$v['goods_id']){
//                $groove_num=2;
//            }else if($eq['groove_good3']==$v['goods_id']){
//                $groove_num=3;
//            }else if($eq['groove_good4']==$v['goods_id']){
//                $groove_num=4;
//            }else if($eq['groove_good5']==$v['goods_id']){
//                $groove_num=5;
//            }else if($eq['groove_good6']==$v['goods_id']){
//                $groove_num=6;
//            }else if($eq['groove_good7']==$v['goods_id']){
//                $groove_num=7;
//            }else if($eq['groove_good8']==$v['goods_id']){
//                $groove_num=8;
//            }
//
//            Db::name('receive_record')->where(['id'=>$v['id']])->update( [ 'groove_num' =>$groove_num ] );
//
//        }
//    }
}