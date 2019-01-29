<?php
/**
 * Created by PhpStorm.
 * User: Colin
 * Date: 2018/10/12
 * Time: 9:48
 */

namespace app\group\controller;
use think\Controller;
use think\Session;
use think\Db;
use think\Request;
use app\common\controller\Base;

class Astrict extends Check
{
    public function __construct()
    {
        $this->group_id = session('group_id');
        $this->power_id = session('power_id');
        $this->group_user_id = session('group_user.id');
        parent::__construct();
    }

    //设置出烟页面
    public function index()
    {
        $group = Db::name('equipment_group')->where(['id'=>$this->group_id])->field('choice,choice_total')->find();
        $astrict_type = $group['choice'];
        $Total = $group['choice_total'];

        $goods_list = Db::name('goods_out_limit')->alias('ol')
            ->join('sp_goods g','ol.goods_id = g.id','left')
            ->field('ol.goods_id as id,ol.limit,g.name')->where(" g.status=1 and ol.goods_id!=0 and ol.group_id=$this->group_id")->select();
        $group_list = Db::name('equipment_group')->where(['status'=>1])->field('id,group_name')->select();

        return view('',['goods_list'=>json_encode($goods_list),'group_list'=>json_encode($group_list),'astrict_type'=>$astrict_type,'total'=>$Total]);
    }

    public function get_group_limit()
    {
        $group_set = Db::name('equipment_group')->where(['id'=>$this->group_id])->field('choice as astrict_type,choice_total as total')->find();

        $lis = Db::name('goods_out_limit')->where(['group_id'=>$this->group_id])->field('goods_id,limit')->select();
        if(is_array($lis)){
            return json(['status'=>1,'msg'=>'成功','data'=>$lis,'choice'=>$group_set]);
        }else{
            return json(['status'=>0,'msg'=>'无数据']);
        }
    }

    //修改设置
    public function set()
    {
        $astrict_type = trim(input('post.astrict_type'));
        $total = trim(input('post.total'));
        $goods_set = trim(input('post.goods_set'));
        $group_id = $this->group_id;
        if(empty($astrict_type)){
            return json(['status'=>0,'msg'=>'参数异常']);
        }else{
            $data['choice'] = $astrict_type;
        }

        if(!empty($total)){
            $max = 280;
            if($total>$max){
                return json(['status'=>0,'msg'=>"总数不能大于".$max]);
            }
            $data['choice_total'] = $total;
        }

        Db::name('equipment_group')->where("id=$group_id")->update($data);

        if(!empty($goods_set))
        {
            $arr = json_decode($goods_set,true);
            if(is_array($arr)) foreach ($arr as $k => $v)
            {
                $check = Db::name('goods_out_limit')->where(['goods_id'=>$k,'group_id'=>$group_id])->find();
                if($check)
                {
                    Db::name('goods_out_limit')->where(['goods_id'=>$k,'group_id'=>$group_id])->update(['limit'=>$v]);
                }else{
                    Db::name('goods_out_limit')->insert(['goods_id'=>$k,'group_id'=>$group_id,'limit'=>$v]);
                }
            }
        }

        return json(['status'=>1,'msg'=>'操作成功']);
    }


}