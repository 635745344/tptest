<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use app\library\AdminHelper;
use app\common\controller\Base;
use think\Request;

class User extends Base
{	
	public function __construct(){
		parent::__construct();
        $this->M = new \app\common\model\User;
    }
	public function index(){ return view(); }
	//查询	
	public function lists($page,$limit)
	{
		$where=[];
		if($params=input('post.')){
			if(isset($params['search']) && $params['search']!=''){
				$where['account|nickname']=['like','%'.$params['search'].'%'];
			}
			if(isset($params['account']) && $params['account']!=''){
				$where['account']=['like','%'.$params['account'].'%'];
			}
			if(isset($params['nickname']) && $params['nickname']!=''){
				$where['nickname']=['like','%'.$params['nickname'].'%'];
			}
			if(isset($params['phone'])&& $params['phone']!=''){
				$where['phone']=['like','%'.$params['phone'].'%'];
			}
			if(!empty($params['start_time']) && !empty($params['end_time'])){
				$where['create_time']=['between',[$params['start_time'],$params['end_time'] ]];
			}else{
				if(!empty($params['start_time'])){
					$where['create_time']=['>=',$params['start_time']];
				}
				if(!empty($params['end_time'])){
					$where['create_time']=['<=',$params['end_time']];
				}
			}
		}
		$data=Db::name('user')
					->where($where)
					->field('id,account,nickname,headimgurl,phone,status,create_time')
					->order('create_time desc')
					->page($page,$limit)
					->select();

		$count=$this->M->where($where)->count();

		return r_page(1,$data,$count);
	}
	//添加
	public function add()
	{ 
		$model=input('post.');
		$this->M->check($model,'add');
		$model['password'] = AdminHelper::encrypt(config('admin.default_pwd'));
		$result = $this->M->save($model);
		return r_oper($result);
	}
	//编辑
	public function edit()
	{
		$model=input('post.');
		$this->M->check($model,'edit');
		$result=$this->M->allowField(['account','password','nickname','phone','qq','email','headimgurl'])->save($model,['id'=>$model['id']]);
		return r_oper($result);
	}
	//查看单一数据
	public function model($id)
	{
		return $this->M->where('id',$id)->field('id,account,nickname,phone,qq,email,headimgurl')->find();
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
	//重置密码
	public function reset_pwd($id)
	{
		return r_oper($this->M->save(['password'=>AdminHelper::encrypt(config('admin.default_pwd'))],['id'=>$id]));
	}
	//上传图片
	public function upload(){
		$request = Request::instance();
	    $file = request()->file('image');
//        ->validate(['size'=>20480,'ext'=>'jpg,jpeg,png'])
	    $info = $file->move( ROOT_PATH .'public/upload/user' ,true,false);
	    if($info){
	    	return r(1,'',['data'=>$request->domain().'/upload/user/'.str_replace('\\','/',$info->getSaveName())]);
	    }else{
	    	return r(0,$file->getError());
	    }
	}

	public function cs(){
        $chuyanjiService =new \app\common\service\ChuyanjiService();
        $chuyanjiService->isOnLine();
	}

	

}