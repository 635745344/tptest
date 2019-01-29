<?php
namespace app\admin\controller;
use think\Controller;
use think\Log;
use think\Request;
use app\library\AdminHelper;
use think\Db;
use think\Config;

class Login extends Controller
{
	//登录页
	public function login()
	{
		return view();
	}

    /**
	 * [用户登录验证]
	 * @param  [string] $account  [用户账号]
	 * @param  [string] $password [密码]
	*/
	public function login_verify($account='',$password='',$code='')
	{
		if(!$code){
			return r(0,'验证码不能为空！');
		}
		if(!captcha_check($code)){
			return r(0,'验证码错误！');
		}

		if(!$account || !$password){
			return r(0,'用户名或密码不能为空！');
		}
		$data = Db::name('admin')->where(['account'=>$account,'password'=>AdminHelper::encrypt($password)])->find();
		if($data){
			if($data['status']){
				AdminHelper::login_after($data);
				return r(1,'登录成功！',array('data'=>url('admin/admin/index')));
			}else{
				return r(0,'账号已被禁用！');
			}
		}else{
			return r(0,'用户名或密码错误！');
		}
	}
	//退出登录
	public function login_out()
	{
		AdminHelper::clear_login();
		return r(1,'退出成功！',array('data'=>url('admin/login/login')));
	}
}
