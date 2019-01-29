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

class DataView extends Base
{
   
    public function index()
   {
       $group_list = Db::name('equipment_group')->where(['status'=>1])->field('id as group_id,group_name')->select();
        return view('',['group_list'=>json_encode($group_list)]);
   }

   public function dataone()
   {
       $group_list = Db::name('equipment_group')->where(['status'=>1])->field('id as group_id,group_name')->select();
       return view('',['group_list'=>json_encode($group_list)]);
   }

    public function datatwo()
    {
        $group_list = Db::name('equipment_group')->where(['status'=>1])->field('id as group_id,group_name')->select();
        return view('',['group_list'=>json_encode($group_list)]);
    }

    /*******************************************接口部分****************************************************************************************************/

    /*
     * @某时间段出烟统计
     * @参数 ： group_id  start_day  end_day  day
     * @return json
     */

    public function day_smoke_data()
    {
        $group_id = trim(input('group_id'));
        $start_day = trim(input('start_day'));
        $end_day = trim(input('end_day'));
        $day = trim(input('day')); //7 30 60天数

        $start_day = $start_day==''?strtotime(date('Y-m-d',time() - 86400*7)):strtotime($start_day);
        $end_day = $end_day==''?strtotime(date('Y-m-d',time())):strtotime($end_day)+86400;

        if($group_id > 0)
        {
            $where['eq.group_id'] = $group_id;
        }

        if($day>0)
        {
            $start_day = strtotime(date('Y-m-d',time() - 86400 * $day));
            $end_day = strtotime(date('Y-m-d',time()));
        }


        $where['rr.create_time'] = ['between',[$start_day,$end_day]];
        $where['rr.status'] = 1;
        $where['rr.is_callblack'] = 1;

        $count = Db::name('receive_record')->alias('rr')
            ->join('sp_equipment eq','rr.eq_code=eq.code','left')
            ->where($where)->field("count(rr.id) as number,FROM_UNIXTIME(rr.`create_time`,'%Y/%m/%d') as create_day")->group('create_day')->count();

        $page = $day==7?7:10;
        $limit = ceil($count/$page);

        $day_list = '';
        $count_list = '';

        for ($i=1;$i<=$page;$i++){

            $list = Db::name('receive_record')->alias('rr')
                ->join('sp_equipment eq','rr.eq_code=eq.code','left')
                ->where($where)->field("count(rr.id) as number,FROM_UNIXTIME(rr.`create_time`,'%Y/%m/%d') as create_day")->group('create_day')->page($i,$limit)->select();

            $k = count($list);
            $number = 0;
            foreach ($list as $key => $value){
                $number = $number + $value['number'];
            }
            if($k >0 )
            {
                if($page==7)
                {
                    $ts = $list[0]['create_day'];
                }else{
                    $ts = $list[0]['create_day'].'~'.$list[$k-1]['create_day'];
                }
                $count_list[] = $number;
                $day_list[] = $ts;
            }
        }

        if(empty($count_list) || empty($day_list))
        {
            $start_day = strtotime(date('Y-m-d',time() - 86400 * 7));
            for ($i=1;$i<=7;$i++)
            {
               $count_list[] = 0;
               $day_list[] = date('Y/m/d',$start_day);
               $start_day = $start_day + 86400;
            }
        }



        return json(['status'=>1,'msg'=>'查询成功','count_list'=>$count_list,'day_list'=>$day_list]);

    }

    /*
     * @某时间段机器出烟数量排名
     * @参数 ： group_id  start_day  end_day day
     * @return json
     */
    public function machine_smoke_data()
    {
        $group_id = trim(input('group_id'));
        $start_day = trim(input('start_day'));
        $end_day = trim(input('end_day'));
        $day = trim(input('day')); //7 30 60天数
        $page = trim(input('page'))==''?1:trim(input('page'));
        $limit = trim(input('limit'))==''?10:trim(input('limit'));

        $start_day = $start_day==''?strtotime(date('Y-m-d',time() - 86400*7)):strtotime($start_day);
        $end_day = $end_day==''?strtotime(date('Y-m-d',time())):strtotime($end_day)+86400;

        if($group_id > 0)
        {
            $where['eq.group_id'] = $group_id;
        }

        if($day>0)
        {
            $start_day = strtotime(date('Y-m-d',time() - 86400 * $day));
            $end_day = strtotime(date('Y-m-d',time()));
        }

        $where['rr.create_time'] = ['between',[$start_day,$end_day]];
        $where['rr.status'] = 1;
        $where['rr.is_callblack'] = 1;

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

    /*
     * @某时间段扫码手机系统
     * @参数 ： start_day  end_day day
     * @return json
    */
    public function system_rank()
    {

        $start_day = trim(input('start_day'));
        $end_day = trim(input('end_day'));
        $day = trim(input('day')); //7 30 60天数
        $group_id = trim(input('group_id'));


        $start_day = $start_day==''?strtotime(date('Y-m-d',time() - 86400*7)):strtotime($start_day);
        $end_day = $end_day==''?strtotime(date('Y-m-d',time())):strtotime($end_day)+86400;

        if($day>0)
        {
            $start_day = strtotime(date('Y-m-d',time() - 86400 * $day));
            $end_day = strtotime(date('Y-m-d',time()));
        }

        if($group_id>0){
            $where['group_id'] = $group_id;
        }

        $where['create_time'] = ['between',[$start_day,$end_day]];


        $count = Db::name('v_scan_code_one')->where($where)->group('os')->count();

        $list = Db::name('v_scan_code_one')->where($where)->field("COUNT(os) AS `value`,os as `name`")
            ->group('os')->order('value desc')->select();


        return json(['status'=>1,'msg'=>'查询成功','count'=>$count,'info'=>$list]);
    }

    /*
     * @某时间段扫码手机型号
     * @参数 ： start_day  end_day day
     * @return json
    */
    public function model_rank()
    {

        $start_day = trim(input('start_day'));
        $end_day = trim(input('end_day'));
        $day = trim(input('day')); //7 30 60天数
        $page = trim(input('page'))==''?1:trim(input('page'));
        $limit = trim(input('limit'))==''?5:trim(input('limit'));
        $group_id = trim(input('group_id'));


        $start_day = $start_day==''?strtotime(date('Y-m-d',time() - 86400*7)):strtotime($start_day);
        $end_day = $end_day==''?strtotime(date('Y-m-d',time())):strtotime($end_day)+86400;

        if($day>0)
        {
            $start_day = strtotime(date('Y-m-d',time() - 86400 * $day));
            $end_day = strtotime(date('Y-m-d',time()));
        }

        if($group_id>0){
            $where['group_id'] = $group_id;
        }

        $where['create_time'] = ['between',[$start_day,$end_day]];


        $count = Db::name('v_scan_code_one')->where($where)->group('model')->count();

        $list = Db::name('v_scan_code_one')->where($where)->field("COUNT(model) AS `value`,model as `name`")
            ->group('model')->order('value desc')->select();

        $n_list = $list;
        $sum = 0;
        foreach ($list as $key =>$value){
            if($key>9)
            {
                $sum = $sum +$value['value'];
               unset($n_list[$key]);
            }
        }

        if($sum>0){
            $n_list[10] = ['value'=>$sum,'name'=>'其他'];
        }

//        if(is_array($list)) $list = $this->sum_model($list);

        $data = Db::name('v_scan_code_one')->where($where)->field('model as name,count(model) as value')
            ->group('name')->order('value desc')->page($page,$limit)->select();


        return json(['status'=>1,'msg'=>'查询成功','count'=>$count,'info'=>$n_list,'page'=>$data]);
    }

    /*
     * @某时间段领烟数量排名
     * @参数 ： start_day  end_day day
     * @return json
    */

    public function user_smoke_data()
    {
        $start_day = trim(input('start_day'));
        $end_day = trim(input('end_day'));
        $group_id = trim(input('group_id'));
        $day = trim(input('day')); //7 30 60天数
        $page = trim(input('page'))==''?1:trim(input('page'));
        $limit = trim(input('limit'))==''?5:trim(input('limit'));

        $start_day = $start_day==''?strtotime(date('Y-m-d',time() - 86400*7)):strtotime($start_day);
        $end_day = $end_day==''?strtotime(date('Y-m-d',time())):strtotime($end_day)+86400;

        if($day>0)
        {
            $start_day = strtotime(date('Y-m-d',time() - 86400 * $day));
            $end_day = strtotime(date('Y-m-d',time()));
        }

        if($group_id > 0 ){
            $where['eq.group_id'] = $group_id;
        }

        $where['rr.create_time'] = ['between',[$start_day,$end_day]];
        $where['rr.status'] = 1;
        $where['rr.is_callblack'] = 1;

        $count = $list = Db::name('receive_record')->alias('rr')
            ->join('sp_equipment eq','rr.eq_code=eq.code','left')
            ->where($where)->group('rr.openid')->count();

        $list = Db::name('receive_record')->alias('rr')
            ->join('sp_user u','rr.openid=u.openid','left')
            ->join('sp_scan_code sc','rr.openid=sc.openid','left')
            ->join('sp_equipment eq','rr.eq_code=eq.code','left')
            ->where($where)->field("ifnull(u.nickname,'*') as nickname,ifnull(sc.city,'*') as city,u.phone,count(rr.openid) as number")
            ->group('rr.openid')->order('number desc')->page($page,$limit)->select();

        return json(['status'=>1,'msg'=>'查询成功','count'=>$count,'info'=>$list]);
    }

    /*
     * @某天早上 8点到晚上 12点 每2个小时时间统计
     * @参数 ：   group_id   day
     * @return json
    */

    public function smoke_hour_data()
    {
        $group_id = trim(input('group_id'));
        $day = trim(input('day'))==''?strtotime(date('Y-m-d',time() - 86400)):strtotime(trim(input('day'))); //前一天

        if($group_id>0) $where['eq.group_id'] = $group_id;

        $star_ts = $day;
        $end_ts = $day + 86400;
        $where['rr.create_time'] = ['between',[$star_ts,$end_ts]];
        $where['rr.status'] = 1;
        $where['rr.is_callblack'] = 1;

        $day_list = Db::name('receive_record')->alias('rr')
                    -> join('sp_equipment eq','rr.eq_code=eq.code','left')
                    ->where($where)
                    ->field("count(rr.id) as number,FROM_UNIXTIME(rr.`create_time`,'%H') as hour")
                    ->group('hour')->order('hour asc')->select();

        if(is_array($day_list)) $data = $this->dispose($day_list);

        return json(['status'=>1,'msg'=>'查询成功','info'=>$data,'select_day'=>date('Y-m-d',$day)]);

    }

    /*
     * @某时间段产品数量统计
     * @参数 ：start_day  end_day
     * @return json
    */

    public function goods_data()
    {
        $start_day = trim(input('start_day'));
        $end_day = trim(input('end_day'));
        $group_id = trim(input('group_id'));
        $day = trim(input('day'));

        $start_day = $start_day==''?strtotime(date('Y-m-d',time() - 86400*7)):strtotime($start_day);
        $end_day = $end_day==''?strtotime(date('Y-m-d',time())):strtotime($end_day)+86400;
        if($day>0)
        {
            $start_day = strtotime(date('Y-m-d',time() - 86400 * $day));
            $end_day = strtotime(date('Y-m-d',time()));
        }

        $where['create_time'] = ['between',[$start_day,$end_day]];
        $goods_id_list = Db::name('v_goods_count')->where($where)->field('goods_id,goods_name')->group('goods_id')->select();

//        $list = Db::name('v_goods_count')->where($where)->select();
        if($group_id>0) {
            $where2 = " AND eq.group_id = $group_id";
        }else{
            $where2 = '';
        }

        $sql = "select k.goods_id,k.goods_name from (select `rr`.`goods_id` AS `goods_id`,count(`rr`.`goods_id`) AS `goods_count`,
                date_format(from_unixtime(`rr`.`create_time`),'%Y-%m-%d') AS `create_day`,`g`.`name` AS `goods_name`
                from (`sp_receive_record` `rr` left join `sp_goods` `g` on((`rr`.`goods_id` = `g`.`id`)) left join `sp_equipment` eq on eq.code=rr.eq_code )
                where ((`rr`.`status` = 1) and (`rr`.`is_callblack` = 1)) $where2 and rr.`create_time` BETWEEN $start_day and $end_day
                group by `create_day` order by `rr`.`goods_id`,`create_day`) k group by k.goods_id";
        $goods_id_list = Db::query($sql);


        $r = ($end_day - $start_day) / 86400;

        if($day>7 || $r>7){
            $arr = $this->sum_goods_max($goods_id_list,$start_day,$end_day,$group_id);
        }else{
            $arr = $this->sum_goods($goods_id_list,$start_day,$end_day);
        }

        return json(['status'=>1,'msg'=>'查询成功','info'=>$arr['list'],'time'=>$arr['time'],'goods'=>$arr['goods'],'page'=>$arr['page']]);
    }

    /*
      * @获取店铺经纬度
      * @参数 ：group_id
      * @return json
     */
    public function shop_coordinate($group_id=0)
    {
        if($group_id > 0 ){
            $where['group_id'] = $group_id;
        }

        $where['group_id'] = ['>',-1];
        $list = Db::name('v_eq_coordinate')->where($where)->select();

        return json(['status'=>1,'msg'=>'查询成功','info'=>$list]);
    }


    //******************************************************数据处理方法***********************************************************************
    public function dispose($day_list)
    {
        $arr = [];
        $a = '';
        for ($i=0;$i<24;$i++)
        {
            foreach ($day_list as $key => $value)
            {
                if($i == $value['hour']){
                    $a = ['hour'=>$i,'number'=>$value['number']];
                    break;
                }
            }

            if(is_array($a))
            {
                $arr[$i] = $a;
            }else{
                $arr[$i] = ['hour'=>$i,'number'=>0];
            }

            $a = '';
        }

        foreach ($arr as $key => $value)
        {
            if($key>7)
            {
                $d_list[] = $value['hour'].':00~'.$value['hour'].':59';
                $n_list[] = $value['number'];
            }
        }

        $d['time'] = $d_list;
        $d['number'] = $n_list;

        return $d;

    }

    public function sum_model($list)
    {

        foreach ($list as $key => $value)
        {
            $ban = substr($value['name'] , 0 , 3);
            $b[] = $ban;
        }

        $pp = array_unique($b);
        $arr = '';

        foreach ($pp as $key =>$value)
        {
            $num = 0;
            foreach ($list as $kk => $vv)
            {
                $ban = substr($vv['name'] , 0 , 3);
                if($value == $ban)
                {
                    $num = $num + $vv['value'];
                    $arr[$key]['value'] = $num;
                    $arr[$key]['name'] = $vv['name'];
                }
            }
        }

        return $arr;
    }

    public function sum_goods($goods_id_list,$start_day,$end_day)
    {
        $r = ($end_day - $start_day) / 86400;
        $time = '';
        $goods = '';
        $page = '';
        foreach ($goods_id_list as $key => $value)
        {
            $goods_id = $value['goods_id'];
            $goods_id_list[$key]['type'] = 'line';
            $goods_id_list[$key]['name'] = $value['goods_name'];
            $page[$key]['name'] = $value['goods_name'];
            for ($i=0;$i<$r;$i++)
            {
                $ts = $start_day + 86400 * $i;
                $day = date('Y-m-d',$ts);

                $count = Db::name('v_goods_count')->where(['create_day'=>$day,'goods_id'=>$goods_id])->value('goods_count');
                $goods_id_list[$key]['data'][] = $count==''?0:$count;
                $time[$i] = $day;
                $goods[$key] = $value['goods_name'];
                $page[$key][$day] = $count==''?0:$count;
            }
        }


        $array['list']= $goods_id_list;
        $array['time'] = $time;
        $array['goods'] = $goods;
        $array['page'] = $page;
        return $array;
    }

    public function sum_goods_max($goods_id_list,$start_day='',$end_day='',$group_id=0)
    {
        $r = ($end_day - $start_day) / 86400;
        $p = ceil($r / 10);
        $time = '';
        $goods = '';
        $page = '';

        if($group_id>0) $where['group_id'] = $group_id;

        foreach ($goods_id_list as $key => $value)
        {
            $goods_id = $value['goods_id'];
            $goods_id_list[$key]['type'] = 'line';
            $goods_id_list[$key]['name'] = $value['goods_name'];
            $page[$key]['name'] = $value['goods_name'];
            $sts = $start_day;
            for ($i=0;$i<=9;$i++)
            {
                $ets = $sts + 86400 * $p;

                if($ets > strtotime(date('Y-m-d',time())))
                {
                    $ets = strtotime(date('Y-m-d',time())) - 86400;
                }

                if($sts >= strtotime(date('Y-m-d',time())) || $ets > strtotime(date('Y-m-d',time())))
                {
                    break;
                }

                $where['create_time'] = ['between',[$sts,$ets]];
                $where['goods_id'] = $goods_id;
                $where['status'] = 1;
                $where['is_callblack'] = 1;

                $count = Db::name('v_receive_record')->where($where)->count();

                $goods_id_list[$key]['data'][] = $count==''?0:$count;
                $dl = date('y/m/d',$sts).'~'.date('y/m/d',$ets);
                $time[$i] = $dl;
                $goods[$key] = $value['goods_name'];
                $page[$key][$dl] = $count==''?0:$count;
                $sts  = $ets + 86400;

            }
        }

        $ts2 = '';
        if(is_array($time)) foreach ($time as $k => $v)
        {
            $ts2[] = $v;
        }

        $array['list']= $goods_id_list;
        $array['time'] = $ts2;
        $array['goods'] = $goods;
        $array['page'] = $page;

        return $array;

    }

}