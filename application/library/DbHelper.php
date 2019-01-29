<?php
namespace app\library;
use think\Db;
use think\Config;
use think\Session;
use think\Response;

//数据库帮助类
class DbHelper
{
	/**
	 * 去除不需要字段
	 * @param  [array] $model      [原始数据]
	 * @param  [array] $save_field [需要保留的字段]
	 * @return [array]             [保留字段]
	 */
	public static function field_filter($model,$save_field)
	{
		$new_save_field=array();
		foreach ($save_field as $key => $value) {
			$new_save_field[$value]=$model[$value];
		}
		return array_intersect($model,$new_save_field);
	}
}