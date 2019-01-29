<?php
namespace app\common\model;
use think\Model;

class WxConfig extends Model
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
		if(empty($model['type']) || empty(trim($model['type'])) ){
			$data[]=array('name'=>'type','msg'=>'回复类型不能为空');
			// trigger_type
		}
		if(empty($model['trigger_type']) || empty(trim($model['trigger_type'])) ){
			$data[]=array('name'=>'trigger_type','msg'=>'触发类型不能为空');
		}
		if(empty($model['appid']) || empty(trim($model['appid'])) ){
			$data[]=array('name'=>'appid','msg'=>'公众号不能为空');
		}
		if($data){
			$result = r(0,'',array('data'=>$data));
			exit(json_encode($result));
		}
	}
}