<?php
namespace app\group\controller;
use think\Controller;
use think\Log;
use think\Request;
use think\Db;
use think\session;
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
		$where['user_name'] = $account;
		$where['status'] = 1;
		$check = Db::name('group_user')->where($where)->find();
		if($check)
		{
		    $salt = $check['salt'];
            $pwd = md5($salt.md5($password));

            if($pwd == $check['password'])
            {
                $data['id'] = $check['id'];
                $data['user_name'] = $check['user_name'];
                $data['add_time'] = $check['add_time'];
                session('group_user',$data);
                session('power_id',$check['power_id']);
                session('group_id',$check['group_id']);
                return r(1,'登录成功！',array('data'=>url('group/admin/index')));
            }else{
                return r(0,'账号密码错误！');

            }

        }else{

            return r(0,'账号不存在');

        }

	}
	//退出登录
	public function login_out()
	{
        session('group_user',null);
        session('power_id',null);
        session('group_id',null);
		return r(1,'退出成功！',array('data'=>url('group/login/login')));
	}
}
