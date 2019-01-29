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
        $registration_id=input('registration_id');
        $equipment_id=input('equipment_id');
        $mac=input('mac');

         if(empty($registration_id) || empty($equipment_id) || empty($mac)){
            return json(['status'=>0,'info'=>'参数异常']);
         }

        //检查极光推送注册id是否存在
        //$equipment=Db::name('equipment')->where(['registration_id'=>$registration_id])->find();
        //if(!empty($equipment)){
            //return json(['status'=>0,'info'=>'极光推送注册id已经注册','code'=>$equipment['code']]);
        //}
        

        //根据equipment_id判断是否存在 存在就更新 2018-09-26 update
        $check_equipment = Db::name('equipment')->where(['equipment_id'=>$equipment_id])->find();

        if($check_equipment){ 
            //equipment_id存在 更新数据
            $new_code = $check_equipment['code'];
            Db::name('equipment')->where(['equipment_id'=>$equipment_id])->update(['registration_id'=>$registration_id,'mac'=>$mac]);

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
            }

            $eq_config=Db::name('equipment_default')->find();

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
                'registration_id'=>$registration_id,
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
        P(['registration_id'=>$registration_id,'equipment_id'=>$equipment_id,'new_code'=>$new_code,'mac'=>$mac,'check_equipment'=>$check_equipment]);
        return json(['status'=>1,'info'=>'初始化成功!','code'=>$new_code]);
    }

    //获取广告
    public function getAdvertisement()
    {
        $imgs=[];
        $data=Db::name('advertisement')->where(['status'=>1])->find();

        foreach (json_decode($data['img_info'],true) as $k=>$v ){
            if($v['status']==1 && !empty($v['url']))
            {
                $imgs[]=$v['url'];
            }
        }

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

        //开启文件锁
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
        $version = config('app_update_config.version');
        $filename = config('app_update_config.filename');
        $download_url = "http://gzh.zonma.net/Api/Chuyanji/download?filename=".$filename;
        return json(['status'=>1,'version'=>$version,'filename'=>$filename,'download_url'=>$download_url]);
        
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
            Header("Accept-Length:".filesize($file_dir));
            Header("Content-Disposition: attachment;filename=" . $filename);            
            ob_clean();   
            flush(); 
            echo fread($file1, filesize($file_dir));
            fclose($file1);
        }
    }

    //管理员判断
    public function adminCheck($account,$pwd){

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
        $version = config('app_music_config.version');
        $filename = config('app_music_config.filename');
        $download_url = "http://gzh.zonma.net/Api/Chuyanji/download?filename=".$filename;
        return json(['status'=>1,'version'=>$version,'filename'=>$filename,'download_url'=>$download_url]);
    }

    
}