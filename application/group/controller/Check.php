<?php

namespace app\group\controller;
use think\Controller;
use think\Db;
use think\session;
use think\Request;

//公共接口
class Check extends Controller
{


    protected function _initialize(){
        $group_user = session('group_user');
        //登录验证
        if(!is_array($group_user)){
            exit("<script>if(confirm('登录超时，是否重新登录？')){window.location.href = '/group/login/login';}</script>");
        }
        //权限验证
        if(!$this->CheckPower())
        {
            print_r('当前用户无权限');
            echo "<script type=\"text/javascript\" >alert('当前用户无权限');</script>";
            exit;
        }
    }

    public function CheckPower()
    {
        $power_id = session('power_id');
        $request= Request::instance();
        $controller = strtolower($request->controller());
        $where['id'] = array('in',$power_id);
        $where['status'] = 1;
        $power_list = Db::name('group_power')->where($where)->field('id,controller')->select();
        $arr = array_unique(array_map(function($v){
            return strtolower($v['controller']);
        },$power_list));

        if(!in_array($controller,$arr))
        {
            $currency = Db::name('group_power')->where(['pid'=>0,'status'=>0])->field('id,controller')->select();
            $em_arr = array_unique(array_map(function($v){
                return strtolower($v['controller']);
            },$currency));

            if(!in_array($controller,$em_arr)){
                return false;
            }else{
                return true;
            }

        }else{
            return true;
        }
    }

    /**
     * 根据权限获取菜单
     * return json
     */
    public function get_group_menu()
    {
        $power_id = session('power_id');
        $p = [];
        $arr = explode(',',$power_id);
        if(is_array($arr)) foreach ($arr as $k => $v){
            if(!empty($v))
            {
                $ac = Db::name('group_power')->where(['pid'=>$v])->field('id')->select();
                $p[] = $v;
                foreach ($ac as $kk =>$vv){
                    $p[] = $vv['id'];
                }
            }
        }
        $id_str = implode(',',$p);
        $where['id'] = array('in',$id_str);
        $where['status'] = 1;
        $power_list = Db::name('group_power')->where($where)->field('id,power_name as name,url,pid as parent_id,sort,icon')->select();
        return $power_list;
    }

}