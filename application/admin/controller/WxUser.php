<?php

namespace app\admin\controller;
use think\Controller;
use app\library\DbHelper;
use think\Db;
use think\Config;
use app\library\ExcelHelper;
use app\common\controller\Base;
use think\Request;

class WxUser  extends Base
{
    public function __construct(){
        parent::__construct();
//        $this->db_sx_wechat = Db::connect("db_sx_wechat");
    }

    //首页
    public function index()
    {
        $all_count=Db::name('user')->where(['status'=>1])->count();
        $manage_count = Db::name('manage_account')->where(['status'=>1])->where('openid in (select openid from sp_user)')->count();
        $maintain_count = Db::name('maintain_account')->where(['status'=>1])->where('openid in (select openid from sp_user)')->count();
        $retailer_count = Db::name('retailer_account')->where(['status'=>1])->where('openid in (select openid from sp_user)')->count();

        $other_count = Db::query('select count(1) count from sp_user where openid not in ( select openid from sp_maintain_account where status=1 ) and openid not in ( select openid from sp_retailer_account where status=1 ) and openid not in ( select openid from sp_manage_account where status=1 ) ');

        $other_count=$other_count[0]['count'];

        $group=[
            [
                'id'=>2,
                'name'=>'管理员',
                'count'=>$manage_count,
            ],
            [
                'id'=>3,
                'name'=>'维护员',
                'count'=>$maintain_count,
            ],
            [
                'id'=>4,
                'name'=>'零售户',
                'count'=>$retailer_count,
            ],
            [
                'id'=>1,
                'name'=>'未分组',
                'count'=>$other_count,
            ],
        ];

        return view('',['group'=>json_encode($group),'all_count'=>$all_count]);
    }
    //获取微信用户列表
    public function lists($page=1,$limit=10)
    {
        $where=[];
        if($params=input('post.')){
            if( !empty($params['nickname']) || ( isset($params['nickname']) && $params['nickname']==0) ){
                $where['u.nickname']=['like',$params['nickname'].'%'];
            }
        }

        if(!empty($params['user_group_id'])){
            if($params['user_group_id']==1) //未分组
            {
                $data=Db::name('user')->alias('u')
                    ->join('(select * from sp_manage_account where status=1) mana','u.openid=mana.openid','left')
                    ->join('(select * from sp_maintain_account where status=1) maia','u.openid=maia.openid','left')
                    ->join('(select * from sp_retailer_account where status=1) ra','u.openid=ra.openid','left')
                    ->where($where)
                    ->where('u.openid not in ( select openid from sp_maintain_account where status=1 ) ')
                    ->where('u.openid not in ( select openid from sp_retailer_account where status=1 ) ')
                    ->where('u.openid not in ( select openid from sp_manage_account where status=1 ) ')
                    ->group('u.openid')
                    ->order('u.create_time desc')
                    ->field('u.id,u.nickname,u.headimgurl,mana.id mana_id,maia.id maia_id,ra.id ra_id')
                    ->page($page,$limit)
                    ->select();

                foreach ($data as $k=>$v){
                    $user_group_id=1; //1:未分组
                    if(!empty($v['mana_id'])){ //2:管理员
                        $user_group_id=2;
                    } else if(!empty($v['maia_id'])){ //3:维护员
                        $user_group_id=3;
                    } else if(!empty($v['ra_id'])){ //4:零售户
                        $user_group_id=4;
                    }
                    $data[$k]['user_group_id']=$user_group_id;
                    unset($data[$k]['mana_id']);
                    unset($data[$k]['maia_id']);
                    unset($data[$k]['ra_id']);
                }
                $count=Db::name('user')->alias('u')
                    ->join('(select * from sp_manage_account where status=1) mana','u.openid=mana.openid','left')
                    ->join('(select * from sp_maintain_account where status=1) maia','u.openid=maia.openid','left')
                    ->join('(select * from sp_retailer_account where status=1) ra','u.openid=ra.openid','left')
                    ->where($where)
                    ->where('u.openid not in ( select openid from sp_maintain_account where status=1 ) ')
                    ->where('u.openid not in ( select openid from sp_retailer_account where status=1 ) ')
                    ->where('u.openid not in ( select openid from sp_manage_account where status=1 ) ')
                    ->count();
            }
            else if($params['user_group_id']==2) //管理员
            {
                $where['mana.status']=1;
                $data=Db::name('user')->alias('u')
                    ->join('sp_manage_account mana','u.openid=mana.openid')
                    ->group('u.openid')
                    ->order('u.create_time desc')
                    ->field('u.id,u.nickname,u.headimgurl,2 user_group_id')
                    ->where($where)
                    ->page($page,$limit)
                    ->select();

                $count=Db::name('user')->alias('u')
                      ->join('sp_manage_account mana','u.openid=mana.openid')
                      ->where($where)
                      ->count();
            }
            else if($params['user_group_id']==3) //维护员
            {
                $where['maia.status']=1;
                $data=Db::name('user')->alias('u')
                    ->join('sp_maintain_account maia','u.openid=maia.openid')
                    ->group('u.openid')
                    ->order('u.create_time desc')
                    ->field('u.id,u.nickname,u.headimgurl,3 user_group_id')
                    ->where($where)
                    ->page($page,$limit)
                    ->select();
                $count=Db::name('user')->alias('u')
                    ->join('sp_maintain_account maia','u.openid=maia.openid')
                    ->where($where)
                    ->count();
            }
            else if($params['user_group_id']==4) //零售户
            {
                $where['ra.status']=1;
                $data=Db::name('user')->alias('u')
                    ->join('sp_retailer_account ra','u.openid=ra.openid')
                    ->group('u.openid')
                    ->order('u.create_time desc')
                    ->field('u.id,u.nickname,u.headimgurl,4 user_group_id')
                    ->where($where)
                    ->page($page,$limit)
                    ->select();
                $count=Db::name('user')->alias('u')
                    ->join('sp_retailer_account ra','u.openid=ra.openid')
                    ->where($where)
                    ->count();
            }

        }else{
//            $where['mana.status']=1;
//            $where['maia.status']=1;
//            $where['ra.status']=1;

            $data=Db::name('user')->alias('u')
                ->join('(select * from sp_manage_account where status=1) mana','u.openid=mana.openid','left')
                ->join('(select * from sp_maintain_account where status=1) maia','u.openid=maia.openid','left')
                ->join('(select * from sp_retailer_account where status=1) ra','u.openid=ra.openid','left')
                ->where($where)
                ->group('u.openid')
                ->order('u.create_time desc')
                ->field('u.id,u.nickname,u.headimgurl,mana.id mana_id,maia.id maia_id,ra.id ra_id')
                ->page($page,$limit)
                ->select();

            foreach ($data as $k=>$v){
                $user_group_id=1; //1:未分组
                if(!empty($v['mana_id'])){ //2:管理员
                    $user_group_id=2;
                } else if(!empty($v['maia_id'])){ //3:维护员
                    $user_group_id=3;
                } else if(!empty($v['ra_id'])){ //4:零售户
                    $user_group_id=4;
                }
                $data[$k]['user_group_id']=$user_group_id;
                unset($data[$k]['mana_id']);
                unset($data[$k]['maia_id']);
                unset($data[$k]['ra_id']);
            }

            $count=Db::name('user')->alias('u')
                ->join('(select * from sp_manage_account where status=1) mana','u.openid=mana.openid','left')
                ->join('(select * from sp_maintain_account where status=1) maia','u.openid=maia.openid','left')
                ->join('(select * from sp_retailer_account where status=1) ra','u.openid=ra.openid','left')
                ->where($where)->count();

        }
        return json(['status'=>1,'info'=>'','data'=>$data,'count'=>$count]);
    }
    //分配分组
    public function updateUserGroup($user_id,$user_group_id)
    {
        $data=[
            'user_group_id'=>$user_group_id,
        ];
        $user_id=explode(',',$user_id);
        foreach ($user_id as $k=>$v){
            $user_id_now=$v;

            $user=Db::name('user')->where(['id'=>$user_id_now])->field('id,openid')->find();

            //维护人员表
            $old_maintain = Db::name('maintain_account')->field('id,status,openid')->where(['openid'=>$user['openid']])->find();
            //管理人员表
            $old_manage = Db::name('manage_account')->field('id,status,openid')->where(['openid'=>$user['openid']])->find();
            //零售户人员表
            $old_retailer = Db::name('retailer_account')->field('id,status,openid')->where(['openid'=>$user['openid']])->find();
            if($user_group_id==1){
                if(!empty($old_maintain)){
                    if($old_maintain['status']!==0){
                        Db::name('maintain_account')->where(['id'=>$old_maintain['id']])->update(['status'=>0]);
                    }
                }
                if(!empty($old_manage)){
                    if($old_manage['status']!==0){
                        Db::name('manage_account')->where(['id'=>$old_manage['id']])->update(['status'=>0]);
                    }
                }
                if(!empty($old_retailer)){
                    if($old_retailer['status']!==0){
                        Db::name('retailer_account')->where(['id'=>$old_retailer['id']])->update(['status'=>0]);
                    }
                }
            }

            if($user_group_id==3) //3：维护员
            {
                if(empty($old_maintain)){ //为空添加
                    $data_maintain=[
                        'account'=>'',
                        'name'=>'',
                        'password'=>'',
                        'openid'=>$user['openid'],
                        'status'=>1,
                        'create_time'=>time(),
                        'update_time'=>time(),
                    ];
                    Db::name('maintain_account')->insert($data_maintain);
                }else{
                    Db::name('maintain_account')->where(['id'=>$old_maintain['id']])->update(['openid'=>$user['openid'],'status'=>1]);
                }
            }
        }
        return json(['status'=>1,'info'=>'修改成功']);
    }

}