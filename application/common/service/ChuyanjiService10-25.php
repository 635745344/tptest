<?php

namespace app\common\service;
use JPush\Client as JPush;
use think\Exception;
use think\Request;
use think\Db;
use think\Controller;

class ChuyanjiService
{
//    protected $app_key='510c98aca2536c9f07772e04';
//    protected $master_secret='0b9892c0b65c60a2a569b03d';
    protected $app_key='a3d4031a80300076a89406d3';
    protected $master_secret='7bc262885bdc597433f7ab57';
    protected $client;

    public function __construct()
    {
        vendor('JPush.autoload');
        $this->client = new JPush($this->app_key, $this->master_secret);
    }
    //推送广告修改通知
    public function pushImgInfo($msg)
    {
        //->setNotificationAlert($msg)
        $result = $this->client->push()->setPlatform('all')->addAllAudience()->message($msg, array(
            'title' => '广告修改',
            'content_type' => 'text',
        ))->setOptions(1,0)->send();

        $result = json_decode(json_encode($result),true);

        $is_success=true;
        if( !empty($result['body']['msg_id']) )
        {
            $msg_id=(float)($result['body']['msg_id']);

            $equipments=Db::name('equipment')->field('registration_id')->where(['status'=>1])->select();

            $registration_ids=[];
            foreach ($equipments as $k=>$v)
            {
                if(!empty($v['registration_id'])){
                    $registration_ids[]=$v['registration_id'];
                }
            }
            $reason='';
            try{
                $result = $this->getPush($msg_id,$registration_ids);
            }catch (\Exception $e){
                $result=[];
                $reason=$e->getMessage();
            }

            if(empty($result)){
                $result='';
            }else{
                $result=json_encode($result);
            }
            //添加推送日志
            Db::name('push_log')->insert(['type'=>2,'msg_id'=>$msg_id,'reason'=>$reason,'result'=>$result,'status'=>1,'create_time'=>time()]);

        }else{
            Db::name('push_log')->insert(['type'=>2,'status'=>0,'reason'=>$result,'create_time'=>time()]);
            $is_success=false;
        }
        return $is_success;
    }
    //出烟通知
    public function pushSmoke($msg,$registration_id,$openid,$eq_code,$goods_id,$groove_num)
    {
//        $result = $this->client->push()->setPlatform('all')->addRegistrationId($registration_id)->setNotificationAlert($msg)->send();
        $result = $this->client->push()->setPlatform('all')->addRegistrationId($registration_id)->message($msg, array(
            'title' => '出烟通知',
            'content_type' => 'text',
        ))->setOptions(1,0)->send();
        //1507bfd3f7e7e3acf2b
//        $result = $this->client->push()->setPlatform('all')->addRegistrationId(['160a3797c8296f7c365'])->message($msg, array(
//            'title' => '出烟通知',
//            'content_type' => 'text',
//        ))->send();

        $result = json_decode(json_encode($result),true);

        P($result);
        
        $is_success=true;
        $push_log_data=[
            'type'=>3,
            'create_time'=>time()
        ];
        $push_id=0;
        if( !empty($result['body']['msg_id']) )
        {
            $msg_id=(float)$result['body']['msg_id'];
            //添加推送日志
            $push_log_data['msg_id']=$msg_id;
            $push_log_data['status']=1;

            try{
                $push_log_data['result']=json_encode($this->getPush($msg_id,[$registration_id])) ;
//          $push_log_data['result']=json_encode($this->getPush($msg_id,['160a3797c8296f7c365'])) ;
            }catch (\Exception $e){
                $push_log_data['reason']=$e->getMessage();
            }
            P($push_log_data);
            $push_id=Db::name('push_log')->insertGetId($push_log_data);
        }else{
            $is_success=false;
            $push_log_data['status']=0;
            $push_id=Db::name('push_log')->insertGetId($push_log_data);
        }

        $receive_data=[
            'push_id'=>$push_id,
            'create_time'=>time(),
            'count'=>1,
            'wx_account_id'=>1,
            'openid'=>$openid,
            'eq_code'=>$eq_code,
            'goods_id'=>$goods_id,
            'groove_num'=>$groove_num
        ];
        if($is_success){
            $receive_data['status']=1;
            Db::name('receive_record')->insert($receive_data);
        }else{
            $receive_data['status']=0;
            $receive_data['reason']=json_encode($result);
            Db::name('receive_record')->insert($receive_data);
        }
        return $is_success;
    }

    //出烟通知2新增循环查询状态
    public function pushSmokeTwo($msg,$registration_id,$openid,$eq_code,$goods_id,$groove_num)
    {
        $result = $this->client->push()->setPlatform('all')->addRegistrationId($registration_id)->message($msg, array(
            'title' => '出烟通知',
            'content_type' => 'text',
        ))->setOptions(1,0)->send();

        $result = json_decode(json_encode($result),true);
        P($result);
        $is_success=true;
        $push_log_data=[
            'type'=>3,
            'create_time'=>time()
        ];
        $push_id=0;
        if( !empty($result['body']['msg_id']) )
        {
            $m_id=$result['body']['msg_id'];
            $msg_id = (float)$m_id;
            P('msg_id_'.$msg_id);
            //添加推送日志
            sleep(1);
            $push_log_data['msg_id']=$msg_id;
            $push_log_data['status']=1;
            try{
                //新增部分
                $msg_status = json_encode($this->getPush($msg_id,[$registration_id]));
                $result_arr = json_decode($msg_status,true);
                P($result_arr);
                if($result_arr[$registration_id]['status']!=0)
                {
                    $is_success = false;
                    for ($i=0; $i<4; $i++)
                    {
                        sleep(3);
                        $beg = json_encode($this->getPush($msg_id,[$registration_id]));
                        $arr = json_decode($beg,true);
                        P($arr);
                        if($arr[$registration_id]['status']==0)
                        {
                            $msg_status = $beg;
                            $is_success = true;
                            break;
                        }
                    }

                }else{
                    $is_success = true;
                }
                $push_log_data['result'] = $msg_status;
                //end
            }catch (\Exception $e){
                P('catch_'.$msg_id);
                $is_success = false;
                $push_log_data['reason']=$e->getMessage();
            }

            $push_id=Db::name('push_log')->insertGetId($push_log_data);
        }else{
            $is_success=false;
            $push_log_data['status']=0;
            $push_id=Db::name('push_log')->insertGetId($push_log_data);
        }

        $receive_data=[
            'push_id'=>$push_id,
            'create_time'=>time(),
            'count'=>1,
            'wx_account_id'=>1,
            'openid'=>$openid,
            'eq_code'=>$eq_code,
            'goods_id'=>$goods_id,
            'groove_num'=>$groove_num
        ];
        if($is_success){
            $receive_data['status']=1;
            Db::name('receive_record')->insert($receive_data);
        }else{
            $receive_data['status']=0;
            $receive_data['reason']=json_encode($result);
            Db::name('receive_record')->insert($receive_data);
        }
        return $is_success;
    }
    //获取推送结果
    public function getPush($msg_id,$rids)
    {
        $report = $this->client->report();
        P('getPush_msg_id_'.$msg_id);
        $registration_ids=[];
        $result=[];
        $count=0;
        $all_count=count($rids);
        foreach ($rids as $k=>$v)
        {
            if(!empty($v)){
                $registration_ids[]=$v;
                $count++;
            }
//          var_dump($msg_id);exit();
            if($count>=1000  || $k+1 >= $all_count){
                $request = $report->getMessageStatus((float)$msg_id,$registration_ids);
                if(empty($request['body']['error']))
                {
                    $request = json_decode( json_encode($request['body']) );
                    foreach ($request as $k_request=>$v_request){
                        $result[$k_request]=$v_request;
                    }
                }

                $count=0;
            }
        }
        return $result;
    }
    //检查是否在线
    public function isOnLine($cycle)
    {
//      ->setNotificationAlert(json_encode(['type'=>1]))
        $result = $this->client->push()->setPlatform(['android'])->addAllAudience()->message(json_encode(['type'=>1]), array(
            'title' => '检查在线情况',
            'content_type' => 'text',
        ))->send();

        $result = json_decode(json_encode($result),true);

        $is_success=true;
        if( !empty($result['body']['msg_id']) )
        {
            $msg_id= (float)$result['body']['msg_id'];

            $equipments=Db::name('equipment')->field('registration_id')->select();

            $registration_ids=[];
            foreach ($equipments as $k=>$v)
            {
                if(!empty($v['registration_id'])){
                    $registration_ids[]=$v['registration_id'];
                }
            }
            $result=[];
            $reason='';
            try{
                $result = $this->getPush($msg_id,$registration_ids);
            }
            catch (\Exception $e){
                $reason=$e->getMessage();
            }
            $result=json_decode(json_encode($result),true);

//            //添加推送日志
//            Db::name('push_log')->insert(['type'=>1,'msg_id'=>$msg_id,'result'=>json_encode($result),'status'=>1,'create_time'=>time()]);
            if(!empty($result)){

                $eq_zx=[]; //在线
                $eq_lx=[]; //离线

                foreach ($result as $k_result=>$v_result)
                {
                    if(in_array($v_result['status'],[0,3]) ) //送达
                    {
                        $eq_zx[]=$k_result;
                    }
                }

                $all_eq = Db::name('equipment')->field('id,registration_id,status')->select();

                foreach ($all_eq as $k_all_eq=>$v_all_eq)
                {
                    $is_lx=true;
                    foreach ($eq_zx as $k_eq_zx=>$v_eq_zx)
                    {
                        if($v_all_eq['registration_id']==$v_eq_zx){
                            $is_lx=false;
                        }
                    }
                    if($is_lx){
                        $eq_lx[]=$v_all_eq['registration_id'];
                    }
                }

                Db::name('equipment')->where(['registration_id'=>['in',$eq_zx]])->update(['status'=>1]); //在线
                Db::name('equipment')->where(['registration_id'=>['in',$eq_lx]])->update(['status'=>0]); //离线

                $registration_ids='';
                foreach ($eq_zx as $k_eq_zx => $v_eq_zx){
                    $registration_ids .= "'".$v_eq_zx."'".",";
                }
                $registration_ids=substr($registration_ids,0,strlen($registration_ids)-1);

                if(!empty($registration_ids)){
                    Db::execute(' update sp_equipment set run_time = run_time+'.$cycle.' where registration_id in ('.$registration_ids.')');
                }
            }

           Db::name('push_log')->insert(['type'=>1,'status'=>1,'msg_id'=>$msg_id,'result'=>json_encode($result),'reason'=>$reason,'create_time'=>time()]);

        } else
        {
            Db::name('push_log')->insert(['type'=>1,'status'=>0,'reason'=>$result,'create_time'=>time()]);
            $is_success=false;
        }
        return $is_success;
    }

    //推送新URL
    public function pushUrl($msg,$registration_id){
        $result = $this->client->push()->setPlatform('all')->addRegistrationId($registration_id)->message($msg, array(
            'title' => '地址更新',
            'content_type' => 'text',
        ))->setOptions(1,0)->send();

        $result = json_decode(json_encode($result),true);
        $is_success=true;

        $push_log_data=[
            'type'=>4,
            'create_time'=>time()
        ];
        $push_id=0;
        if( !empty($result['body']['msg_id']) )
        {
            $msg_id=(float)$result['body']['msg_id'];
            //添加推送日志
            $push_log_data['msg_id']=$msg_id;
            $push_log_data['status']=1;

            try{
                $push_log_data['result']=json_encode($this->getPush($msg_id,[$registration_id])) ;
            }catch (\Exception $e){
                $push_log_data['reason']=$e->getMessage();
            }

            $push_id=Db::name('push_log')->insertGetId($push_log_data);
        }else{
            $is_success=false;
            $push_log_data['status']=0;
            $push_id=Db::name('push_log')->insertGetId($push_log_data);
        }

        return $is_success;
       
    }

    public function pushSmokeTwoTest($msg,$registration_id)
    {
        $result = $this->client->push()->setPlatform('all')->addRegistrationId($registration_id)->message($msg, array(
            'title' => '出烟通知',
            'content_type' => 'text',
        ))->setOptions(1,0)->send();

        $result = json_decode(json_encode($result),true);
        dump($result);
        $is_success=true;
        $push_log_data=[
            'type'=>3,
            'create_time'=>time()
        ];
        $push_id=0;
        if( !empty($result['body']['msg_id']) )
        {
            $m_id=$result['body']['msg_id'];
            $msg_id = (float)$m_id;
            //添加推送日志
            $push_log_data['msg_id']=$msg_id;
            $push_log_data['status']=1;
//            try{
                //新增部分
            dump($msg_id);
                $msg_status = json_encode($this->getPush($msg_id,[$registration_id]));
                $result_arr = json_decode($msg_status,true);
            dump($result_arr);
            if($result_arr[$registration_id]['status']!=0)
                {
                    $is_success = false;
                    for ($i=0; $i<4; $i++)
                    {
                        sleep(2);
                        $beg = json_encode($this->getPush($msg_id,[$registration_id]));
                        $arr = json_decode($beg,true);
                        dump($arr);
                        if($arr[$registration_id]['status']==0)
                        {
                            $msg_status = $beg;
                            $is_success = true;
                            break;
                        }
                    }

                }else{
                    $is_success = true;
                }
                $push_log_data['result'] = $msg_status;
//                //end
//            }catch (\Exception $e){
//                var_dump('catch_'.$msg_id);
//                $is_success = false;
//                $push_log_data['reason']=$e->getMessage();
//            }
        }else{
            $is_success=false;
        }


        return $is_success;
    }
}

