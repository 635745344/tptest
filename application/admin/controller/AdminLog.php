<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use app\common\controller\Base;

class AdminLog extends Base
{
	public function __construct(){
		parent::__construct();
        $this->M = new \app\common\model\AdminLog;
    }
	public function index() { return view(); }
	//查询
	public function lists($page,$limit)
	{
		$where=[];
		if($params=input('post.')){
			if(isset($params['search']) && $params['search']!=''){
				$where['a.account']=['like','%'.$params['search'].'%'];
			}
			if(!empty($params['start_time']) && !empty($params['end_time'])){
				$where['al.create_time']=['between',[$params['start_time'],$params['end_time'] ]];
			}else{
				if(!empty($params['start_time'])){
					$where['al.create_time']=['>=',$params['start_time']];
				}
				if(!empty($params['end_time'])){
					$where['al.create_time']=['<=',$params['end_time']];
				}
			}
		}
		$data=Db::name('admin_log')->alias('al')
				  ->join('sp_admin a','al.admin_id=a.id','left')
				  ->join('sp_oper_type ot','al.type_id=ot.id','left')
				  ->where($where)
				  ->field('a.account,ot.name,al.remark,al.ip,al.create_time')
				  ->order('al.create_time desc')
				  ->page($page,$limit)
				  ->select();

		$count=$this->M->alias('al')
				  ->join('sp_admin a','al.admin_id=a.id','left')
				  ->where($where)->count();

		return r_page(1,$data,$count);
	}
	//获取最新登录日志
	public function new_log()
	{
		$data=Db::name('admin_log')
				  ->where(['admin_id'=>1])
				  ->field('create_time')
				  ->order('create_time desc')
				  ->limit(5)
				  ->select();
		return json($data);
	}
}