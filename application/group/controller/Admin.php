<?php
namespace app\group\controller;
use think\Controller;
use app\library\DbHelper;
use think\Db;
use think\Config;
use app\library\ExcelHelper;
use think\Request;

class Admin extends Check
{
	public function __construct()
    {
        $this->group_id = session('group_id');
        $this->power_id = session('power_id');
        $this->group_user_id = session('group_user.id');
        $this->new_view = Db::name('equipment_group')->where(['id'=>$this->group_id])->value('new_view');
		parent::__construct();
    }
	//登录后台首页
	public function index()
    {
        $user = session('group_user');
        $user['head_img'] = 'http://img.eacoomall.com/images/static/assets/img/default-avatar.svg';
		return view('',['menu'=>json_encode($this->get_group_menu()),'admin'=>$user]);
	}
	//用户管理
	public function index2(){return view();}
	public function index3()
    {
        $start_time=strtotime(date('Y-m-d',time()));
        $end_time=$start_time+3600*24;

        //今日出烟数量
        $where['rr.create_time'] = ['between',[$start_time,$end_time]];
        $where['rr.status'] = 1;
        $where['eq.group_id'] = $this->group_id;

        if($this->new_view==1) $where['rr.is_callblack'] = 1;

        $smoke_count = Db::name('receive_record')->alias('rr')
                        ->join('sp_equipment eq','rr.eq_code=eq.code','left')->where($where)->count();

        //今日领烟用户
        $member_count = Db::name('receive_record')->alias('rr')
            ->join('sp_equipment eq','rr.eq_code=eq.code','left')->where($where)->group('rr.openid')->count();

        //当前在线机器数
        $online_eq = Db::name('equipment')->where(['group_id'=>$this->group_id,'status'=>1])->count();

        //累计出烟数
        $where2['rr.status'] = 1;
        $where2['eq.group_id'] = $this->group_id;
        if($this->new_view==1) $where2['rr.is_callblack'] = 1;

        $sum = Db::name('receive_record')->alias('rr')
            ->join('sp_equipment eq','rr.eq_code=eq.code')->where($where2)->count();
        return view('',['smoke_count'=>$smoke_count,'member_count'=>$member_count,'online_eq'=>$online_eq,'sum'=>$sum]);
	}



	//修改密码
	public function edit_pwd($password)
	{
	    if(empty($password))
	    {
            return json(['status'=>0,'info'=>'密码不能为空']);
        }

        $salt = create_password(6);
        $pwd = md5($salt.md5($password));
        Db::name('group_user')->where(['id'=>$this->group_user_id])->update(['salt'=>$salt,'password'=>$pwd]);
        return json(['status'=>1,'info'=>'修改成功']);
	}

}