<?php
/**
 * Created by PhpStorm.
 * User: Colin
 * Date: 2018/10/12
 * Time: 9:48
 */

namespace app\admin\controller;

use think\Controller;
use think\Session;
use think\Db;
use think\Request;
use app\common\controller\Base;

class GroupUser extends Base
{
    //组管理员管理
    /**
     * 页面
     */
    public function index()
    {
        return view();
    }

    //添加页面
    public function add()
    {
        $id = trim(input('id'));
        $group_list = Db::name('equipment_group')->field('id,group_name')->select();
        return view('', ['group_list' => json_encode($group_list)]);
    }


    /**
     * 获取列表
     * @param page limit keyword
     * @return json
     */

    public function get_group_user_list($page = 1, $limit = 10, $user_name = '', $group_name = '')
    {
        if (!empty($user_name)) {
            $where2['gu.user_name'] = ['like', "%$user_name%"];
        }

        if (!empty($group_name)) {
            $where2['eg.group_name'] = ['like', "%$group_name%"];
        }

        $where2['gu.status'] = ['>', -1];

        $power_list = Db::name('group_power')->where(['pid' => 0, 'status' => 1])->field('id as pid,power_name,controller')->order('id asc')->select();

        $pid_str = [];
        foreach ($power_list as $k => $v) {
            $pid_str[] = $v['controller'];
        }

        $count = Db::name('group_user')->alias('gu')
            ->join('equipment_group eg', 'gu.group_id=eg.id', 'left')
            ->where($where2)->count();
        $list = Db::name('group_user')->alias('gu')
            ->join('equipment_group eg', 'gu.group_id=eg.id', 'left')
            ->where($where2)->field('gu.id,gu.user_name,gu.power_id,eg.group_name')->page($page, $limit)->select();
        if (is_array($list)) foreach ($list as $key => $value) {
            $power_id = $value['power_id'];
            $ps_w['id'] = array('in', $power_id);
            $psl = Db::name('group_power')->where($ps_w)->column('controller');
//            $psl_str = '-'.implode('-',$psl);
            foreach ($pid_str as $k => $v) {
//                if(strpos($psl_str,$v) !== false)
                if (in_array($v, $psl)) {
                    $list[$key][$power_list[$k]['controller']] = 1;
                    $list[$key][$power_list[$k]['controller'] . '_id'] = $power_list[$k]['pid'];
                } else {
                    $list[$key][$power_list[$k]['controller']] = 0;
                    $list[$key][$power_list[$k]['controller'] . '_id'] = $power_list[$k]['pid'];
                }
            }

        }

        return json(['status' => 1, 'msg' => '成功', 'info' => $list, 'count' => $count]);

    }


    /**
     * 判断用户名是否存在
     * @param user_name
     * @return json
     */
    public function check_name($user_name)
    {
        if (empty($user_name)) {
            return json(['status' => 0, 'msg' => '参数错误']);
        } else {
            $where['status'] = ['>', -1];
            $where['user_name'] = $user_name;
            $info = Db::name('group_user')->where($where)->find();
            if ($info) {
                return json(['status' => 0, 'msg' => '账号已存在']);
            } else {
                return json(['status' => 1, 'msg' => '账号可用']);
            }
        }
    }

    /**
     * 添加组管理员
     * @param user_name password group_id
     * @return json
     */
    public function edit()
    {
        $user_name = trim(input('user_name'));
        $password = trim(input('password'));
        $group_id = trim(input('group_id'));
        $power_id = trim(input('power_id'));
        $id = trim(input('id'));

        //id不存在执行判断
        if (empty($id)) {
            if (empty($user_name) || empty($password)) {
                return json(['status' => 0, 'msg' => '账号密码不能为空']);
            }

            $check = Db::name('group_user')->where(['user_name' => $user_name])->find();
            if ($check) {
                return json(['status' => 0, 'msg' => '账号已存在']);
            }
        }

        $salt = create_password(6);
        $pwd = md5($salt . md5($password));
        $data = array(
            'user_name' => $user_name,
            'password' => $pwd,
            'group_id' => $group_id,
            'power_id' => $power_id,
            'salt' => $salt,
        );

        if (empty($id)) {
            $res = Db::name('group_user')->insertGetId($data);
        } else {
            $res = Db::name('group_user')->where(['id' => $id])->update($data);
        }

        if ($res) {
            return json(['status' => 1, 'msg' => '添加成功']);
        } else {
            return json(['status' => 0, 'msg' => '操作失败']);
        }

    }

    /**
     * 删除组管理员
     * @param id
     * @return json
     */
    public function delete($user_id = '')
    {
        if (!empty($user_id)) {
            Db::name('group_user')->where(['id' => $user_id])->update(['status' => -1]);
            return json(['status' => 1, 'msg' => '删除账号成功']);
        } else {
            return json(['status' => 0, 'msg' => '参数错误']);
        }
    }


    /**
     * 判断当前组管理员权限
     * 权限划分控制器为单位
     * 无权限断开执行
     */
    public function CheckPower()
    {
        $power_id = session('power_id');
        $request = Request::instance();
        $controller = strtolower($request->controller());
        $where['id'] = array('in', $power_id);
        $power_list = Db::name('group_power')->where($where)->field('id,controller')->select();
        $arr = array_unique(array_map(function ($v) {
            return strtolower($v['controller']);
        }, $power_list));

        if (!in_array($controller, $arr)) {
            print_r('当前用户无权限');
            echo "<script type=\"text/javascript\" >alert('当前用户无权限');</script>";
            exit;
        }
    }

    /**
     * 根据权限获取菜单
     * return json
     */
    public function get_menu()
    {
        $power_id = session('power_id');
        $power_id = '1,2,3,4,5,6,7,8,9,10,11,12,13';
        $where['id'] = array('in', $power_id);
        $power_list = Db::name('group_power')->where($where)->select();
        return $power_list;
    }

    /**
     * 根据权限列表
     * return json
     */
    public function power_list()
    {
        $power = Db::name('group_power')->where(['pid' => 0, 'status' => 1])->field('id,power_name')->select();
        return json(['status' => 1, 'msg' => '成功', 'info' => $power]);
    }

    /**
     * 修改密码
     * @param user_id $password
     * return json
     */
    public function set_pwd($user_id = '', $password = '')
    {
        if (empty($user_id) || empty($password)) {
            return json(['status' => 0, 'msg' => '参数异常']);
        }
        $salt = create_password(6);
        $pwd = md5($salt . md5($password));
        $data = array(
            'password' => $pwd,
            'salt' => $salt,
        );

        Db::name('group_user')->where(['id' => $user_id])->update($data);
        return json(['status' => 1, 'msg' => '操作成功']);
    }


    /**
     * 单独修改某权限
     * @param user_id pid sataus
     * @return json
     */
    public function set_power($user_id = '', $pid = '', $status = 1)
    {
        if (empty($user_id) || is_int($pid)) {
            return json(['status' => 0, 'msg' => '参数异常']);
        }

        $power_li = Db::name('group_user')->where(['id' => $user_id])->value('power_id');
        $power_arr = explode(',', $power_li);
        $in_power = 0;

        foreach ($power_arr as $key => $value) {
            if ($value == $pid) {
                $in_power = 1;
                $k = $key;
            }
        }

        if ($status == 1) {
            if ($in_power == 0) {
                $power_arr[] = $pid;
            }

        } else {

            if ($in_power == 1) {
                unset($power_arr[$k]);
            }

        }

        $str = implode(',', $power_arr);
        Db::name('group_user')->where(['id' => $user_id])->update(['power_id' => $str]);
        return json(['status' => 1, 'msg' => '操作成功']);
    }

}