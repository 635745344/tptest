<?php
/**
 * Created by PhpStorm.
 * User: Colin
 * Date: 2018/9/25
 * Time: 10:44
 */
namespace app\api\controller;
use think\Db;
use think\Controller;
use app\common\service\WxService;
use think\Cache;
use app\library\AdminHelper;
use think\Config;

class UrlCode extends Controller{

    //获取新二维码
    public function getNewCode($eq_code,$sign_key){

        $api_sign_key = md5($eq_code.'zonma');
        $key  = _getUrlKey($eq_code); //签名

        P(array($eq_code,$sign_key,$key));

        if($api_sign_key != $sign_key){
            return json(['status'=>0,'msg'=>'sign_key error']);
        }
        if(empty($eq_code)){
            return json(['status'=>0,'msg'=>'eq_code is empty']);
        }

        
        Cache::set($eq_code.'_key',$key,180);  //存缓存180秒过期
        return json(['status'=>1,'key'=>$key]);

    }

    
    public function testKey($eq_code){
        $api_sign_key = md5($eq_code.date('Y-m-d',time())); 
        print_r($api_sign_key);
    }

    public function getTestUrl($eq_code){
        $key  = _getUrlKey($eq_code); //签名
        Cache::set($eq_code.'_key',$key,180);  //存缓存180秒过期
        $url = "http://gzh.zonma.net/mobile/goods/index?eq_code=".$eq_code."&key=".$key;
        print_r($url);
    }


     //检查更新
    public function checkUpdate(){
        // $version = Config::get('app_update_config.version');
        // $filename = Config::get('app_update_config.filename');
        $version = '1.1';
        $filename = 'test.txt'; //  \public\upload\test.txt
        $download_url = "http://gzh.zonma.net/Api/UrlCode/download?filename=".$filename;
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

    //最新MP3判断更新
    public function musicCheck(){
        $version = config('app_music_config.version');
        $filename = config('app_music_config.filename');
        $download_url = "http://gzh.zonma.net/Api/UrlCode/download?filename=".$filename;
        return json(['status'=>1,'version'=>$version,'filename'=>$filename,'download_url'=>$download_url]);
    }



}