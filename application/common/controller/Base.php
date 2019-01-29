<?php
namespace app\common\controller;
use think\Controller;
use think\Session;
use app\library\AdminHelper;
use think\Db;
use think\Request;

/*
	基础控制类
 */
class Base extends Controller
{
    private $user_info;

    /**
     *
     */
    protected function _initialize(){
		$this->user_info=AdminHelper::get_admin();
		//登录验证
		if(empty($this->user_info)){
			exit("<script>if(confirm('登录超时，是否重新登录？')){window.location.href = '/admin/login/login';}</script>");
		}
		//权限验证
		if(!$this->power_verify()){
			exit(json_encode(['status'=>0,'msg'=>'亲，您没有访问权限！']));
		}
	}

	/**
	 * [权限验证]
	 * @return [bool] [是否具有权限]
	 */
	public function power_verify()
	{
		//查看是否超级管理员
		if($this->user_info['role_id']==config('admin.admin_role_id')){
			return true;
		}

		//查看是否有缓存
        $ban_urls=Session::get("ban_urls");
		if(empty($ban_urls))
		{
			// 获取用户权限ids
			$power_ids = explode(',',Db::name('role')->where(['id'=>$this->user_info['role_id']])->find()['power_ids']);
			//查询是否有权限
			$power_urls=[];
			foreach (Db::name('power')->where(['id'=> array('in',$power_ids)])->select() as $key => $value) {
				foreach (explode(',',$value['url']) as $key2 => $value2) {
					if(!empty($value2)){
						$power_urls[]=strtolower($value2);
					}
				}
			}
            
			$power_urls=array_unique($power_urls);

			$power_urls_all=[];

			foreach (Db::name('power')->select() as $key => $value) {
				foreach (explode(',',$value['url']) as $key2 => $value2) {
					if(!empty($value2)){
						$power_urls_all[]=strtolower($value2);
					}
				}
			}

			$ban_urls=array_diff($power_urls_all, $power_urls);

			Session::set('ban_urls',$ban_urls);
		}
        
		$request = Request::instance();
		$url = strtolower($request->module().'/'.$request->controller().'/'.$request->action());

		return in_array($url,Session::get('ban_urls')) ? false : true;
	}
}

