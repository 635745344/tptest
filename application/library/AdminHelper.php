<?php
namespace app\library;
use think\Db;
use think\Config;
use think\Session;
use think\Response;
use think\Request;

class AdminHelper
{
	/**
	 * [密码加密]
	 * @param  [string] $scource_pwd [未加密密码]
	 * @return [string]              [加密后密码]
	 */
	public static function encrypt($scource_pwd){
		return md5($scource_pwd .  Config::get('admin.login_key'));
	}

	/**
	 * [获取登录管理员信息]
	 * @return [array] [admin表信息]
	 */
	public static function get_admin(){
		$user_info=Session::get('user_info');
		if(empty($user_info)){
			self::is_login();
		}
		return Session::get('user_info');
	}

	/**
	 * [登录成功后添加Session信息]
	*/
	public static function login_after($data)
	{
		$request = Request::instance();

		$login_token= Db::name('admin_token')->where('token',session_id())->find();
		$login_expire=self::login_expire();
		if(!$login_token){ 	//不存在时
			Db::name('admin_token')
				->insert(['admin_id'=>$data['id'],'token'=>session_id(),'expires'=>$login_expire]);
		}else{
			if($login_token['expires']>time()){
				Db::name('admin_token')
					->where('id', $login_token['id'])
					->update(['expires'=>time()]);
			}
		}
//        $ip=ip();
		Db::name('admin_log')->insert([
			'admin_id'=>$data['id'],
			'create_time'=>time(),
			'ip'=>$request->ip(),
			'type_id'=>1
		]);
//        Db::name('admin')->where(['id'=>$data['id']])->save(['id'=> get_client_ip(),'last_login_time'=>time(),'login_times'=>]);
        Db::execute(" update sp_admin set last_login_ip=:last_login_ip , last_login_time=:last_login_time,login_times=login_times+1 where id=:id",
            ['last_login_ip'=>$request->ip(),'last_login_time'=>time(),'id'=>$data['id']]);
		Session::set('user_info',$data);
	}

	/**
	 * [检查是否已经登录]
	*/
	public static function is_login()
	{
	    $user_info=Session::get('user_info');
		if(!empty($user_info)) //Session不存在时
		{ 
			$login_token = Db::name('admin_token')->where('token',session_id())->field('id,admin_id,expires')->find();
			if($login_token){
				//检查是否过期
				if($login_token['expires']>time()){
					$admin_info= Db::name('admin')->where('admin_id',$login_token['admin_id'])->find();
					if($admin_info['status']=0){
						Response::create(r(0,'账号已被禁用！'),'json');
						exit();
					}
					Session::set('user_info',$admin_info);
				}else{
					//删除过期token
					Db::name('admin_token')->where('expires','<=',time())->delete();
					Response::create("<script>if(confirm('登录超时，是否重新登录？')){window.location.href = '/admin/login/login';}</script>");
					exit();
				}
			}else{
				Response::create(r(0,'检查到您未登录，请先登录！'),'json');
				exit();
			}
		}else{ //存在Session时
			//重新设置Seesion过期时间
			Session::set('user_info',Session::set('user_info'));
		}
	}
	/**
	 * [清除登录信息]
	 */
	public static function clear_login()
	{
		Session::delete('user_info');
		Db::name('admin_token')->where('token',session_id())->delete();
	}
	/**
	 * [登录有效期]
	 */
	public static function login_expire(){
		return Config::get('session.expire')+time();
	}
}
