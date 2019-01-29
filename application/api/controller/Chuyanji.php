<?php
namespace app\api\controller;

use think\Db;
//use Library\Util\MemcacheInstance;
use app\api\controller\Base;
use JPush\Client as JPush;
use think\Request;
use think\Cache;
use app\library\AdminHelper;
use think\Config;

class Chuyanji extends Base
{
    protected function _initialize()
    {
        parent::_initialize();
//        $this->mem = MemcacheInstance::getInstance (); // 采用单例模式调用Memcached
    }
    //初始化设备
    public function initEquipment()
    {
        $jg_push_id=input('post.jg_push_id');
        $tx_push_id=input('post.tx_push_id');
        $equipment_id=input('post.equipment_id');
        $mac=input('post.mac');

         if(empty($equipment_id) || empty($mac)){
            return json(['status'=>0,'info'=>'参数异常']);
         }


        //根据equipment_id判断是否存在 存在就更新 2018-09-26 update
        $check_equipment = Db::name('equipment')->where(['mac'=>$mac])->find();

        if($check_equipment){ 
            //equipment_id存在 更新数据
            $new_code = $check_equipment['code'];
            Db::name('equipment')->where(['mac'=>$mac])->update(['registration_id'=>$jg_push_id,'tx_push_id'=>$tx_push_id,'equipment_id'=>$equipment_id]);
//            return json(['status'=>0,'info'=>'参数异常']);

        }else{
            //不存在equipment_id 插入
            //开始事物
            Db::startTrans();
            $last_code=Db::name('equipment')->order('create_time desc')->find()['code'];
            $new_code='';
            if(empty($last_code)){
                $new_code='100000';
            }else{
                $new_code = substr($last_code,0,6)+1;
                //12-27 add
                //新增判断
                $check_new_code = Db::name('equipment')->where(['code'=>$new_code])->find();
                if(is_array($check_new_code)) return json(['status'=>0,'info'=>'code重复']);
            }


            $eq_config=Db::name('equipment_default')->where(['id'=>1])->find();

            //添加设备信息
            $data_equipment=[
                'code'=>$new_code,
                'groove1'=>0,
                'groove2'=>0,
                'groove3'=>0,
                'groove4'=>0,
                'groove5'=>0,
                'groove6'=>0,
                'groove7'=>0,
                'groove_good1'=>$eq_config['groove_good1'],
                'groove_good2'=>$eq_config['groove_good2'],
                'groove_good3'=>$eq_config['groove_good3'],
                'groove_good4'=>$eq_config['groove_good4'],
                'groove_good5'=>$eq_config['groove_good5'],
                'groove_good6'=>$eq_config['groove_good6'],
                'groove_good7'=>$eq_config['groove_good7'],
                'registration_id'=>$jg_push_id,
                'tx_push_id'=>$tx_push_id,
                'equipment_id'=>$equipment_id, //新增
                'mac'=>$mac,
                'run_time'=>0,
                'activation_time'=>time(),
                'update_time'=>time(),
                'status'=>0,
                'create_time'=>time(),
            ];

            Db::name('equipment')->insert($data_equipment);
            Db::commit();
        }
        P(['initEquipment',date('Y-m-d H:i:s',time()),'jg_push_id'=>$jg_push_id,'tx_push_id'=>$tx_push_id,'equipment_id'=>$equipment_id,'new_code'=>$new_code,'mac'=>$mac,'check_equipment'=>$check_equipment]);

        return json(['status'=>1,'info'=>'初始化成功!','code'=>$new_code]);
    }

    //修改极光推送id 10-29 add
    public function updateJgpushId()
    {
        $eq_code = trim(input('post.eq_code'));
        $jg_push_id = trim(input('post.jg_push_id'));
        P(['updateJgpushId',date('Y-m-d H:i:s',time()),'eq_code'=>$eq_code,'registration_id'=>$jg_push_id]);
        if(empty($eq_code) || empty($jg_push_id))
        {
            return json(['status'=>0,'info'=>'参数异常']);
        }

        DB::name('equipment')->where(['code'=>$eq_code])->update(['registration_id'=>$jg_push_id]);

        return json(['status'=>1,'info'=>'修改成功']);

    }
    //修改腾讯推送token 10-29 add
    public function updateTxpushId()
    {
        $eq_code = trim(input('post.eq_code'));
        $tx_push_id = trim(input('post.tx_push_id'));
        P(['updateTxpushId',date('Y-m-d H:i:s',time()),'eq_code'=>$eq_code,'tx_push_id'=>$tx_push_id]);
        if(empty($eq_code) || empty($tx_push_id))
        {
            return json(['status'=>0,'info'=>'参数异常']);
        }

        DB::name('equipment')->where(['code'=>$eq_code])->update(['tx_push_id'=>$tx_push_id]);

        return json(['status'=>1,'info'=>'修改成功']);
    }

    //获取广告
    public function getAdvertisement()
    {
        $eq_code=input('eq_code');
        $eq = Db::name('equipment')->where(['code'=>$eq_code])->find();

        if(!empty($eq_code))
        {
            //分组判断
            if($eq['group_id']==0){
                $data=Db::name('advertisement')->where(['status'=>1,'id'=>1])->find();
            }else if(is_array($eq)){
                $data=Db::name('advertisement')->where(['status'=>1,'group_id'=>$eq['group_id']])->find();
            }
        }else{
            //没有传eq_code的
            $data=Db::name('advertisement')->where(['group_id'=>22])->find();
        }


        $imgs=[];

        foreach (json_decode($data['img_info'],true) as $k=>$v ){
            if($v['status']==1 && !empty($v['url']))
            {
                $imgs[]=$v['url'];
            }
        }
        P(['getAdvertisement','code'=>$eq_code,'img'=>implode(',',$imgs)]);

        return json(['status'=>1,'info'=>'','imgs'=>implode(',',$imgs),'stop_time'=>$data['stop_time']*1000 ]);
    }
    //设备库存改变
    public function stockChange()
    {
        $params=input('post.');
        $data=[
            'groove1'=>$params['groove1'],
            'groove2'=>$params['groove2'],
            'groove3'=>$params['groove3'],
            'groove4'=>$params['groove4'],
            'groove5'=>$params['groove5'],
            'groove6'=>$params['groove6'],
            'groove7'=>$params['groove7'],
        ];
        //设备
        Db::name('equipment')->where(['code'=>$params['code']])->save($data);
        return json(['status'=>1,'info'=>'修改成功！']);
    }

    //检查设备在线状态
    public function isOnLine($eq_code)
    {
//        P('检查在线api_'.$eq_code.'_'.date('Y-m-d H:i:s',time()));
         $cycle=30; //周期时间30秒
         //修改设备状态
         $last_update_time=Cache::get('1525395254_'.$eq_code);
         $is_update=true;
         if($last_update_time===false)
         {
             Cache::set('1525395254_'.$eq_code,time());
         }else{
             if($last_update_time+20>time()){
                 $is_update=false;
             }
         }
         if($is_update){
            Db::execute("update sp_equipment set run_time = run_time+".$cycle.", status=1 where code='".$eq_code."' ");
         }
         Cache::set('1525395254_'.$eq_code,time());
         $fp = fopen(ROOT_PATH."runtime/lock/isOnLine.txt", "w+");

//         开启文件锁
         if(flock($fp, LOCK_EX)){ // 进行排它型锁定
             if( $this->_isExecCheckLx(true))
             {
                 $request = Request::instance();
                 $domain=$request->domain();
                 $url=$domain.'/api/isOnLine/checkAllEqLx';
                 model_http_curl_get($url);
             }

             flock($fp, LOCK_UN); // 释放锁定
         }
         fclose($fp);

        return json(['status'=>1,'info'=>'在线状态更新成功！']);

    }

    //检查是否执行检查离线程序
    private function _isExecCheckLx($is_default_time=false)
    {
        $is_exec=true;
        $last_visit_time=Cache::get('1525396412_last_visit_time');
        if($last_visit_time===false){
            if($is_default_time){
                Cache::set('1525396412_last_visit_time',time(),0);
            }else{
                $is_exec=false;
            }
        }else{
            if( $last_visit_time + 60 > time() ){
                $is_exec=false;
            }
        }
        return $is_exec;
    }

    //是否中断循环程序
    public function isExitOnLine($isExit=1)
    {
        Cache::set('1525395254_is_exit',$isExit);
    }
    //是否中断循环程序
    public function cs()
    {
//        $retailers=Db::name('retailer_account')->select();
//        foreach ($retailers as $k=>$v){
//            $user=Db::name('user')->where(['openid'=>$v['openid']])->find();
//            Db::name('retailer_account')->where(['id'=>$v['id']])->update(['user_id'=>$user['id']]);
//        }
//        $manage_accounts=Db::name('manage_account')->select();
//        foreach ($manage_accounts as $k=>$v){
//            $user=Db::name('user')->where(['openid'=>$v['openid']])->find();
//            Db::name('manage_account')->where(['id'=>$v['id']])->update(['user_id'=>$user['id']]);
//        }
//        $maintain_accounts=Db::name('maintain_account')->select();
//        foreach ($maintain_accounts as $k=>$v){
//            $user=Db::name('user')->where(['openid'=>$v['openid']])->find();
//            Db::name('maintain_account')->where(['id'=>$v['id']])->update(['user_id'=>$user['id']]);
//        }
        return json(['info'=>'ok']);
    }
    //是否中断循环程序
    public function cs1()
    {
        $path=ROOT_PATH."1.txt";

        $fp = fopen($path, "w+");
//        flock($fp, LOCK_EX);
        if(flock($fp, LOCK_EX)) // 进行排它型锁定
        {
            sleep(1000);
            var_dump('sdfsf');exit();
        }
        var_dump('no');exit();
    }
    //是否中断循环程序
    public function cs2()
    {
//        $path=ROOT_PATH."1.txt";
//        $fp = fopen($path, "w+");
//        flock($fp, LOCK_EX);
//        var_dump('yes');exit();

    }
    
    //获取新二维码
    public function getNewCode($eq_code,$sign_key){
    
    	//$day = date('Y-m-d',time());
    	
    	$api_sign_key = md5($eq_code.'zonma');
    	$key  = _getUrlKey($eq_code); //签名
    
     	P(array($eq_code,$sign_key,$key));
    
    	if($api_sign_key != $sign_key){
    		return json(['status'=>0,'msg'=>'sign_key error']);
    	}
    	if(empty($eq_code)){
    		return json(['status'=>0,'msg'=>'eq_code is empty']);
    	}
    
    
    	Cache::set($eq_code.'_key',$key,86400);  //存缓存180秒过期
    	
    	return json(['status'=>1,'key'=>$key]);
    
    }

    //检查更新
    public function checkUpdate(){
        $eq_code = trim(input('eq_code'));

        $group_id = Db::name('equipment')->where(['code'=>$eq_code])->find()['group_id'];
        $group_set = Db::name('equipment_group')->where(['id'=>$group_id])->find();

        if(!$group_set)  //未分组新机器自动获取最新的版本
        {
            $version = config('app_update_config.version');
            $filename = config('app_update_config.filename');
            $info = 'new';
        }else if($group_set['is_upgrade']==1)  // 1获取最新版版本
        {
            $version = config('app_update_config.version');
            $filename = config('app_update_config.filename');
            $info = 'new';
        }else if($group_set['is_upgrade']==0) //0 旧版本
        {
            $version = config('app_update_config_old.version');
            $filename = config('app_update_config_old.filename');
            $info = 'old';
        }

        $download_url = "http://gzh.zonma.net/Api/Chuyanji/download?filename=".$filename;
        return json(['status'=>1,'version'=>$version,'filename'=>$filename,'download_url'=>$download_url,'info'=>$info]);
        
    }

    //下载
    public function download(){
        $filename = input('get.filename');
        $file_dir = ROOT_PATH . 'public' . DS . 'upload' . '/' . "$filename";    // 下载文件存放目录
        if (! file_exists($file_dir) ) {
           echo '未找到文件';
           exit;
        }else{
            $file1 = fopen($file_dir, "r");                                        
            Header("Content-type: application/octet-stream");          
            Header("Accept-Ranges: bytes"); 
            Header("Content-Length:".filesize($file_dir));
            Header("Content-Disposition: attachment;filename=" . $filename);            
            ob_clean();   
            flush(); 
            echo fread($file1, filesize($file_dir));
            fclose($file1);
        }
    }

    //管理员判断
    public function adminCheck($account,$pwd,$eq_code){
        P(['adminCheck',$account,$pwd,$eq_code]);
        if(empty($account) || empty($pwd)){
            return json(['status'=>0,'info'=>'参数异常']);
        }
        // $salt = '5eaf0f4cbd74d824cc90ba2fce318980';
        // $pwd = md5($pwd.$salt);
        //加密key 5eaf0f4cbd74d824cc90ba2fce318980 
        // $data = Db::name('admin')->where(['account'=>$account,'password'=>AdminHelper::encrypt($pwd)])->find();
        $data = Db::name('admin')->where(['account'=>$account,'password'=>$pwd])->find();

        if($data){
            return json(['status'=>1,'info'=>'成功']);
        }else{
            return json(['status'=>0,'info'=>'账号密码错误']);
        }

    }

    //最新MP3判断更新
    public function musicCheck(){
        $eq_code = trim(input('eq_code'));
        $version = config('app_music_config.version');
        $filename = config('app_music_config.filename');
        $download_url = "http://gzh.zonma.net/Api/Chuyanji/download?filename=".$filename;
        return json(['status'=>1,'version'=>$version,'filename'=>$filename,'download_url'=>$download_url]);
    }

    //推送信息回调 app回调
    public function msgStatus(){
        $eq_code = trim(input('eq_code'));
        $msg_id = trim(input('msg_id'));
        $status = trim(input('status'));
        sleep(3);
        P(['msgStatus',date('Y-m-d H:i:s',time()),input()]);
        if(empty($msg_id) || empty($eq_code)){
            return json(['status'=>0,'info'=>'参数异常']);
        }

        //收到异常状态处理 status==1 正常 -1硬件问题 2多人同时出烟
        if($status == -1){
            Db::name('receive_record')->where(['push_code'=>$msg_id])->update(['reason'=>-1,'is_callblack'=>1,'status'=>0]);
        }else if($status == 2){
            Db::name('receive_record')->where(['push_code'=>$msg_id])->update(['reason'=>2,'is_callblack'=>1,'status'=>0]);
        }

        $info = DB::name('push_log')->where(['push_code'=>$msg_id])->find();
        P(['msgStatus_select',$info]);
        Db::name('push_log')->where(['push_code'=>$msg_id])->update(['is_callback'=>1,'reason'=>$status]);
        //eq_code部分 接收到这机器的机器号表示这机器在线
        Db::name('equipment')->where(['code'=>$eq_code])->update(['status'=>1]);

        return json(['status'=>1,'info'=>'成功']);

    }

    //出烟成功回调 app回调
    public function chuyanCallback()
    {
        $msg_id = trim(input('msg_id'));
        $status = trim(input('status'));

        P(['chuyanCallback','msg_id'=>$msg_id,'status'=>$status,date('Y-m-d H:i:s',time())]);

        if(empty($msg_id) || empty($status))
        {
            return json(['status'=>0,'info'=>'参数错误']);
        }

        sleep(2);

        //该状态 改库存
        $record = Db::name('receive_record')->where(['push_code'=>$msg_id])->find();
        P(['chuyanCallback_Data',$record]);
        $group_id = Db::name('equipment')->where(['code'=>$record['eq_code']])->find()['group_id'];
        $group_set = Db::name('equipment_group')->where(['id'=>$group_id])->find();
        if(is_array($record))
        {
            //记录
            Db::name('callback_log')->insert( ['msg_id'=>$msg_id,'time'=>date('Y-m-d H:i:s',time()),'log'=>$status,'is_select'=>1,'ts'=>time()]);

            //成功
            if($status==1)
            {
                $record_res = Db::name('receive_record')->where(['push_code'=>$msg_id])->update(['is_callblack'=>1,'status'=>1,'reason'=>0]);
                //判断当前机器分组是否使用轮询查询页面
                //如果是修改库存
                if($group_set['new_view']==1)
                {
                    $eq = Db::execute('update sp_equipment set groove'.$record['groove_num'].'=groove'.$record['groove_num']."-1,smoke_count=smoke_count+1 where code='".$record['eq_code']."'");
                }else{
                    $eq = true;
                }
            }else if($status== -1){
                $r = Db::name('receive_record')->where(['push_code'=>$msg_id])->find();
                if($r['is_callblack']==0)
                {
                    $record_res = Db::name('receive_record')->where(['push_code'=>$msg_id])->update(['is_callblack'=>1,'status'=>0,'reason'=>$status]);
                    $eq = true;
                }else{
                    $record_res = true;
                    $eq = true;
                }
                //失败处理
            }

            //库存报警
            $this->alarm_smoke($record['eq_code'],$record['groove_num'],$record['goods_id']);

            if($record_res && $eq)
            {
                return json(['status'=>1,'info'=>'处理成功']);
            }else{
                return json(['status'=>0,'info'=>'处理失败']);
            }

        }else{
            //记录
            Db::name('callback_log')->insert( ['msg_id'=>$msg_id,'time'=>date('Y-m-d H:i:s',time()),'log'=>$status,'is_select'=>0,'ts'=>time()]);

            //测试代码
            //测试收到回调 receive_record 还没当前msg_id这条数据问题
            //查2次 每次隔2秒
            P('进入后备部分_'.$msg_id.date('Y-m-d H:i:s',time()));
            for ($i=1; $i<3;$i++){
                sleep(2);
                $c = Db::name('receive_record')->where(['push_code'=>$msg_id])->find();
                if($c['is_callblack']!=1 && is_array($c)){
                    Db::name('receive_record')->where(['push_code'=>$msg_id])->update(['is_callblack'=>1,'status'=>1,'reason'=>0]);
                    Db::execute('update sp_equipment set groove'.$c['groove_num'].'=groove'.$c['groove_num']."-1,smoke_count=smoke_count+1 where code='".$c['eq_code']."'");
                    break;
                }
            }

            return json(['status'=>0,'info'=>'无数据']);
        }

    }

    //出烟状态查询 index2.html 轮询访问
    public function get_smoke_res()
    {
        $msg_id = trim(input('msg_id'));
        P('get_smoke_res_msg_id_'.$msg_id.'_'.date('Y-m-d H:i:s',time()));

        $check = DB::name('receive_record')->where(['push_code'=>$msg_id,'is_callblack'=>1])->find();
        if($check)
        {
            if($check['status']==1 && $check['reason']==0)  //正常出验
            {
                return json(['status'=>1,'info'=>'领取成功']);
            }
            else if($check['reason']== 2)
            {
                return json(['status'=>2,'info'=>'当前正在出烟']);  //多人在领烟
            }
            else if($check['reason'] == -1)
            {
                return json(['status'=>2,'info'=>'网络不稳定,请稍后重试!']); //其他问题
            }

        }else{
            return json(['status'=>0,'info'=>'失败']);
        }

    }

    //库存报警推送
    public function alarm_smoke($eq_code,$groove_number,$goods_id)
    {
        $eq_info = Db::name('equipment')->where(['code'=>$eq_code])->find();
        $groove = 'groove'.$groove_number;
        $goods_name = Db::name('goods')->where(['id'=>$goods_id])->value('name');

        //alarm_count >0 表示开启报警设置
        //maintain_id >0 表示设置了维护员
        if($eq_info['alarm_count']>0 && $eq_info['maintain_id']>0)
        {
            $maintain_openid = Db::name('maintain_account')->where(['id'=>$eq_info['maintain_id']])->value('openid');
            $groove_count = $eq_info[$groove];
            if($groove_count<$eq_info['alarm_count'])
            {
                $data = array(
                    'touser'=>$maintain_openid,  //接收者openid
                    'groove_good'=>$groove_count, //几号仓
                    'goods_name'=>$goods_name, //烟名
                    'alarm_count'=>$eq_info['alarm_count'],  //阀值
                    'groove'=>$groove_number, //剩余库存
                    'eq_code'=>$eq_code,  //机器号
                );

                $WxService = new \app\common\service\WxService();
                $push_result = $WxService->sendAlarmTemplate($data);
                return $push_result;

            }else{
                return false;
            }

        }else {
            return false;
        }

    }


}