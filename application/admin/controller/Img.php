<?php
namespace app\admin\controller;
use think\Controller;
use think\Session;
use think\Db;
use think\Request;
use app\common\controller\Base;
use Qiniu;

class Img extends Base
{

    //上传图片
    public function uploadImg()
    {
        $id=input('id'); //图片id
        $imgkey =input('imgkey');//图片路径
//        $imgkey='FoYPelPMVyeFTAIKjzfcmgXtivHM';

        if(empty($imgkey)){
            return json(['status'=>1,'info'=>'参数imgkey错误']);
        }

        $imgurl='';
        if(!empty($imgkey)){
            $imgurl='http://owiqdymo0.bkt.clouddn.com/'.$imgkey;
        }
        $info = $this->_saveqiniuthumnb('','',$imgkey);
        $thumbimg='http://owiqdymo0.bkt.clouddn.com/'.$imgkey.'?imageMogr2/auto-orient/thumbnail/400';

        $cutimg='http://owiqdymo0.bkt.clouddn.com/'.$imgkey.'?imageMogr2/auto-orient/thumbnail/400/gravity/Center/crop/400x400';

       return json(['status'=>0,'info'=>'上传成功','id'=>$id,'url'=>$cutimg]);
    }
//php composer.phar require qiniu/php-sdk
        private function _saveqiniuthumnb($id,$dir,$sourcename)
    {
        $access_key = 'I0bPQDz05WZT8zk-ZLa3WEI8VAZdmX_0sB_5oi5G';//get_sysconfig('qiniu_accesskey');
        $secret_key = 'pEtSzgdDootI61quWrjFJ3WIWaF5HjeHVgG-maZg';//get_sysconfig('qiniu_secretkey');
        $bk = 'uploads';//get_sysconfig('qiniu_bucket');
        $key = date('YmdHis') . rand(100, 999);
        $qiniu_domain = '7xrthl.com1.z0.glb.clouddn.com';//get_sysconfig('qiniu_domain');
        $config = array(
            'secrectKey' => $secret_key, //七牛服务器
            'accessKey' => $access_key, //七牛用户
            'domain' => $qiniu_domain,
            'bucket' => $bk, //空间名称
            'timeout' => 300, //超时时间
            'useHTTPS'=>true
        );

        $auth = new \Qiniu\Auth($access_key, $secret_key);

        //要缩略的文件所在的空间和文件名。
        $key = $sourcename;//'5cj7uv723zv.png';

        //pipeline是使用的队列名称,不设置代表不使用私有队列，使用公有队列。
        $pipeline = 'zonma_piepline';
//        /gravity/Center/crop/400x400
        //要进行缩略的操作。
        $fops = 'imageMogr2/auto-orient/thumbnail/400';//x400!
//        /gravity/Center/crop/400x400
        //可以对缩略后的文件进行使用saveas参数自定义命名，当然也可以不指定文件会默认命名并保存在当前空间
        $dest_key_name = $sourcename.'-thumb-400';
        $saveas_key = \Qiniu\base64_urlSafeEncode($bk.':'.$dest_key_name);//目标Bucket_Name:自定义文件key
        $fops = $fops.'|saveas/'.$saveas_key;

        $pfop = new \Qiniu\Processing\PersistentFop($auth, $config);

        $notifyurl="http://gzh.zonma.net/Api/Test/qiniu";//?id='.$dest_key_name

        $force = false;

        $info = $pfop->execute($bk,$key, $fops,$pipeline,$notifyurl,$force);
        return $info;
    }

    
}