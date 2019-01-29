<?php

namespace app\admin\controller;
use think\Controller;
use think\Db;
use app\common\controller\Base;

//导入导出字段管理
class Field extends Base
{
    public function __construct(){
        parent::__construct();
        $this->M = new \app\common\model\Field;
    }
    public function index(){ return view(); }
    //查询
    public function lists($page,$limit)
    {
        $where=[];
        if($params=input('post.')){
            if(isset($params['search']) && $params['search']!=''){
                $where['table_name|module']=['like','%'.$params['search'].'%'];
            }
            if(!empty($params['module1']) && $params['module1']!=''){
                $where['module']=$params['module1'];
            }
        }
        $data=Db::name('field')
            ->where($where)
            ->field('id,field_name,name,remark,table_name,module,status')
            ->order('module,sort')
            ->page($page,$limit)
            ->select();

        $count=$this->M->where($where)->count();

        return r_page(1,$data,$count);
    }
    //获取模块列表
    public function getModuleList()
    {
        $data = Db::name('field')->group('module')->field('module id,module')->select();
        return json($data);
    }
    //添加
    public function add()
    {
        $model=input('post.');
//        $this->M->check($model,'add');
        $result = $this->M->save($model);
        return r_oper($result);
    }
    //编辑
    public function edit()
    {
        $model=input('post.');
//        $this->M->check($model,'edit');
        $result=$this->M->save($model,['id'=>$model['id']]);
        return r_oper($result);
    }
    //查看单一数据
    public function model($id)
    {
        return $this->M->where('id',$id)->find();
    }
    //删除
    public function del($id)
    {
        return r_oper($this->M->where('id',$id)->delete());
    }
    //启用或禁用
    public function status($id,$status)
    {
        return r_oper($this->M->save(['status' => $status],['id'=>['in',explode(',',$id)]]));
    }
}