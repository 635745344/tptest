<?php
namespace app\common\model;
use think\Model;
use think\Request;
use think\Response;
use think\Session;
use think\Db;
use app\library\AdminHelper;
use think\Config;

class Admin extends Model
{
	protected $autoWriteTimestamp = true;
    protected $updateTime = false;
    
	/**
	 * [检验字段]
	 * @param  [type] $model [数据模型]
	 * @param  [type] $type  [类型（add,edit）]
	 */
	public static function check($model,$type='')
	{
		$data=array();
		$account=trim($model['account']);
		if(empty($model['account']) || empty($account) ){
			$data[]=array('name'=>'account','msg'=>'用户名不允许为空');
		}
		else{
			if($type=='add'){
				if(Db::name('admin')->where('account',$model['account'])->count()){
					$data[]=array('name'=>'account','msg'=>'用户名已存在');
				}
			}
			else if($type=='edit'){
				if(Db::name('admin')->where(['account'=>$model['account'],'id'=>['<>',$model['id']]])->count()){
					$data[]=array('name'=>'account','msg'=>'用户名已存在');
				}
			}
		}

		if($model['role_id']==0){
			$data[]=array('name'=>'role_id','msg'=>'必选');
		}
		// else if(AdminHelper::get_admin()['role_id']!=Config::get('admin')['admin_role_id'] 
		// 	&& $model['role_id'] == Config::get('admin')['admin_role_id'] ){
		// 	$data[]=array('name'=>'role_id','msg'=>'您没有权限添加或修改超级管理员角色');
		// }

		if($data){
			$result = r(0,'',array('data'=>$data));
			exit(json_encode($result));
		}

	}
}