<?php
/**
 * Created by PhpStorm.
 * User: Colin
 * Date: 2018/10/12
 * Time: 9:48
 */

namespace app\group\controller;
use think\Controller;
use think\Session;
use think\Db;
use think\Request;
use app\common\controller\Base;

class Goods extends Check
{
    public function __construct()
    {
        $this->group_id = session('group_id');
        $this->power_id = session('power_id');
        $this->group_user_id = session('group_user.id');
        parent::__construct();
    }

    public function index()
    {
        return view();
    }

    /**
     * 获取商品列表
     * 参数 page limit keyword
     * @return json对象
     */
    public function get_goods_list($page=1,$limit=10,$keyword='')
    {
        if(!empty($keyword))
        {
            $where['name'] = ['like',"%$keyword%"];
        }
        $where['status'] = ['>',-1];
        $where['group_id']= $this->group_id;
        $list = Db::name('goods')->where($where)->field("id,name,img_url,status,describe,goods_code")->page($page,$limit)->select();
        $count = Db::name('goods')->where($where)->count();
        if(is_array($list)){
            return json(['status'=>1,'msg'=>'success','info'=>$list,'count'=>$count]);
        }else{
            return json(['status'=>0,'msg'=>'error','count'=>$count]);
        }
    }

    public function test(){
        var_dump(create_password(6));exit;
        $request= Request::instance();
        $module_name=$request->module();
        $controller_name=$request->controller();
        $action=$request->action();
        var_dump($module_name);
        var_dump($controller_name);
        var_dump($action);
    }

    /**
     * 添加商品
     * 参数 name img_url status describe
     * @return json对象
     */
    public function add()
    {
        $id = trim(input('id'));
        $data['name'] = trim(input('name'));
        $data['img_url'] = trim(input('img_url'));
        $data['status'] = trim(input('status')) ==''?1:trim(input('status'));
        $data['goods_brand_id'] = trim(input('goods_brand_id'));
        $data['describe'] = trim(input('describe'));
        $data['goods_code'] = trim(input('goods_code'));
        $data['update_time'] = time();
        $data['group_id']=$this->power_id;

        if(empty($id))
        {
            $data['create_time'] = time();
            Db::name('goods')->insertGetId($data);
        }else{
            Db::name('goods')->where(['id'=>$id])->update($data);
        }

        return json(['status'=>1,'msg'=>'success']);

    }

    /**
     * 编辑页面
     * 参数 name img_url status describe
     * @return json对象
     */
    public function edit()
    {
//        print_r('hello word');
        $goods = '';
        $id = trim(input('id'));
        if(!empty($id))
        {
            $goods = Db::name('goods')->where(['id'=>$id,'group_id'=>$this->power_id])->find();
        }
        $brand = Db::name('goods_brand')->field('id as goods_brand,name as brand_name')->select();
        return view('',['goods'=>json_encode($goods),'brand'=>json_encode($brand)]);
    }

    /**
     * 删除
     * 参数 name img_url status describe
     * @return json对象
     */
    public function delete($id=''){
        if(!empty($id))
        {
            Db::name('goods')->where(['id'=>$id])->update(['status'=>-1]);
            return json(['status'=>1,'info'=>'success']);
        }else{
            return json(['status'=>0,'info'=>'error']);
        }

    }


    /**
     * 修改商品
     * 参数 name img_url status describe
     * @return json对象
     */
    public function renew()
    {
        $id = trim(input('id'));
        $data['name'] = trim(input('name'));
        $data['img_url'] = trim(input('img_url'));
        $data['status'] = trim(input('status'));
        $data['describe'] = trim(input('describe'));
        $data['update_time'] = time();
        Db::name('goods')->where(['id'=>$id])->update($data);
        return json(['status'=>1,'msg'=>'success']);
    }


    public function upload()
    {
        $request = Request::instance();
        $file = request()->file('image');
//        'size'=>20480, ->validate(['ext'=>'jpg,jpeg,png'])
        $info = $file->move( ROOT_PATH .'public/upload/goods' ,true,false);
        if($info){
            $server_name = 'http://'.$_SERVER['SERVER_NAME'];

            return r(1,'',['data'=>$server_name.'/upload/goods/'.str_replace('\\','/',$info->getSaveName())]);
        }else{
            return r(0,$file->getError());
        }
    }


    public function brand()
    {
        return view();
    }

}