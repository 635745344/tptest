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

class Group extends Base
{
    //设置出烟页面
    public function index()
    {
        $group_list = Db::name('equipment_group')->where(['status'=>1])->field('id,group_name')->select();
        return view('',['group_list'=>json_encode($group_list)]);
    }

    //设置出烟页面
    public function depot()
    {
        return view();
    }

    //添加分组
    public function add_group()
    {
        $group_name = trim(Input('post.group_name'));

        if(empty($group_name)){
            return json(['status'=>0,'msg'=>'组名不能为空']);
        }

        $group_id = Db::name('equipment_group')->insertGetId(['group_name'=>$group_name,'create_time'=>time()]);
        if($group_id){
            return json(['status'=>1,'msg'=>'成功','group_id'=>$group_id]);
        }else{
            return json(['status'=>0,'msg'=>'失败']);
        }
    }

    //獲取分組
    public function get_group()
    {
        $group_list = Db::name('equipment_group')->where(['status'=>1])->field('id,group_name')->select();
        $group_list[] = ['id'=>0,'group_name'=>'未分组'];
        if(is_array($group_list)) foreach ($group_list as $k =>$v){
            $count = Db::name('equipment')->where(['group_id'=>$v['id']])->count();
            $group_list[$k]['group_count'] = $count;
        }

        $equipment_count = Db::name('equipment')->count();

        if($group_list){
            return json(['status'=>1,'msg'=>'成功','group_list'=>$group_list,'equipment_count'=>$equipment_count]);
        }else{
            return json(['status'=>0,'msg'=>'失败']);
        }
    }

    //獲取每個分組的機器
    public function get_group_machine()
    {
        $group_id = trim(Input('group_id'));
        $limit = trim(Input('limit'));
        $page = trim(Input('page'));

        $limit = $limit==''?10:$limit;
        $page = $page==''?1:$page;

        if($group_id==''){
            $where = "q.group_id > -1 ";
        }else{
            $where = "q.group_id=$group_id";
        }
        $goods = Db::name('goods')->where(['status'=>1])->field('id,name')->select();
        $group_list = Db::name('equipment_group')->where(['status'=>1])->field('id,group_name')->select();
        $group_list[] = ['id'=>0,'group_name'=>'未分组'];

        if($group_id!=''){
            $ls = DB::query("SELECT q.id,q.code,q.group_id,g.group_name,q.groove_good1,q.groove_good2,q.groove_good3,q.groove_good4,q.groove_good5,q.groove_good6,q.groove_good7 FROM `sp_equipment` q
LEFT JOIN `sp_equipment_group` g ON q.group_id=g.id WHERE q.group_id=$group_id");
        }else{
            $ls = DB::query("SELECT q.id,q.code,q.group_id,g.group_name,q.groove_good1,q.groove_good2,q.groove_good3,q.groove_good4,q.groove_good5,q.groove_good6,q.groove_good7 FROM `sp_equipment` q
LEFT JOIN `sp_equipment_group` g ON q.group_id=g.id");
        }


        $count = count($ls);

        $list=Db::name('equipment')->alias('q')
            ->join('sp_equipment_group g',' q.group_id=g.id ','left')
            ->join('sp_maintain_account ma','q.maintain_id=ma.id','left')
            ->join('sp_user u','ma.openid=u.openid','left')
            ->where($where)
            ->field('q.id,q.code,q.group_id,g.group_name,q.groove_good1,q.groove_good2,q.groove_good3,q.groove_good4,q.groove_good5,q.groove_good6,q.groove_good7,q.maintain_id,q.alarm_count,u.nickname')
            ->page($page,$limit)
            ->order('q.id asc')
            ->select();

        $maintain_list = Db::name('maintain_account')->alias('ma')
            ->join('sp_user u','ma.openid=u.openid','left')
            ->field('ma.id as maintain_id,u.nickname')->where(['ma.status'=>1])->select();

        $maintain_list[] = ['maintain_id'=>0,'nickname'=>'未分配'];

        if(is_array($list)) foreach ($list as $key => $value)
        {
            $list[$key]['group_list'] = $group_list;
            $list[$key]['maintain_list'] = $maintain_list;
            for ($i=1;$i<8;$i++)
            {
                $groove_good = 'groove_good'.$i;
                $gr_gid = $value[$groove_good];
                foreach ($goods as $k =>$v){
                    if($v['id']==$gr_gid){
                        $list[$key][$groove_good.'_g_name'] = $v['name'];
                    }else if($gr_gid==0){
                        $list[$key][$groove_good.'_g_name'] = '未分配';
                    }
                }
            }
        }

        if(is_array($list)){
            return json(['status'=>1,'msg'=>'成功','machine_list'=>$list,'count'=>$count]);
        }else{
            return json(['status'=>0,'msg'=>'失败']);
        }
    }

    //把机器加入某个分组
    public function save_group_machine(){
        $group_id = trim(Input('post.group_id'));
        $eq_id = trim(Input('post.eq_id'));
        $arr = explode(',',$eq_id);
        $group_equipment_data = Db::name('equipment_default')->where(['group_id'=>$group_id])->field('group_id,groove_good1,groove_good2,groove_good3,groove_good4,groove_good5,groove_good6,groove_good7')->find();

        if(!$group_equipment_data){
            $group_equipment_data['group_id'] = $group_id;
        }

        foreach ($arr as $key => $value){
            $e_id = $value;
            Db::name('equipment')->where(['id'=>$e_id])->update($group_equipment_data);
        }

        return json(['status'=>1,'msg'=>'成功']);

    }

    //修改报警设置
    public function alarm_set($eq_id='',$alarm_count=5,$maintain_id='')
    {

        if(empty($eq_id))
        {
            return json(['status'=>0,'msg'=>'请选择机器']);
        }
        if(empty($maintain_id))
        {
            $maintain_id = 0;
        }

        $arr = explode(',',$eq_id);
        foreach ($arr as $key => $value)
        {
            $id = $value;
            Db::name('equipment')->where(['id'=>$id])->update(['alarm_count'=>$alarm_count,'maintain_id'=>$maintain_id]);
        }

        return json(['status'=>1,'msg'=>'设置成功']);
    }

    //获取报警设置
    public function get_alarm_set($eq_code='',$group_id='',$page=1,$limit=10)
    {
        if(empty($eq_code)) $where['eq.code'] = $eq_code;
        if($group_id >0) $where['eq.group_id'] = $group_id;

        $count = Db::name('equipment')->alias('eq')
            ->join('sp_maintain_account ma','eq.maintain_id=ma.id','left')
            ->join('sp_user u','ma.openid=u.openid','left')
            ->field('eq.code,eq.maintain_id,eq.alarm_count,u.nickname,u.headimgurl')
            ->where($where)->count();

        $list = Db::name('equipment')->alias('eq')
            ->join('sp_maintain_account ma','eq.maintain_id=ma.id','left')
            ->join('sp_user u','ma.openid=u.openid','left')
            ->field('eq.code,eq.maintain_id,eq.alarm_count,u.nickname,u.headimgurl')
            ->page($page,$limit)
            ->where($where)->select();

        return json(['stauts'=>1,'msg'=>'查询成功','list'=>$list,'count'=>$count]);

    }

    public function get_maintain_list($nickname = '')
    {
        if(!empty($nickname)){
            $where['u.nickname'] = ['like',"%$nickname%"];
        }

        $where['ma.status'] = 1;
        $list = Db::name('maintain_account')->alias('ma')
            ->join('sp_user u','ma.openid=u.openid','left')
            ->field('ma.id as maintain_id,u.nickname')->where($where)->select();

        return json(['status'=>1,'msg'=>'查询成功','list'=>$list]);
    }

}