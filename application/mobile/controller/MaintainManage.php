<?php
/**
 * Created by PhpStorm.
 * User: Colin
 * Date: 2018/4/19
 * Time: 14:39
 */

namespace app\mobile\controller;
use think\Controller;
use think\Session;
use think\Db;
use think\Request;
use app\mobile\controller\MobileBase;
use app\library\Helper;
use think\Cache;


//维护员管理
class MaintainManage extends MobileBase
{
    protected function _initialize(){
        parent::_initialize();

        $this->maintain_id = session('maintain_id');
        $this->openid = session('openid');
        $this->user_id=session('user')['id'];
         if(empty($this->maintain_id)){
            $maintain = Db::name('maintain_account')->where(['openid'=>$this->openid,'status'=>1])->find();
            if(!empty($maintain)){
                $this->maintain_id=$maintain['id'];
            }else{
                $this->redirect('/mobile/common/prompt2?msg='.'亲，您不是维护员，没有权限进入！');
            }
         }
    }
    
    public function index()
    {
        $this->redirect('/mobile/MaintainManage/searchEquipmentView');
    }
    //搜索设备界面
    public function searchEquipmentView()
    {
        return view();
    }
    //设备信息界面
    public function equipmentInfoView($eq_code)
    {
        $eq_info=Db::name('equipment')->alias('e')
            ->join('sp_shop s','e.shop_id=s.id','left')
            ->join('sp_maintain_account ma','e.maintain_id=ma.id','left')
            ->where(['e.code'=>$eq_code])
            ->field('e.code,FROM_UNIXTIME(e.activation_time) activation_time,FROM_UNIXTIME(e.last_bind_time) last_bind_time,
            e.smoke_count,e.run_time,s.id shop_id,s.name sp_name,ma.name ma_name')
            ->find();

        $eq_info['code']=empty($eq_info['code'])?'':$eq_info['code'];
        $eq_info['activation_time']=empty($eq_info['activation_time'])?'':$eq_info['activation_time'];
        $eq_info['last_bind_time']=empty($eq_info['last_bind_time'])?'':$eq_info['last_bind_time'];
        $eq_info['smoke_count']=empty($eq_info['smoke_count'])?'':$eq_info['smoke_count'];
        $eq_info['run_time']=empty($eq_info['run_time'])?0: Helper::convertTimeSlot($eq_info['run_time']);
        $eq_info['shop_id']=empty($eq_info['shop_id'])?0:$eq_info['shop_id'];
        $eq_info['sp_name']=empty($eq_info['sp_name'])?'':$eq_info['sp_name'];
        $eq_info['ma_name']=empty($eq_info['ma_name'])?'':$eq_info['ma_name'];

        $new_key = md5(uniqid().rand(100000,999999).$eq_code);
        Cache::set($eq_code.'_key',$new_key,86400);

        return view('',['eq_info'=>$eq_info,'key'=>$new_key]);
    }
    //查询店铺界面
    public function searchShopView($eq_code)
    {
        if(!empty($eq_code)){
            $info = Db::name('v_eq_coordinate')->where(['eq_code'=>$eq_code])->find();
        }
        return view('',['eq_code'=>$eq_code,'info'=>json_encode($info)]);
    }
    //其他操作界面
    public function otherOperView($eq_code)
    {
        return view();
    }
    //装货界面
    public function configView()
    {
        return view();
    }
    //搜索店铺
    public function searchShop($monopolyId)
    {
        $data = Db::name('shop')->where(['monopolyId'=>$monopolyId])->find();
        if(empty($data)){
            return json(['status'=>0,'info'=>'烟草证号不存在','data'=>$data]);
        }else{
            return json(['status'=>1,'info'=>'ok','data'=>$data]);
        }
    }
    //绑定店铺
    public function bindShop($eq_code,$shop_id)
    {
        $equipment=Db::name('equipment')->where(['code'=>$eq_code])->field('shop_id')->find();
        if(!empty($equipment['shop_id'])){
            return json(['status'=>0,'info'=>'该设备已绑定，不能重复绑定！']);
        }
        if(Db::name('shop')->where(['id'=>$shop_id,'status'=>1])->count()<=0){
            return json(['status'=>0,'info'=>'店铺不存在！']);
        }

        Db::name('equipment')->where(['code'=>$eq_code])->update(['shop_id'=>$shop_id,'maintain_id'=>$this->user_id,'last_bind_time'=>time(),'update_time'=>time()]);
        return json(['status'=>1,'info'=>'绑定成功！']);
    }

    public function searchEquipment($eq_code)
    {
        if(Db::name('equipment')->where(['code'=>$eq_code])->count()>0){
            return json(['status'=>1,'info'=>'设备存在']);
        }else{
            return json(['status'=>0,'info'=>'设备不存在']);
        }
    }
    //发送操作
    public function sendOper($eq_code,$code)
    {
        $data=[
            'maintain_id'=>$this->maintain_id,
            'eq_code'=>$eq_code,
            'code'=>$code,
            'create_time'=>time(),
        ];

        Db::name('eq_oper_log')->insert($data);
        return json(['status'=>1,'info'=>'提交成功！']);
    }
    //补货
    public function configSmoke()
    {
        $params=input('post.');
        if(Db::name('equipment')->where(['code'=>$params['eq_code']])->count()<=0){
            return json(['status'=>0,'info'=>'设备编号不存在！']);
        }

        $data=[
            'groove1'=>$params['groove1'],
            'groove2'=>$params['groove2'],
            'groove3'=>$params['groove3'],
            'groove4'=>$params['groove4'],
            'groove5'=>$params['groove5'],
            'groove6'=>$params['groove6'],
            'groove7'=>$params['groove7'],
        ];
        Db::name('equipment')->where(['code'=>$params['eq_code']])->update($data);

        $data_eq_oper_log=[
            'maintain_id'=>$this->maintain_id,
            'eq_code'=>$params['eq_code'],
            'code'=>2,
            'create_time'=>time(),
        ];

        Db::name('eq_oper_log')->insert($data_eq_oper_log);

        return json(['status'=>1,'info'=>'提交成功！']);
    }

    //2018/12/20 add
    public function shop_data_set()
    {
        $shop_id = trim(input('shop_id'));
        $eq_code = trim(input('eq_code'));
        $array = array(
            'name' =>trim(input('name')),
            'duty_name' =>trim(input('duty_name')),
            'phone' =>trim(input('phone')),
            'province' =>trim(input('province')),
            'city' =>trim(input('city')),
            'district' =>trim(input('district')),
            'address' =>trim(input('address')),
            'longitude' =>trim(input('longitude')),
            'latitude' =>trim(input('latitude')),
            'monopolyId' =>trim(input('monopolyId')),
            'openid'=>$this->openid,
        );

//        dump($array);exit;

        if($shop_id > 0 )
        {
            $array['update_time'] = time();
            Db::name('shop')->where(['id'=>$shop_id])->update($array);
            Db::name('equipment')->where(['code'=>$eq_code])->update(['shop_id'=>$shop_id]);
        }else{
            $array['create_time'] = time();
            $shop_id = Db::name('shop')->insertGetId($array);
            Db::name('equipment')->where(['code'=>$eq_code])->update(['shop_id'=>$shop_id]);
        }

        return json(['status'=>1,'info'=>'提交成功','shop_id'=>$shop_id]);

    }


}