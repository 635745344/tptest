<?php
namespace app\admin\controller;
use think\Db;
use app\common\controller\Base;
use think\Session;
use think\Config;
use app\library\AdminHelper;

class Menu extends Base
{
	public function __construct(){
		parent::__construct();
        $this->M = new \app\common\model\Menu;
        $this->Power=new \app\common\model\Power;
    }
	public function index(){return view();}
	//查询	
	public function lists($page,$limit)
	{
		$data=$this->M->alias('m')
				  ->join('sp_power p','m.id = p.menu_id')
				  ->where(['p.is_menu'=>1])
				  ->field('m.id,m.name,m.parent_id,m.icon,m.status,m.sort,p.url')
				  ->select();
		return r_page(1,$data);
	}

	//根据id获取单条数据
	public function model($id)
	{
		$data=$this->M->alias('m')
				  ->join('sp_power p','m.id = p.menu_id','LEFT')
				  ->where(['m.id'=>$id,'p.is_menu'=>1])
				  ->field('m.id,m.name,m.parent_id,m.icon,m.sort,m.status,p.url')
				  ->find();
 		return $data;
	}
	//添加节点
	public function add()
	{
		$model=input('post.');
		$menu_id = $this->M->insertGetId($model);
		$result = $this->Power->insertGetId(['url'=>$model['url'],'is_menu'=>1,'menu_id'=>$menu_id]);
		
		return r_oper($result);
	}
	//编辑
	public function edit()
	{
		$model=input('post.');
		$model_before = $this->M->where('id',$model['id'])->field('parent_id,sort')->find();

		if($model['sort']!=$model_before['sort']){
			if($model_before['parent_id']==$model['parent_id']){
				if($model['sort']>$model_before['sort']){
					$this->M->where(['sort'=>[['>',$model_before['sort']],['<=',$model['sort']]],'parent_id'=>$model_before['parent_id'] ])->setDec('sort');
				}else{					
					$this->M->where(['sort'=>[['<',$model_before['sort']],['>=',$model['sort']]],'parent_id'=>$model_before['parent_id'] ])->setInc('sort');
				}
			}else{
				$this->M->where(['sort'=>['>',$model_before['sort']],'parent_id'=>$model_before['parent_id'] ])->setDec('sort');
				$this->M->where(['sort'=>['>=',$model_before['sort']],'parent_id'=>$model['parent_id'] ])->setInc('sort');
			}
		}
		$this->M->save($model,['id'=>$model['id']]);

		//检查父级id与之前是否相同
		$result = $this->Power->save(['url'=>$model['url']],['menu_id'=>$model['id']]);
		return r_oper($result);
	}
	//删除
	public function del($id)
	{
		$this->M->where('id',$id)->delete();
		$son=$this->M->where('parent_id',$id)->select();
		$son_menu_ids=[];
		foreach ($son as $key => $value) {
			$son_menu_ids[]=$value['id'];
		}
		$son_menu_ids[]=$id;

		$result = $this->Power->where(['menu_id'=>['in',$son_menu_ids]])->delete();

		return r_oper($result);
	}
	//启用或禁用
	public function status($id,$status)
	{
		$model=input('.post');
		$this->M->where(['id'=>$id])->whereOr(['parent_id'=>$id])->update(['status'=>$status]);
		$data=$this->M->where(['id'=>$id])->whereOr(['parent_id'=>$id])->field('id')->select();
		$menu_ids=[];

		foreach ($data as $key => $value) {
			$menu_ids[]=$value['id'];
		}
		$result = Db::name('power')
					 ->where('id','in',$menu_ids)
					 ->update(['status'=>$status]);
		return r_oper($result);
	}
	/**
	 * [获取一级列表]
	 */
	public function parent_one()
	{
		return json($this->M->where(['parent_id'=>0])->field('id,name')->order('sort')->select());
	}
	/**
	 * [根据父级id获取列表]
	 * @param  [string] $id [父级id]
	 */
	public function parent_list($id='-1')
	{
		$id=($id=='undefined'?'-1':$id);
		return json($this->M->where("parent_id=:id and parent_id<>0",['id'=>$id])->field('id,name')->order('sort')->select());
	}
}