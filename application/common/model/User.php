<?php
namespace app\common\model;
use think\Model;
use think\Db;
use think\Validate;

class User extends Model
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
		if(empty($model['account'])){
			$data[]=array('name'=>'account','msg'=>'用户名不允许为空');
		}
		if(empty($model['nickname'])){
			$data[]=array('name'=>'nickname','msg'=>'昵称不允许为空');
		}
		if($model['email']!='' && !preg_match("/\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/",$model['email'])){
			$data[]=array('name'=>'email','msg'=>'邮箱格式不正确');
		}
		if($model['qq']!='' && !preg_match('/[1-9][0-9]{4,}/',$model['qq'])){
			$data[]=array('name'=>'qq','msg'=>'qq格式不正确');
		}
		if($model['phone']!='' && !preg_match('/^(13[0-9]|14[5|7]|15[0|1|2|3|5|6|7|8|9]|18[0|1|2|3|5|6|7|8|9])\d{8}$/',$model['phone'])){
			$data[]=array('name'=>'phone','msg'=>'手机格式不正确');
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
		if($data){
			$result = r(0,'',array('data'=>$data));
			exit(json_encode($result));
		}
	}
}