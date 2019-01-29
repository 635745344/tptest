<?php
/**
 * Created by PhpStorm.
 * User: Colin
 * Date: 2018/6/13
 * Time: 17:21
 */

namespace app\api\controller;

use think\Controller;
use think\Db;
use think\Session;
use think\Request;
use app\common\service\WxService;
use think\Cache;
use think\UserAgent;
use think\Curl;

class Test extends Controller
{
    //创建微信url
    public function createWxUrl($returnURL='',$scope='snsapi_base',$authkey='',$originalToken='')
    {

        $oauth = & load_wechat('Oauth');

        $result = $oauth->getOauthRedirect('http://gzh.zonma.net/api/test/getUserinfo?callback='.urlencode($returnURL), time(), $scope);

        if($result===FALSE){
            return 'false_createWxUrl';
        }else{
            $this->redirect($result);
        }

    }

    //创建微信url
    public function getUserinfo($returnURL)
    {
        $returnURL=urldecode($returnURL);

        $oauth = & load_wechat('Oauth');

        $result = $oauth->getOauthAccessToken();

        $openid=$result['openid'];
        $access_token=$result['access_token'];

        if(!empty($result) && $result['scope']=='snsapi_userinfo'){
            $result = $oauth->getOauthUserinfo($access_token, $openid);
        }
        
        if($result===FALSE){
            return 'false_getUserinfo';
        }else{
            return json($result);
        }
    }

    //判断没有关注获取用户信息结果
    public function noFollowUser()
    {
        // 实例微信粉丝接口
        $user = & load_wechat('User');
        $openid = session('openid');
        // 读取微信粉丝列表oRD28w3O430Mi0GBWjf6-_zqrItg
        $info = $user->getUserInfo('oRD28w4KrvUWc8hr0AYKjCNZO3aE');

        var_dump($info);
    }

    public function sleeptime()
    {
        $start_time=time();
        sleep(5);
        echo time()-$start_time;
    }

    public function checktuisong(){
        $msg=[
            'type'=>3,
            'groove_num'=>2,
        ];
        $msg=json_encode($msg);
        $rid = "190e35f7e06ffc96b43";
        $chuyanjiService = new \app\common\service\ChuyanjiService();
        $chuyanjiService->pushSmokeTwoTest($msg,$rid);
    }

    public  function qiniu()
    {
        $data=file_get_contents('php://input');
        $arr =json_decode($data,true);
        P(['']);
        if(!empty($arr) && $arr['code']==0)
        {
            $item=$arr['items'][0];

            $cutimg='http://owiqdymo0.bkt.clouddn.com/'.$item['key'].'?imageMogr2/gravity/Center/crop/400x400';
            $thumbimg='http://owiqdymo0.bkt.clouddn.com/'.$item['key'];
            $img='http://owiqdymo0.bkt.clouddn.com/'. $arr['inputKey'];
        }
    }



    public function tuisongTest(){
        $msg_id= (float)trim(Input('get.msg_id'));
        $rid= trim(Input('get.rid'));
        $chuyanjiService = new \app\common\service\ChuyanjiService();
        $a = json_encode($chuyanjiService->getPush($msg_id,[$rid]));
        var_dump($a);
        $is_success = false;
        for ($i=0; $i<3; $i++)
        {
            sleep(2);
            $beg = json_encode($chuyanjiService->getPush($msg_id,[$rid]));
            $arr = json_decode($beg,true);
            var_dump($arr);
            if($arr[$rid]['status']==0)
            {
                $msg_status = $beg;
                $is_success = true;
                break;
            }
        }
        var_dump($is_success);
    }

    public function testJson(){
        $arr = Db::name('push_log')->where(['msg_id'=>'2329861234'])->find();
        $json = json_decode($arr['result'],true);
        $status = $json['1507bfd3f7e7e3acf2b']['status'];
        var_dump($status);
    }

    public function wenjuan(){
        $data_questionnaire = ['touser'=>'oRD28w4KrvUWc8hr0AYKjCNZO3aE','goods_id'=>22];
        $WxService = new \app\common\service\WxService();
        $push_result = $WxService->sendQuestionTemplateTwo($data_questionnaire);
        var_dump($push_result);
    }

    public function logout(){
        session('openid',null);
        session('user',null);

    }

    //获取新二维码
    public function getNewCode($eq_code){
        //$day = date('Y-m-d',time());
        $key  = _getUrlKey($eq_code); //签名
        Cache::set($eq_code.'_key',$key,86400);  //存缓存180秒过期
        $url = "http://gzh.zonma.net/mobile/goods/index?eq_code=".$eq_code."&key=".$key;
        print_r($url);
    }


    public function xinge(){
        $token = 'b22a6cef4b6d478a441caea14e4af97b63a2838d';
        $title = '测试标题';
        $content = '测试内容';
        $XingeRest = new \app\common\service\XingeRest();
//        $push_result = $XingeRest->PushSingleDeviceMessage($token,$title,$content);
//        var_dump($push_result);
        $push_result = $XingeRest->PushToken();
        var_dump($push_result);
    }

    public function xingeV2(){
        $token = '57d684ff6d720b0d45a2b4a05e7a31560a7a60e8';
        $title = '测试标题';
        $content = '测试内容';
        $openid = 'oRD28ww4A4xwIG3Yd7a8y04Kq6Ys';
        $eq_code = '100035';
        $goods_id = 23;
        $groove_num = 3;
        $push_code = time();
        $msg=[
            'type'=>3,
            'groove_num'=>3,
            'msg_id'=>$push_code,
        ];

        $XingeRest = new \app\common\service\XingeRestNew();
        $push_result1 = $XingeRest->PushSingSmoke(json_encode($msg),$token,$openid,$eq_code,$goods_id,$groove_num,$push_code);
        dump($push_result1);


        $chuyanjiService =new \app\common\service\ChuyanjiServiceNew();
        $push_result = $chuyanjiService->pushSmoke(json_encode($msg),'1a0018970a89ac754fc','',$eq_code,$goods_id,$groove_num,$push_code);
        dump($push_result);

        exit;

       dump(config('app_old_push.lis'));
//        $p2 = date('His',time()) . rand(1000,9999);
//        $m=['type'=>4,'key'=>time(),'msg_id'=>$p2];
//        $pus = $XingeRest->isOnLine();
//        $push_result = $XingeRest->PushTokenV2();
//        var_dump($pus);

    }

    public function wj(){

        $f = Db::name('questionnaire')->where("id=118")->find();
        unset($f['id']);

        $new_ID = Db::name('questionnaire')->insertGetId($f);

        $list = Db::name('question')->where("questionnaire_id=118")->field('title,type,create_time,option')->select();
        foreach ($list as $key=>$value){
            $list[$key]['questionnaire_id'] =$new_ID;
        }

        Db::name('question')->insertAll($list);
    }

    public function pptt()
    {
        $ts = date('Y-m-d H:i:s',time());
        return json(['status'=>1,'msg'=>'定时任务访问成功','ts'=>$ts]);
    }


    public function altest()
    {

        $push_result = $this->alarm_smoke('100028',2,23);


        dump($push_result);
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