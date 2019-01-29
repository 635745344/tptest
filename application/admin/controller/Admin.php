<?php
namespace app\admin\controller;
use think\Controller;
use app\library\AdminHelper;
use app\library\DbHelper;
use think\Db;
use think\Config;
use app\library\ExcelHelper;
use app\common\controller\Base;
use think\Request;

class Admin extends Base
{
	public function __construct(){
		parent::__construct();
        $this->M = new \app\common\model\Admin;
    }
	//登录后台首页
	public function index()
    {
		$admin=AdminHelper::get_admin();
//		echo $this->get_menu();exit();
//        var_dump($this->get_menu());
		return view('',['menu'=>$this->get_menu(),'admin'=>$admin]);
	}
	//用户管理
	public function index2(){
        return view();
	}
	public function index3(){
        $start_time=strtotime(date('Y-m-d',time()));
        $end_time=$start_time+3600*24;

        //今日出烟数量
        $where['create_time'] = ['between',[$start_time,$end_time]];
        $where['status'] = 1;
        $where['is_callblack'] = 1;
        $smoke_count = Db::name('receive_record')->where($where)->count();

        //今日领烟用户
        $member_count = Db::name('receive_record')->where($where)->group('openid')->count();

        //今日新增用户
        $new_member = Db::name('user')->where(['status'=>1,'create_time'=>['between',[$start_time,$end_time]]])->count();

        //总用户数
        $member_sum = Db::name('user')->where(['status'=>1])->count();
        return view('',['smoke_count'=>$smoke_count,'member_count'=>$member_count,'new_member'=>$new_member,'member_sum'=>$member_sum]);

    }

    //添加人员视图
    public function addView(){return view();}
	//查询
	public function lists($page,$limit)
	{
		$where=[];
		if($params=input('post.')){
			if(isset($params['search'])){
				$where['a.account']=['like','%'.$params['search'].'%'];
			}
		}

		$data=$this->M->alias('a')
				  ->join('sp_role r','a.role_id = r.id','LEFT')
				  ->where($where)
				  ->field('a.id,a.account,a.status,a.last_login_time,a.create_time,r.name role_name')
				  ->order('a.create_time desc')
				  ->page($page,$limit)
				  ->select();

		$count=$this->M->alias('a')->where($where)->count();
		return r_page(1,$data,$count);
	}
	//添加
	public function add()
	{
		$model=input('post.');
		$this->M->check($model,'add');
		if( empty($model['head_img'])){
			$model['head_img']='/static/image/admin/default-avatar.svg';
		}

		$model['password'] = AdminHelper::encrypt(config('admin.default_pwd'));
		$result = $this->M->save($model);
		return r_oper($result);
	}
	//编辑
	public function edit()
	{
		$model=input('post.');
		$this->M->check($model,'edit');
		if( empty($model['head_img'])){
			$model['head_img']='/static/image/admin/default-avatar.svg';
		}
		$result=$this->M->allowField(['account','role_id'])->save($model,['id'=>$model['id']]);
		return r_oper($result);
	}
	//查看单一数据
	public function model($id)
	{
		return $this->M->field('id,account,role_id')->find($id);
	}
	//删除
	public function del($id)
	{
		return r_oper($this->M->destroy($id));
	}
	//启用或禁用
	public function status($id,$status)
	{
		return r_oper($this->M->save(['status' => $status],['id'=>['in',explode(',',$id)]]));
	}
	//导入预览
    public function importPreview()
    {

    }
	//导入
	public function import()
	{
		$Execl=new ExcelHelper();
		$data = $Execl->importExecl($_FILES['file']['tmp_name'],$_FILES['file']['name']);
		$data = ExcelHelper::convert_data(['account'=>'账号名称','status'=>'状态'],$data);
		$password=AdminHelper::encrypt(config('admin.default_pwd'));

		foreach ($data as $key => $value) {
			$data[$key]['create_time']=time();
			$data[$key]['role_id']=2;
			$data[$key]['password']=$password;
			$data[$key]['status']= ($value['status']=='禁用'?'0':'1');
		}
		return r_oper($this->M->saveAll($data));
	}
	//导出
	public function export()
	{
		$where=[];
		if($params=input('post.')){
			if(isset($params['search'])&&$params['search']!=''){
				$where['a.account']=['like','%'.$params['search'].'%'];
			}
		}
		$data=Db::name('admin')->alias('a')
				  ->join('sp_role r','a.role_id = r.id','LEFT')
				  ->where($where)
				  ->field('a.account,if(a.status=1,\'启用\',\'禁用\') status,FROM_UNIXTIME(a.last_login_time) last_login_time,FROM_UNIXTIME(a.create_time) create_time,r.name role_name')
				  ->select();
  	    
		$Execl=new ExcelHelper();
		$Execl->exportExcel('用户名单',['账号名称','状态','最后登录时间','创建时间','角色名称'],$data);
		return;
	}
	//修改密码
	public function edit_pwd($password)
	{
	    if(empty($password)){
             return r(0,'密码不能为空！');
        }
		return r_oper($this->M->save(['password'=>AdminHelper::encrypt($password)],['id'=>AdminHelper::get_admin()['id']]));
	}
	//重置密码
	public function reset_pwd($id)
	{
		return r_oper($this->M->save(['password'=>AdminHelper::encrypt(config('admin.default_pwd'))],['id'=>$id]));
	}

    /**
     * [获取授权菜单列表]
     */
    public function get_menu()
    {
        $user_info = AdminHelper::get_admin();
        $where=[];
        //是否超级管理员角色
        if(config('admin.admin_role_id') != $user_info['role_id']){
            // 获取用户权限ids
            $power_ids= Db::name('admin')->alias('a')
                ->join('sp_role r','a.role_id=r.id','LEFT')
                ->where(['a.id'=>$user_info['id'],'a.status'=>1,'r.status'=>1])
                ->field('power_ids')
                ->find()['power_ids'];
            $where['p.id']=['in',explode(',',$power_ids)];
        }
        $where['m.status']=1;
        $where['p.status']=1;
        $where['p.is_menu']=1;
        $data=DB::name('menu')->alias('m')
            ->join('sp_power p','m.id=p.menu_id')
            ->where($where)
            ->field('m.id,m.name,parent_id,icon,sort,url')
            ->select();

        return json_encode($data);
//        return json();
    }
}