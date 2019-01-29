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
use think\UserAgent;

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
		P("进入页面".time());
        $eq_code = trim(input('get.eq_code'));
        $key = trim(input('get.key'));
        $t = trim(input('t'));
        // key判断处理 2018-09-25
        $EqInfo = Db::name('equipment')->where(['code'=>$eq_code])->find();
        if(empty($EqInfo)){
            $this->assign('overdue',1);
        }

        $this->assign('key',$key);

        //查询分组设置
        $group_set = DB::name('equipment_group')->where(['id'=>$EqInfo['group_id']])->find();
        if($group_set['qrcode_control']==1)
        {
            $storage_key = Cache::get($eq_code.'_key'); //公共key
            $member_key = Cache::get($this->openid.'_op_key'); //存在缓存的key判断是否用过
            $check_key = DB::name('scan_code')->where(['openid'=>$this->openid,'key'=>$key])->find();
            if($key == $member_key || is_array($check_key))
            {
                $this->pushNewCode($eq_code,$EqInfo['registration_id']);
                $rs = false;
            }else {
                Cache::set($this->openid.'_op_key',$key,86400);
                $rs = true;
            }

        }else{
            $rs = true;
        }

        if($rs == true){
            //如果是维护人员，扫了其维护的设备，则直接进入维护页面
            if(empty($this->maintain_id))
            {
                $maintain = Db::name('maintain_account')->where(['openid'=>$this->openid,'status'=>1])->find();
                if(!empty($maintain) && empty($t)){
                    $this->maintain_id=$maintain['id'];
                    //跳转到维护页面
                    $this->redirect('/mobile/MaintainManage/equipmentInfoView?eq_code='.$eq_code.'&key='.$key);
                }
                else
                    $this->assign('overdue',2);
            }
            else if(empty($t))
            {
                $this->redirect('/mobile/MaintainManage/equipmentInfoView?eq_code='.$eq_code.'&key='.$key);
            }

            //記錄掃描進來用戶
//            $this->user_scan($eq_code,$key,1);
            $this->pushNewCode($eq_code,$EqInfo['registration_id']);//推送新二维码密钥

        }else{
        	$this->pushNewCode($eq_code,$EqInfo['registration_id']);
            $this->assign('overdue',1);
        }    
        //end

        //2018-10-24 改动
        //未分组查询默认
        if($EqInfo['group_id']==0)
        {
            $default_goods_list = Db::name('equipment_default')->where(['id'=>1])->find();
        }else{
            $default_goods_list = Db::name('equipment_default')->where(['group_id'=>$EqInfo['group_id']])->find();
        }
        //end
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
		P("完成页面".time());

        if($group_set['new_view']==1){
//            echo 2;
            $view = "index2"; //index2.html 新增 2秒一次轮询查询是否有出烟回调
        }else{
            $view = '';
        }
        return view($view);
    }

    private function pushNewCode($eq_code,$registration_id)
    {
    	//更新key
    	$new_key = md5(uniqid().rand(100000,999999).$eq_code);
    	Cache::set($eq_code.'_key',$new_key,86400);

        $msg_id = time();
    	//推送新url
    	$msg=['type'=>4,'key'=>$new_key,'msg_id'=>$msg_id];
        $tx_push_id = Db::name('equipment')->where(['code'=>$eq_code])->find()['tx_push_id'];

    	$msg=json_encode($msg);

    	if(!empty($registration_id))
    	{
            if(strpos(config('app_old_push.lis'),$eq_code) !==false)
            {
                $chuyanjiService = new \app\common\service\ChuyanjiService();
                $chuyanjiService->pushUrl($msg,$registration_id,$msg_id);
            }else{
                $chuyanjiService = new \app\common\service\ChuyanjiServiceNew();
                $chuyanjiService->pushUrl($msg,$registration_id,$msg_id);
            }

        }

        //腾讯推送
        if(!empty($tx_push_id)){
            if(strpos(config('app_old_push.lis'),$eq_code) !==false)
            {
                $XingeRest = new \app\common\service\XingeRest();
                $XingeRest->pushUrlSave($msg, $tx_push_id, $msg_id);
            }else{
                $XingeRest = new \app\common\service\XingeRestNew();
                $XingeRest->pushUrlSave($msg, $tx_push_id, $msg_id);
            }
        }

    }
    //点击获取香烟
    public function getSmoke($eq_code,$goods_id,$key='')
    {
		 P('收到出烟命令'.$eq_code.$goods_id.time());
        //查询用户是否领取过
        $where=[];
        $where['openid']=$this->openid;
        if($goods_id==8){ //8：打火机
            $where['goods_id']=8;
        }else{
            $where['goods_id']=['<>',8];
        }
        
        Db::startTrans();

        $eq_info=Db::name('equipment')->where(['code'=>$eq_code])->find();
        if(empty($eq_info)){
            return json(['status'=>0,'info'=>'设备编号不存在！']);
        }

        $goods_info=Db::name('goods')->where(['id'=>$goods_id])->find();
        if(empty($goods_info)){
            return json(['status'=>0,'info'=>'产品不存在！']);
        }

        //出烟限制判读
        //10-29 add
        if($this->AstrictThree($goods_id,$eq_info['group_id']) == false){
            return json(['status'=>0,'info'=>'亲，您今天领取数量已达到上限了！']);
        }

        //查询分组的设置
        $group_set = Db::name('equipment_group')->where(['id'=>$eq_info['group_id']])->find();

        $registration_id=$eq_info['registration_id'];
        $tx_push_id=$eq_info['tx_push_id'];

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
        $msg_id = time();

//        2018/12/14 add
        $this->user_scan($eq_code,$key,1);

        P([$msg_id,time(),$eq_code,$goods_id]);
        $msg=['type'=>3,'groove_num'=>$groove_num,'msg_id'=>$msg_id];
        $msg=json_encode($msg);

//        2018-12-18 add if
        if(strpos(config('app_old_push.lis'),$eq_code) !==false)
        {
            $chuyanjiService =new \app\common\service\ChuyanjiService();
            $push_result = $chuyanjiService->pushSmoke($msg,$registration_id,$this->openid,$eq_code,$goods_id,$groove_num,$msg_id);
        }else{
            $chuyanjiService =new \app\common\service\ChuyanjiServiceNew();
            $push_result = $chuyanjiService->pushSmoke($msg,$registration_id,$this->openid,$eq_code,$goods_id,$groove_num,$msg_id);
        }

        //pushSmokeTwo 方法新增延时查询推送状态
        //$push_result = $chuyanjiService->pushSmokeTwo($msg,$registration_id,$this->openid,$eq_code,$goods_id,$groove_num,$msg_id);

        //腾讯推送
        //        2018-12-18 add if
        if(strpos(config('app_old_push.lis'),$eq_code) !==false)
        {
            $XingeRest = new \app\common\service\XingeRest();
            $push_resul_tx = $XingeRest->PushSingSmoke(json_encode($msg),$tx_push_id,$this->openid,$eq_code,$goods_id,$groove_num,$msg_id);
        }else{
            $XingeRest = new \app\common\service\XingeRestNew();
            $push_resul_tx = $XingeRest->PushSingSmoke(json_encode($msg),$tx_push_id,$this->openid,$eq_code,$goods_id,$groove_num,$msg_id);
        }


        P(['getSmoke','tx'=>$push_resul_tx,'jg'=>$push_result,$this->openid,$eq_code,$goods_id,date('Y-m-d H:i:s',time())]);
        if($push_result || $push_resul_tx){
            Db::commit();

            //11-08修改 根据分组是否使用轮询index2页面修改
            //最新apk收到出烟回调减库存
            //new_view==0 是旧版 这里减库存
            if($group_set['new_view']==0)
            {
                Db::query('update sp_equipment set groove'.$groove_num.'=groove'.$groove_num."-1,smoke_count=smoke_count+1 where code='".$eq_code."'");
            }

            if($group_set['given']==1){
                $questionnaire=Db::name('questionnaire')->where(['group_id'=>$group_set['id'],'status'=>1])->field('id')->order('create_time desc')->find();
            }else{
                $questionnaire=Db::name('questionnaire')->where(['goods_id'=>$goods_id,'status'=>1])->field('id')->order('create_time desc')->find();
            }


            //当前的烟没有自己的问卷发送通用
//            if(!$questionnaire)
//            {
//                $questionnaire=Db::name('questionnaire')->where(['action_id'=>1,'status'=>1])->field('id')->order('create_time desc')->find();
//            }

            if(is_array($questionnaire))
            {
                if(Db::name('questionrecord')->where(['user_id'=>$this->user_id,'questionnaire_id'=>$questionnaire['id']])->count()<=0 && $group_set['is_questionnaire']==1)
                {
                    //投放问卷
                    $data_questionnaire=['touser'=>$this->openid,'goods_id'=>$goods_id];
                    $WxService = new \app\common\service\WxService();
                    $push_result = $WxService->sendQuestionTemplateTwo($data_questionnaire);
                    if($group_set['is_questionnaire']==1 || $push_result)
                    {
                        $questionnaire_url = "http://gzh.zonma.net/mobile/question/index?id=".$questionnaire['id'];
                    }
                    return json(['status'=>2,'info'=>'领取成功，是否参加问卷有奖活动！','url'=>$questionnaire_url,'msg_id'=>$msg_id]);
                }
            }

            return json(['status'=>1,'info'=>'领取成功','msg_id'=>$msg_id]);

        }else{

            Db::rollback();
            return json(['status'=>0,'info'=>'网络异常,请重新扫码','msg_id'=>$msg_id]);
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
    //12-13 新增ip查询 记录操作系统 记录手机型号
    public function user_scan($eq_code,$key,$status)
    {
        $UserAgent = new UserAgent();
        $ip =get_client_ip(0,true);
        $url = 'http://ip.taobao.com/service/getIpInfo.php?ip='.$ip;
        $json = get($url);
        $arr = json_decode($json,true);
        $ip_info = $arr['data'];
        $data = array(
            'openid' =>$this->openid,
            'key'=>$key,
            'eq_code'=>$eq_code,
            'status'=>$status,
            'os'=>$UserAgent->GetOs(),
            'model'=>$UserAgent->GetModel(),
            'net_type'=>$UserAgent->GetNetType(),
            'province'=>$ip_info['region'],
            'city'=>$ip_info['city'],
            'isp'=>$ip_info['isp'],
            'ip'=>$ip,
            'user_agent'=>$_SERVER['HTTP_USER_AGENT'],
            'create_time'=>time(),
        );

        $res = Db::name('scan_code')->insert($data);
        return $res;
    }

    //出烟限制
    public function Astrict($goods_id){
        $set = Db::name('issue_set')->find();
        //总数
        $start_time=strtotime(date('Y-m-d',time()));
        $end_time=$start_time+3600*24;

        $res = true;

        $where['create_time']=['between',[$start_time,$end_time]];
        $where['openid'] = $this->openid;

        if($set['choice']==1){
            $count = Db::name('receive_record')->where($where)->count();
            if($count>=$set['choice_total'] && $set['choice_total']!=0){
                $res =  false;
            }
        }else if($set['choice']==2){ //每种烟
            $where['goods_id'] = $goods_id;
            $count = Db::name('receive_record')->where($where)->count();
            $goods_limit = Db::name('goods')->where(['id'=>$goods_id])->field('limit')->find();
            if($count>=$goods_limit['limit'] && $goods_limit['limit']!=0){
                $res =  false;
            }
        }

        return $res;
    }

    public function AstrictTwo($goods_id,$group_id){
        $set = Db::name('equipment_group')->where(['id'=>$group_id])->find();
        //总数
        $start_time=strtotime(date('Y-m-d',time()));
        $end_time=$start_time+3600*24;

        $res = true;

        $where['create_time']=['between',[$start_time,$end_time]];
        $where['openid'] = $this->openid;
        $where['status'] = 1;
        $where['is_callblack'] = 1;

        if($set['choice']==1){
            $count = Db::name('receive_record')->where($where)->count();
            if($count>=$set['choice_total'] && $set['choice_total']!=0){
                $res =  false;
            }
        }else if($set['choice']==2){ //每种烟
            $where['goods_id'] = $goods_id;
            $count = Db::name('receive_record')->where($where)->count();
            $goods_limit = Db::name('goods_out_limit')->where(['goods_id'=>$goods_id,'group_id'=>$group_id])->field('limit')->find();
            if($count>=$goods_limit['limit'] && $goods_limit['limit']!=0){
                $res =  false;
            }
        }

        return $res;
    }


    //2018/12/20 add
    //新增回答问卷 赠送领取烟次数
    //填写问卷增加当天领烟数

    public function AstrictThree($goods_id,$group_id){
        $set = Db::name('equipment_group')->where(['id'=>$group_id])->find();
        //总数
        $start_time=strtotime(date('Y-m-d',time()));
        $end_time=$start_time+3600*24;

        $res = true;

        $where['create_time']=['between',[$start_time,$end_time]];
        $where['openid'] = $this->openid;
        $where['status'] = 1;
        $where['is_callblack'] = 1;

       if($set['is_questionnaire']==1 && $set['give']>0)  //开启填写问题送烟
       {
          //是否有答题历史
           $check_where['user_id'] = $this->user_id;
           $check_where['create_time']=['between',[$start_time,$end_time]];

           //今日答了 多少份问卷
           //今日可领次数 = 回答分数 * 当份赠送次数 + 基本限制次数
           $qu_count = Db::name('v_question_record')->where($check_where)->count();

           $give = $set['give']; //赠送数量
           $count = Db::name('receive_record')->where($where)->count(); //今日领烟数量

           //或许该组设置每日限制的数量
           if($set['choice']==1)
           {
               $limit =  $set['choice_total'];
           } else if($set['choice']==2){
               $limit = Db::name('goods_out_limit')->where(['goods_id'=>$goods_id,'group_id'=>$group_id])->value('limit');
           }

           //已经回答过
           if($qu_count>0)
           {
               //今日限制数 = 组设置限制数 + （问卷数 * 赠送数）
               $limit = $limit + $qu_count * $give;
           }


           if($count < $limit)
           {
               $res =  true;
           }else{
               $res =  false;
           }

       }else{

           //正常没有开启填写问卷调查送烟判断
           if($set['choice']==1){
               $count = Db::name('receive_record')->where($where)->count();
               if($count>=$set['choice_total'] && $set['choice_total']!=0){
                   $res =  false;
               }
           }else if($set['choice']==2){ //每种烟
               $where['goods_id'] = $goods_id;
               $count = Db::name('receive_record')->where($where)->count();
               $goods_limit = Db::name('goods_out_limit')->where(['goods_id'=>$goods_id,'group_id'=>$group_id])->field('limit')->find();

               if($count>=$goods_limit['limit'] && $goods_limit['limit']!=0){
                   $res =  false;
               }
           }

       }

        return $res;
    }


    public function test()
    {
//        dump($this->user_id);exit;
        $goods_id = input('goods_id');
        $group_id = input('group_id');
        $a = $this->AstrictThree($goods_id,$group_id);
        dump($a);
    }


}