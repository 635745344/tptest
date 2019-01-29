<?php
/**
 * Created by PhpStorm.
 * User: Colin
 * Date: 2018/11/14
 * Time: 10:16
 */

namespace app\home\controller;
use think\Controller;
use think\Session;
use think\Db;
use think\Request;
use think\Cache;

class Reveal extends Controller
{
    public function index()
    {
        $eq_code = trim(input('eq_code'));
        $group_id = Db::name('equipment')->where(['code'=>$eq_code])->field('group_id')->find()['group_id'];
        $adv = Db::name('advertisement')->where(['group_id'=>$group_id])->find();

        return view('',['eq_code'=>$eq_code,'opt'=>$adv['opt']]);

    }

    public function dd()
    {
//        $this->PutMovie('upload/advertisement/20181114/c5f87f6424af9ac2b13f3ca33d718960.mp4');
        return view();
    }


    //获取广告或者视频地址
    public function get_reveal($eq_code='')
    {
        if(empty($eq_code))
        {
            return json(['status'=>0,'info'=>'参数异常']);
        }

        $group_id = Db::name('equipment')->where(['code'=>$eq_code])->field('group_id')->find()['group_id'];
        $adv = Db::name('advertisement')->where(['group_id'=>$group_id])->find();

        $img_info = $adv['img_info'];
        $arr = json_decode($img_info,true);

        $img = [];
        if(is_array($arr)) foreach ($arr as $k => $v)
        {
            if($v['status']==1){
                $img[] = $v['url'];
            }
        }

        return json(['status'=>1,'info'=>'成功','img_info'=>$img,'stop_time'=>$adv['stop_time'],'opt'=>$adv['opt'],'video_url'=>$adv['video_url']]);
    }

    public function PutMovie($file) {
        header("Content-type: video/mp4");
        header("Accept-Ranges: bytes");

        $size = filesize($file);
        dump($size);exit;

        if(isset($_SERVER['HTTP_RANGE'])){
            header("HTTP/1.1 206 Partial Content");
            list($name, $range) = explode("=", $_SERVER['HTTP_RANGE']);
            list($begin, $end) =explode("-", $range);
            if($end == 0) $end = $size - 1;
        }
        else {
            $begin = 0; $end = $size - 1;
        }
        header("Content-Length: " . ($end - $begin + 1));
        header("Content-Disposition: filename=".basename($file));
        header("Content-Range: bytes ".$begin."-".$end."/".$size);

        $fp = fopen($file, 'rb');
        fseek($fp, $begin);
        while(!feof($fp)) {
            $p = min(1024, $end - $begin + 1);
            $begin += $p;
            echo fread($fp, $p);
        }
        fclose($fp);
    }
}