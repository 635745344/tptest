<?php
/**
 * Created by PhpStorm.
 * User: Colin
 * Date: 2018/10/12
 * Time: 9:48
 */

namespace app\admin\controller;
use think\Controller;
use think\Session;
use think\Db;
use think\Request;
use app\common\controller\Base;

class Astrict extends Base
{
    //设置出烟页面
    public function index()
    {

        $set = Db::name('issue_set')->find();
        $astrict_type = $set['choice'];
        $Total = $set['choice_total'];

        $group_list = Db::name('equipment_group')->where(['status'=>1])->field('id,group_name')->select();
        $goods_list = Db::name('goods')->where(['status'=>1])->field('id,name,limit')->select();
        return view('',['goods_list'=>json_encode($goods_list),'group_list'=>json_encode($group_list),'astrict_type'=>$astrict_type,'total'=>$Total]);
    }

    public function get_group_limit()
    {
        $group_id = trim(Input('group_id'));

        $group_set = Db::name('equipment_group')->where(['id'=>$group_id])->field('choice as astrict_type,choice_total as total')->find();

        $lis = Db::name('goods_out_limit')->where(['group_id'=>$group_id])->field('goods_id,limit')->select();
        if(is_array($lis)){
            return json(['status'=>1,'msg'=>'成功','data'=>$lis,'choice'=>$group_set]);
        }else{
            return json(['status'=>0,'msg'=>'无数据']);
        }
    }

    //修改设置
    public function set(){

        $astrict_type = trim(input('post.astrict_type'));
        $total = trim(input('post.total'));
        $goods_set = trim(input('post.goods_set'));
        $group_id = trim(input('post.group_id'));
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

    public function test(){
        exit;
        $goods = Db::name('goods')->select();
        foreach ($goods as $k => $v){
            $data['goods_id'] = $v['id'];
            $data['group_id'] = 0;
            $data['limit'] = 50;
            DB::name('goods_out_limit')->insert($data);
        }
    }

}