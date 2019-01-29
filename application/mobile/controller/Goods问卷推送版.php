<?php
/**
 * Created by PhpStorm.
 * User: Colin
 * Date: 2018/4/18
 * Time: 13:33
 */

namespace app\mobile\controller;

use think\Controller;
use think\Session;
use think\Db;
use think\Request;
use app\mobile\controller\MobileBase;
use think\Cache;

class Goods extends MobileBase
{
    protected function _initialize(){
        $this->openid=session('openid');
//        session('user',$user);
        $this->maintain_id = session('maintain_id');
        $this->user=session('user');
        $this->user_id=session('user')['id'];
        parent::_initialize();

    }
    //首页（领取香烟页面）
    public function index()
    {
        $eq_code = trim(input('get.eq_code'));
        $key = trim(input('get.key'));
        // key判断处理 2018-09-25
        $EqInfo = Db::name('equipment')->where(['code'=>$eq_code])->find();
        if(empty($EqInfo)){
            $this->assign('overdue',1);
        }
        $storage_key = Cache::get($eq_code.'_key');
        if($storage_key == $key){
            //記錄掃描進來用戶
            $this->user_scan($eq_code,$key,1);
            
            $this->pushNewCode($eq_code,$EqInfo['registration_id']);//推送新二维码密钥
			 
            //如果是维护人员，扫了其维护的设备，则直接进入维护页面
            if(empty($this->maintain_id)){
            	$maintain = Db::name('maintain_account')->where(['openid'=>$this->openid,'status'=>1])->find();
            	if(!empty($maintain)){
            		$this->maintain_id=$maintain['id'];
            		//跳转到维护页面
            		$this->redirect('/mobile/MaintainManage/equipmentInfoView?eq_code='.$eq_code.'&key='.$key);
            	}
				else
					 $this->assign('overdue',2);
            }
			else
			{
            	$this->redirect('/mobile/MaintainManage/equipmentInfoView?eq_code='.$eq_code.'&key='.$key);
			}
        }else{
        	$this->pushNewCode($eq_code,$EqInfo['registration_id']);
            $this->assign('overdue',1);
        }    
        //end

        $default_goods_list = Db::name('equipment_default')->where(['id'=>1])->find();
        $default_goods_id=[];
        foreach ($default_goods_list as $k=>$v){
            $vals = explode('groove_good',$k);
            $val='';
            foreach ($vals as $k_vals=>$v_vals)
            {
                if(!empty($v_vals) && $k_vals==1){
                    $val=$v_vals;
                }
            }
            if(is_numeric($val)){
                $default_goods_id[]=$v;
            }
        }

        $goods_list=Db::name('goods')->alias('g')
            ->join('goods_brand gb','g.goods_brand_id=gb.id','left')
            ->field(" g.id,gb.name gb_name,g.name,g.img_url,g.describe")
            ->where(['g.status'=>1,'g.id'=>['in',$default_goods_id]])
            ->order('g.create_time desc')
            ->select();

        $questionnaire_id=0;
        $questionnaire = Db::name("questionnaire")->where(['action_id'=>1])->find();
        $questionnaire_id = $questionnaire['id'];

        $this->assign('questionnaire_id',$questionnaire_id);

        $this->assign('goods_list',json_encode($goods_list));
        return view();
    }
    
    private function pushNewCode($eq_code,$registration_id)
    {
    	//更新key
    	$new_key = md5(uniqid().rand(100000,999999).$eq_code);
    	Cache::set($eq_code.'_key',$new_key,86400);
    	//推送新url
    	$msg=[
    	'type'=>4,
    	'key'=>$new_key,
    	];
    	 
    	$msg=json_encode($msg);
    	$chuyanjiService = new \app\common\service\ChuyanjiService();
    	$chuyanjiService->pushUrl($msg,$registration_id);
    }
    //点击获取香烟
    public function getSmoke($eq_code,$goods_id)
    {
        //查询用户是否领取过
        $where=[];
        $where['openid']=$this->openid;
        if($goods_id==8){ //8：打火机
            $where['goods_id']=8;
        }else{
            $where['goods_id']=['<>',8];
        }

        $start_time=strtotime(date('Y-m-d'));
        $end_time=$start_time+3600*24;
//        $where['create_time']=['between',[$start_time,$end_time]];

//        if(Db::name('receive_record')->where($where)->count()>0)
//        {
//            return json(['status'=>0,'info'=>'亲，您已经领取过了哦！']);
//        }

//        if(Db::name('receive_record')->where($where)->count()>0)
//        {
//            return json(['status'=>0,'info'=>'亲，您已经领取过了哦！']);
//        }
        
        Db::startTrans();

        $eq_info=Db::name('equipment')->where(['code'=>$eq_code])->find();
        if(empty($eq_info)){
            return json(['status'=>0,'info'=>'设备编号不存在！']);
        }

        $goods_info=Db::name('goods')->where(['id'=>$goods_id])->find();
        if(empty($goods_info)){
            return json(['status'=>0,'info'=>'产品不存在！']);
        }

        $registration_id=$eq_info['registration_id'];

        $groove_num=0; //出烟槽号
        if($eq_info['groove_good1']==$goods_id){
            $groove_num=1;
        }else if($eq_info['groove_good2']==$goods_id){
            $groove_num=2;
        }else if($eq_info['groove_good3']==$goods_id){
            $groove_num=3;
        }else if($eq_info['groove_good4']==$goods_id){
            $groove_num=4;
        }else if($eq_info['groove_good5']==$goods_id){
            $groove_num=5;
        }else if($eq_info['groove_good6']==$goods_id){
            $groove_num=6;
        }else if($eq_info['groove_good7']==$goods_id){
            $groove_num=7;
        }

        if(empty($eq_info['groove'.$groove_num])){
            return json(['status'=>0,'info'=>'哎哦，该烟已经被领完了哦！']);
        }

        if(empty($groove_num)){
            return json(['status'=>0,'info'=>'选择产品不存在于该设备']);
        }

        $msg=[
            'type'=>3,
            'groove_num'=>$groove_num,
        ];

        $msg=json_encode($msg);
        $chuyanjiService =new \app\common\service\ChuyanjiService();
        $push_result = $chuyanjiService->pushSmoke($msg,$registration_id,$this->openid,$eq_code,$goods_id,$groove_num);
        //pushSmokeTwo 方法新增延时查询推送状态
//        $push_result = $chuyanjiService->pushSmokeTwo($msg,$registration_id,$this->openid,$eq_code,$goods_id,$groove_num);

        if($push_result){
            Db::commit();

            if(Db::name('questionrecord')->where(['user_id'=>$this->user_id])->count()<=0)
            {
                //投放问卷
                $data_questionnaire=[
                    'touser'=>$this->openid,
                    'goods_id'=>$goods_id,
                ];
                $WxService = new \app\common\service\WxService();

                $push_result = $WxService->sendQuestionTemplateTwo($data_questionnaire);
                if($push_result){
                    Db::query('update sp_equipment set groove'.$groove_num.'=groove'.$groove_num."-1,smoke_count=smoke_count+1 where code='".$eq_code."'");
                    $questionnaire=Db::name('questionnaire')->where(['goods_id'=>$goods_id,'status'=>1])->field('id')->order('create_time desc')->find();
                    //当前的烟没有自己的问卷发送通用
                    if(!$questionnaire){
                        $questionnaire=Db::name('questionnaire')->where(['action_id'=>1,'status'=>1])->field('id')->order('create_time desc')->find();
                    }
                    $questionnaire_url = "http://gzh.zonma.net/mobile/question/index?id=".$questionnaire['id'];
                }
                return json(['status'=>2,'info'=>'领取成功，是否参加问卷有奖活动！','url'=>$questionnaire_url]);
            }

            return json(['status'=>1,'info'=>'领取成功']);
        }else{
            Db::rollback();
            return json(['status'=>0,'info'=>'系统异常']);
        }
    }
    //获取openid
    public function getOpenid(){
        var_dump($this->openid);
    }
    //openid清空
    public function clearOpenid(){
        session('openid',null);
    }

    //用戶掃描信息記錄掃碼信息
    public function user_scan($eq_code,$key,$status){

        $data = array(
            'openid' =>$this->openid,
            'key'=>$key,
            'eq_code'=>$eq_code,
            'status'=>$status,
            'create_time'=>time()
        );

        $res = Db::name('scan_code')->insert($data);
        return $res;
    }

    
}