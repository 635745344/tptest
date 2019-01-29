<?php

namespace app\common\service;
use think\Request;
use think\Db;
use think\Controller;
use Wechat\WechatMessage;
use think\Cache;
/**
 * 微信数据服务 extends Controller
 */
class WxService extends Controller
{
    //获取双喜获取公众号openid
    public static function getOpenid()
    {
        $request = Request::instance();
        $returnURL = $request->url(true);
        $Authkey = date('Ymd',time()).'SHUANGXI’';
        $originalToken=md5(time().uniqid());

        session('originalToken',$originalToken);

        $url='https://gdzy.zonma.net/index.php/Member/webauth?returnURL='.$returnURL.'&authkey='.$Authkey.'&originalToken='.$originalToken;

        redirect($url);

        if(empty($openid)){
            //获取众码通云防伪平台微信openid
            $WxService=new WxService();
            $WxService->startOAuth( md5(uniqid().time()),'snsapi_base');
        }else{
            session('openid',$openid);
        }

    }

    //发起微信授权(没关注跳转到关注页)
    public function startOAuth($state,$scope)
    {
//        $myfile = fopen("/opt/lampp/htdocs/chuyanjiwebserver/1.txt", "w");
//        fwrite($myfile,$result);

        $request = Request::instance();
        session('before_url',$request->url(true));

        $domain=$request->domain();
        $domain_parts=explode(':',$domain);

        if($domain_parts[count($domain_parts)-1]==80){
            $domain=substr($domain,0,count($domain)-4);
        }
        $callback=$domain."/wx/OAuth/getCode";

        $oauth = & load_wechat('Oauth');
        $result = $oauth->getOauthRedirect($callback, $state, $scope);

        $this->redirect($result);
        exit();
    }

    //发起微信授权(没关注不跳转)
    public function startOAuth2($state,$scope)
    {
//        $myfile = fopen("/opt/lampp/htdocs/chuyanjiwebserver/1.txt", "w");
//        fwrite($myfile,$result);

        $request = Request::instance();
        session('before_url',$request->url(true));

        $domain=$request->domain();
        $domain_parts=explode(':',$domain);

        if($domain_parts[count($domain_parts)-1]==80){
            $domain=substr($domain,0,count($domain)-4);
        }
        $callback=$domain.'/wx/OAuth/getCode2';

        $oauth = & load_wechat('Oauth');
        $result = $oauth->getOauthRedirect($callback, $state, $scope);

        $this->redirect($result);
        exit();
    }

    //发送问卷调查模板
    public function sendQuestionTemplate($data)
    {
        $join_count=Db::name('questionrecord')->count(); //参与人数
        $questionnaire=Db::name('questionnaire')->where(['action_id'=>1,'status'=>1])->field('id,end_time')->order('create_time desc')->find();
        $end_time=$questionnaire['end_time'];
        $questionnaire_id=$questionnaire['id'];

        if($join_count<2586){
            $join_count=2586;
        }

        $list=[
            'first'=>['value'=>'问卷调查','color'=>'#173177'],
            'keyword1'=>['value'=>'问卷调查','color'=>'#173177'],  //标题
            'keyword2'=>['value'=>$join_count.'人','color'=>'#173177'], //参与人数
            'keyword3'=>['value'=>$end_time,'color'=>'#173177'], //统计人数
            'remark'=>['value'=>'点此填写问卷>>>','color'=>'#173177']
        ];

        $request = Request::instance();
        $domain=$request->domain();
        $url=$domain.'/mobile/question/index?id='.$questionnaire_id;

        $data= [
            'touser'=>$data['touser'],
            'template_id'=>'s6nqBSkqf2Z-JT8A0Oyiu4qd7eOcRD-eHWG7ruY4_hA',
            'url'=>$url,
            'data'=>$list
        ];
//
        $Message = & load_wechat('Message');

        $result=$Message->sendTemplateMessage($data);

        if(!empty($result)){
            return true;
        }else{
            return false;
        }
    }
    //发送问卷调查模板
    public function sendQuestionTemplateTwo($data)
    {

        $questionnaire=Db::name('questionnaire')->where(['goods_id'=>$data['goods_id'],'status'=>1])->field('id,title,end_time')->order('create_time desc')->find();

        //当前的烟没有自己的问卷发送通用
        if(!$questionnaire){
            $questionnaire=Db::name('questionnaire')->where(['action_id'=>1,'status'=>1])->field('id,title,end_time')->order('create_time desc')->find();
        }
        $join_count=Db::name('questionrecord')->where(['questionnaire_id'=>$questionnaire['id']])->count(); //参与人数


        $end_time=$questionnaire['end_time'];
        $questionnaire_id=$questionnaire['id'];

        if($join_count<2586){
            $join_count=2586;
        }

        $list=[
            'first'=>['value'=>'问卷调查','color'=>'#173177'],
            'keyword1'=>['value'=>$questionnaire['title'],'color'=>'#173177'],  //标题
            'keyword2'=>['value'=>$join_count.'人','color'=>'#173177'], //参与人数
            'keyword3'=>['value'=>$end_time,'color'=>'#173177'], //统计人数
            'remark'=>['value'=>'点此填写问卷>>>','color'=>'#173177']
        ];

        $request = Request::instance();
        $domain=$request->domain();
        $url=$domain.'/mobile/question/index?id='.$questionnaire_id;

        $data= [
            'touser'=>$data['touser'],
            'template_id'=>'s6nqBSkqf2Z-JT8A0Oyiu4qd7eOcRD-eHWG7ruY4_hA',
            'url'=>$url,
            'data'=>$list
        ];
//
        $Message = & load_wechat('Message');

        $result=$Message->sendTemplateMessage($data);

        if(!empty($result)){
            return true;
        }else{
            return false;
        }
    }
    //发送领烟地址
    public function sendChuanjiUrl($eq_code,$touser)
    {
        P(['eq_code'=>$eq_code,'k'=>'send']);
        $new_key = md5(uniqid().rand(100000,999999).$eq_code);
        Cache::set($eq_code.'_key',$new_key,180);
//        $content = "出烟机 $eq_code 领烟地址：http://gzh.zonma.net/mobile/goods/index?eq_code=$eq_code&key=$new_key";
//        $parame = array(
//            "touser"  => $touser,
//            "msgtype" => "text",
//            "text"    => array(
//                "content" => $content,
//            )
//        );
        $url = "http://gzh.zonma.net/mobile/goods/index?eq_code=$eq_code&key=$new_key";
        $parame = array(
            "touser"  => $touser,
            "msgtype" => "news",
            "news"    => array(
                "articles" => array(
                    array(
                        "title"=>"点击领烟",
                        "description"=>"该信息3分钟内有效",
                        "url"=>$url,
                        "picurl"=>'',
                    ),
                )
            )
        );
        P($parame);
        $Message = & load_wechat('Message');

        $result=$Message->sendCustomMessage($parame);
        Cache::rm('set_follow');
        if(!empty($result)){
            return true;
        }else{
            return false;
        }
    }

}
