<?php
/**
 * Created by PhpStorm.
 * User: shaw
 * Date: 2018/12/27
 * Time: 15:45
 * 定时任务同服务器每天晚上12点05分访问
 * 统计前一天数据记录入库
 */

namespace app\api\controller;
use think\Db;
use think\Request;
use think\Cache;

class Task extends Base
{

    /**
     * 统计当天每个组数据
     * 每个组一条数据
     */
    public function smoke_data()
    {
        $group_list = Db::name('equipment_group')->field('id,new_view')->select();
        foreach ($group_list as $key => $value)
        {
            $this->day_smoke_data($value);
        }

        return json(['status'=>1,'msg'=>'数据统计成功']);
    }


    public function day_smoke_data($array)
    {
        $start_day = strtotime(date('Y-m-d',time() - 86400));
        $end_day = $start_day+86400;

        $where['eq.group_id'] = $array['id'];
        $where['rr.create_time'] = ['between',[$start_day,$end_day]];
        $where['rr.status'] = 1;
        if($array['new_view']==1)  $where['rr.is_callblack'] = 1;

        $list = Db::name('receive_record')->alias('rr')
            ->join('sp_equipment eq','rr.eq_code=eq.code','left')
            ->where($where)->field("count(rr.id) as number,FROM_UNIXTIME(rr.`create_time`,'%Y-%m-%d') as create_day")->group('create_day')->find();

        $number = $list['number']==''?0:$list['number'];
        $create_day = $list['create_day']==''?date('Y-m-d',$start_day):$list['create_day'];

        $data = ['group_id'=>$array['id'],'number'=>$number,'create_day'=>$create_day];

        Db::name('smoke_data')->insert($data);
    }


    /*
 * @某时间段机器出烟数量排名
 * @参数 ： group_id  start_day  end_day day
 * @return json
 */
    public function machine_smoke_data()
    {

        $start_day = strtotime(date('Y-m-d',time() - 86400));
        $end_day = $start_day + 86400;


        $where['rr.create_time'] = ['between',[$start_day,$end_day]];
        $where['rr.status'] = 1;
        if($this->new_view ==1 ) $where['rr.is_callblack'] = 1;


        $count = Db::name('receive_record')->alias('rr')
            ->join('sp_equipment eq','rr.eq_code=eq.code','left')
            ->join('sp_equipment_group eg','eg.id = eq.group_id','left')
            ->join('sp_shop s','eq.shop_id = s.id','left')
            ->where($where)->group('rr.eq_code')->count();

        $list = Db::name('receive_record')->alias('rr')
            ->join('sp_equipment eq','rr.eq_code=eq.code','left')
            ->join('sp_equipment_group eg','eg.id = eq.group_id','left')
            ->join('sp_shop s','eq.shop_id = s.id','left')
            ->where($where)->field("rr.eq_code,eg.group_name,count(rr.id) as number,ifnull(s.address,'*') as peg")->group('rr.eq_code')->order('number desc')->page($page,$limit)->select();


        return json(['status'=>1,'msg'=>'查询成功','count'=>$count,'info'=>$list]);
    }
}