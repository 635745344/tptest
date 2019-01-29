<?php
namespace app\admin\controller;

use app\common\controller\Base ;
use think\Request;
use think\Db;

class Advertisement extends Base
{
    public function __construct(){
        parent::__construct();
        $this->M = new \app\common\model\Advertisement;
    }
    //主页
    public function index()
    {
        $data=Db::name('Advertisement')->field('id,img_info,stop_time,status')->find();
        $group_list = Db::name('equipment_group')->where(['status'=>1])->field('id,group_name')->select();
        return view('',['data'=>$data,'group_list'=>json_encode($group_list)]);
    }
    //修改信息
    public function editInfo()
    {
        $params=input('post.');
        if(empty($params['id'])) //不存在添加
        {
            $result=Db::name('Advertisement')->insert($params);
        }
        else
        {
            //存在修改
            $result =Db::name('Advertisement')->where(['id'=>$params['id']])->update($params);
        }
        //推送广告
        $imgs=[];

        foreach (json_decode($params['img_info'],true)  as $k=>$v ){
            if($v['status']==1 && !empty($v['url']))
            {
                $imgs[]=$v['url'];
            }
        }
        
        $content=json_encode( ['type'=>2,'imgs'=>implode(',',$imgs),'stop_time'=>$params['stop_time']*1000 ]);

        $chuyanji = new \app\common\service\ChuyanjiService();
        $result = $chuyanji->pushImgInfo($content);
        if($result){
            return json(['status'=>1,'info'=>'修改成功']);
        }else{
            return json(['status'=>0,'info'=>'推送失败']);
        }

    }
    //上传图片
    public function upload()
    {
        $request = Request::instance();
        $file = request()->file('image');
//        'size'=>20480, ->validate(['ext'=>'jpg,jpeg,png'])
        $info = $file->move( ROOT_PATH .'public/upload/advertisement' ,true,false);
        if($info){
            return r(1,'',['data'=>$request->domain().'/upload/advertisement/'.str_replace('\\','/',$info->getSaveName())]);
        }else{
            return r(0,$file->getError());
        }
    }

//    //图片修改后推送
//    public function pushImgInfo()
//    {s
//        vendor('JPush.autoload');
//        $client = new \JPush\Client('510c98aca2536c9f07772e04', '0b9892c0b65c60a2a569b03d');
//        var_dump( $client->push()->setPlatform('all')->addAllAudience()->setNotificationAlert('Hello, JPush')->send());exit;
//    }

}