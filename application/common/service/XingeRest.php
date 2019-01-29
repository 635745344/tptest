<?php

namespace app\common\service;
use xinge;
use xingetwo;
use think\Exception;
use think\Request;
use think\Db;
use think\Controller;

//腾讯推送
class XingeRest
{
//    protected $accessId="2100315194";
//    protected $secretKey="ADD29EB877PT";
    protected $accessId="2100315194";
    protected $appId="d907fb4bc7e0e";
    protected $secretKey="96dc0cdd689b4ec83696b39f52383f08";
    protected $Xin;
    protected $XinTwo;

    public function __construct()
    {
        vendor('xinge.src.XingeApp');
        vendor('xingetwo.src.XingeApp');
        $this->Xin = new xinge\XingeApp($this->appId,$this->secretKey);
        $this->XinTwo = new xingetwo\XingeApp($this->accessId,$this->secretKey);
    }

    //出烟推送
    public function PushSingSmoke($content,$token,$openid,$eq_code,$goods_id,$groove_num,$push_code)
    {
        $push_log_data=[
            'type'=>3,
            'create_time'=>time(),
            'push_code'=>$push_code,
        ];
        P(['tx_push_log',$push_log_data]);
        $push_id=Db::name('push_log')->insertGetId($push_log_data);

        $check_receive = DB::name('receive_record')->where(['push_code'=>$push_code])->find();
        if(!$check_receive)
        {
            P(['add_receive',$push_code,date('Y-m-d H:i:s',time())]);
            $receive_data=[
                'push_id'=>$push_id,
                'create_time'=>time(),
                'count'=>1,
                'wx_account_id'=>1,
                'openid'=>$openid,
                'eq_code'=>$eq_code,
                'push_code'=>$push_code,
                'goods_id'=>$goods_id,
                'groove_num'=>$groove_num
            ];

            $receive_data['status']=1;
            Db::name('receive_record')->insert($receive_data);
        }

        $res = $this->XinTwo->PushTokenAndroid($this->accessId,$this->secretKey,'出烟推送',$content,$token);
        $ret = true;

        $push_log_data=[
            'result'=>json_encode($res),
        ];

        if($res['ret_code']==0)
        {
            $push_log_data['status'] = 1;
        }else{
            $push_log_data['status'] = 0;
        }


        Db::name('push_log')->where(['id'=>$push_id])->update($push_log_data);
        $check_receive = DB::name('receive_record')->where(['push_code'=>$push_code])->find();
        if(!$check_receive)
        {
            $receive_data=[
                'push_id'=>$push_id,
                'create_time'=>time(),
                'count'=>1,
                'wx_account_id'=>1,
                'openid'=>$openid,
                'eq_code'=>$eq_code,
                'push_code'=>$push_code,
                'goods_id'=>$goods_id,
                'groove_num'=>$groove_num
            ];

            if($res['ret_code']==0)
            {
                $receive_data['status']=1;
                Db::name('receive_record')->where(['push_code'=>$push_code])->update($receive_data);
            }else{
                $receive_data['status']=0;
                $receive_data['reason']=json_encode($res);
                Db::name('receive_record')->where(['push_code'=>$push_code])->update($receive_data);
            }
        }

        return $ret;
    }

    //地址推送
    public function pushUrlSave($content,$token,$push_code){

        $push_log_data=[
            'type'=>4,
            'create_time'=>time(),
            'push_code'=>$push_code,
        ];
        $push_id=Db::name('push_log')->insertGetId($push_log_data);

        $result = $this->XinTwo->PushTokenAndroid($this->accessId,$this->secretKey,'地址更新',$content,$token);

        $is_success=true;

        $push_log_data=[
            'type'=>4,
            'create_time'=>time(),
            'push_code'=>$push_code,
        ];

        if( $result['ret_code']==0 )
        {

            $push_log_data['status']=1;
            $push_log_data['result']=json_encode($result);
            Db::name('push_log')->where(['id'=>$push_id])->update($push_log_data);
        }else{
            $is_success=false;
            $push_log_data['status']=0;
            Db::name('push_log')->where(['id'=>$push_id])->update($push_log_data);
        }

        return $is_success;

    }

    //广告推送
    public function pushImgSave($content,$token,$push_code)
    {
        $push_log_data=[
            'type'=>2,
            'create_time'=>time(),
            'push_code'=>$push_code,
        ];
        $push_id=Db::name('push_log')->insertGetId($push_log_data);

        $result = $this->XinTwo->PushTokenAndroid($this->accessId,$this->secretKey,'广告更新',$content,$token);
        $is_success=true;
        $push_log_data=[
            'type'=>2,
            'create_time'=>time(),
            'push_code'=>$push_code,
        ];

        if( $result['ret_code']==0)
        {
            $push_log_data['status']=1;
            $push_log_data['result']=json_encode($result);
            Db::name('push_log')->where(['id'=>$push_id])->update($push_log_data);
        }else{
            $is_success=false;
            $push_log_data['status']=0;
            Db::name('push_log')->where(['id'=>$push_id])->update($push_log_data);
        }

        return $is_success;

    }

    //检查是否在线
    public function isOnLine($cycle='')
    {
        $msg_id = time();

        $push_log_data=[
            'type'=>1,
            'create_time'=>time(),
            'push_code'=>$msg_id,
        ];
        $push_id=Db::name('push_log')->insertGetId($push_log_data);
        //批量发送
        $result = $this->XinTwo->PushAllAndroid($this->accessId,$this->secretKey,'检查在线情况',json_encode(['type'=>1,'msg_id'=>$msg_id]));

        $is_success=true;
        $push_log_data=[
            'type'=>1,
            'create_time'=>time(),
            'push_code'=>$msg_id,
        ];

        if( $result['ret_code']==0)
        {
            $push_log_data['status']=1;
            $push_log_data['result']=json_encode($result);
            Db::name('push_log')->where(['id'=>$push_id])->update($push_log_data);
        }else{
            $is_success=false;
            $push_log_data['status']=0;
            Db::name('push_log')->where(['id'=>$push_id])->update($push_log_data);
        }

        return $is_success;
    }



    public function PushToken()
    {
        $msg_id = rand(1000,9999);
        $msg=['type'=>4,'key'=>time(),'msg_id'=>$msg_id];
        $res = $this->Xin->PushTokenAndroid($this->appId,$this->secretKey,'地址修改',json_encode($msg),'57d684ff6d720b0d45a2b4a05e7a31560a7a60e8');
        return $res;
    }
    public function PushTokenV2()
    {
        $msg_id = rand(1000,9999);
        $msg=['type'=>4,'key'=>time(),'msg_id'=>$msg_id];
        $res = $this->XinTwo->PushTokenAndroid($this->accessId,$this->secretKey,'地址修改', json_encode($msg),'57d684ff6d720b0d45a2b4a05e7a31560a7a60e8');
        return $res;
    }

    public function QueryPushStatus()
    {
        $pushIdList = array('377366833','377390715');
        $ret = $this->XinTwo->QueryPushStatus($pushIdList);
        return $ret;
    }
}

