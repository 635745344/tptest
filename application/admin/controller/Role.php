<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use app\common\controller\Base;

class Role extends Base
{
	public function __construct(){
		parent::__construct();
        $this->M = new \app\common\model\Role;
    }
	//登录后台首页
	public function index(){
		return view();
	}
	//查询	
	public function lists($page,$limit)
	{
		$data=$this->M->field('id,name,status,remark')->order('id desc')->page($page,$limit)->select();
		$count=$this->M->count();
		return r_page(1,$data,$count);
	}
	//添加
	public function add()
	{
		$model=input('post.');
		$this->M->check($model,'add');
		$result = $this->M->allowField('name,remark')->save($model);
		return r_oper($result);
	}
	//编辑
	public function edit()
	{
		$model=input('post.');
		$this->M->check($model,'edit');
		$result=$this->M->allowField(['id','name','remark'])->save($model,['id'=>$model['id']]);
		return r_oper($result);
	}
	//查看单一数据
	public function model($id)
	{
		return $this->M->field('id,name,remark')->find($id);
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
	/**
	 * 获取全部权限列表
	 * @param  [type] $id [角色id]
	 */
	public function get_all_power()
	{
		$data = Db::name('menu')->alias('m')
					->join('sp_power p','m.id=p.menu_id')
					->where(['m.status'=>1,'p.status'=>1])
					->field('m.parent_id,m.sort,p.menu_id,p.id as power_id,p.is_menu,if(p.is_menu=1,m.name,p.name) name')
					->select();
		return $data;
	}
	/**
	 * 获取角色权限列表
	 * @param  [type] $id [角色id]
	 */
	public function get_power($id)
	{		
		// $user_info = AdminHelper::get_admin();
		// $user_info = ['role_id'=>1];
		//是否超级管理员角色
		if(config('admin.admin_role_id') == $id){
			$data=Db::name('power')->field('id')->select();
			$power_ids=[];
			foreach ($data as $key => $value){
				$power_ids[]=$value['id'];
			}
			return array('power_ids' =>implode(',',$power_ids));
		}

		return $this->M->where(['id'=>$id])->field('power_ids')->find();
	}
	//设置权限
	public function set_power($id,$power_ids)
	{
		return r_oper($this->M->save(['power_ids' => $power_ids],['id'=>$id]));
	}
	//获取角色列表
	public function get_list(){
		return json($this->M->field('id,name')->select());
	}
}