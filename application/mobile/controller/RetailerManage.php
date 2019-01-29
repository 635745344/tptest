<?php
/**
 * Created by PhpStorm.
 * User: Colin
 * Date: 2018/4/19
 * Time: 14:38
 */

namespace app\mobile\controller;
use think\Controller;
use think\Session;
use think\Db;
use think\Request;
use app\mobile\controller\MobileBase;

//零售户管理
class RetailerManage  extends MobileBase
{
    protected function _initialize(){
        parent::_initialize();
        //检查是否关注公众号
//        $is_subscribe=session('is_subscribe');
//        if(empty($is_subscribe)){
//            $this->redirect('mobile/common/zmtyfwptFollow');
//        }

        $this->openid=session('openid');
        $this->db_sx_wechat = Db::connect("db_sx_wechat");
    }
    //添加店铺界面(零售户)
    public function index()
    {
        $old_retailer=Db::name('retailer_account')->where(['openid'=>$this->openid])->find();
        if( empty($old_retailer) )
        {
            $this->redirect('/mobile/RetailerManage/addView');
        }else{
            $shop=Db::name('shop')->where(['monopolyId'=>$old_retailer['monopolyId']])->find();
            $this->redirect('/mobile/RetailerManage/retailerInfoView?shop_id='.$shop['id']);
        }
    }
    //添加店铺界面(零售户)
    public function addView()
    {
        return view();
    }
    //信息页(零售户)
    public function retailerInfoView($eq_code)
    {
        $shop_info = Db::name('shop')->alias('s')
            ->join('equipment e','s.id=e.shop_id','left')
            ->field('s.name,s.duty_name,s.phone,s.monopolyId,s.province,s.city,s.district,s.address,e.code')
            ->where(['e.code'=>$eq_code])->find();
        $eq_code=Db::name('equipment')->where(['code'=>$eq_code])->field('code')->find()['code'];

//        return json($shop_info);
        return view('',['shop_info'=>$shop_info,'eq_code'=>$eq_code]);
    }
    //指令界面(零售户)
    public function retailerCommandView()
    {
        $eq_code = trim(input('eq_code'));

      $data = Db::name('v_eq_coordinate')->where(['eq_code'=>$eq_code])->find();
//        dump($data);exit;

        return view('',['data'=>$data]);
    }
    //选择指令界面(零售户)
    public function retailerErrorView()
    {
        return view();
    }
    //添加店铺(零售户)
    public function add()
    {
        $params=input('post.');

        if(Db::name('shop')->where(['monopolyId'=>$params['monopolyId']])->count()>0){
            return json(['status'=>0,'info'=>'该烟草专卖证号已绑定其他店铺']);
        }

        $is_examine=false;
        //烟草证号是否有效
        if($this->db_sx_wechat->name('common_retailers')->where(['monopolyId'=>$params['monopolyId'],'status'=>0])->count()<=0)
        {
            $is_examine=true;
        }

        $status=1;
        if($is_examine){
            $status=2;
        }

        //添加店铺
        $shop_data=[
            'name'=>$params['name'],
            'monopolyId'=>$params['monopolyId'],
            'duty_name'=>$params['duty_name'],
            'phone'=>$params['phone'],
            'province'=>$params['province'],
            'city'=>$params['city'],
            'district'=>$params['district'],
            'address'=>$params['address'],
            'longitude'=>$params['longitude'],
            'latitude'=>$params['latitude'],
            'status'=>$status,
            'create_time'=>time(),
            'update_time'=>time(),
        ];
        $shop_id=0;
        $shop_id =  Db::name('shop')->insert($shop_data);

        //创建账号
        if( Db::name('retailer_account')->where(['monopolyId'=>$params['monopolyId'],'status'=>['<>','0']])->count()<=0 )
        {
            $retailer_data=[
                'password'=>md5(config('admin.login_key').$params['password']),
                'monopolyId'=>$params['monopolyId'],
                'openid'=>$this->openid,
                'status'=>$status,
                'create_time'=>time(),
                'update_time'=>time(),
            ];
            Db::name('retailer_account')->insert($retailer_data);
        }

        if($status==1){
            return json(['status'=>1,'info'=>'注册成功','id'=>$shop_id]);
        }else{
            return json(['status'=>0,'info'=>'审核中，请耐心等待！']);
        }
    }
    //添加指令(零售户)
    public function retailerErrorOper($eq_code,$code)
    {
        $eq_id = Db::name('eq_repair_apply')->where(['code'=>$eq_code])->field('id')->find()['id'];
        $data=[
            'code'=>$code,
            'eq_id'=>$eq_id,
            'status'=>1,
            'create_time'=>time(),
            'update_time'=>time(),
        ];
        Db::name('eq_repair_apply')->insert($data);
        return json(['status'=>1,'info'=>'操作成功！']);
    }
    //生成二维码
    public function getQR($src)
    {
        $src=urldecode($src);
        vendor("phpqrcode.qrlib");
        \QRcode::png($src);
    }
    //加入店铺
    public function joinShop($shop_id,$timestamp,$sign)
    {
        if($timestamp<time()-3600){
//            二维码已过有效期，请重新生成！
            $this->redirect('/mobile/common/prompt?status=2');
        }

        if($sign!=md5(config('join_shop_key').$timestamp))
        {
//            签名验证不通过！
            $this->redirect('/mobile/common/prompt?status=3');
        }

        if(Db::name('manage_account')->where(['openid'=>$this->openid,'shop_id'=>$shop_id,'status'=>1])->count()>0){
            //已经加入成功，不可重复添加！
            $this->redirect('/mobile/common/prompt?status=4');
        }else{
            $data=[
                'openid'=>$this->openid,
                'shop_id'=>$shop_id,
                'status'=>1,
                'create_time'=>time(),
                'update_time'=>time(),
            ];
            Db::name('manage_account')->insert($data);
        }
        //加入成功
        $this->redirect('/mobile/common/prompt?status=1');

    }

}