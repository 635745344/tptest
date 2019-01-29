<?php
/**
 * Created by PhpStorm.
 * User: shaw
 * Date: 2018/12/27
 * Time: 11:24
 */

namespace app\api\controller;

use think\Request;
use think\Cache;
use think\Db;
use think\Config;

class Docking extends Base
{
    /**
     * @api                 {post} /app/docking/proving 验证验证码
     * @apiVersion          1.0.0
     * @apiName             proving
     * @apiParam   {String} code      验证码
     * @apiParam   {String} sign      签名
     */
    public function Proving($code='',$sign)
    {
        P(['Proving_API','post_code'=>$code,'post_sign'=>$sign]);
        $key = 'zhongmahui';
        $sign_key = md5($key.$code);

        //签名验证
        if($sign != $sign_key){
            return json(['status'=>0,'msg'=>'签名错误']);
        }

        //code 验证
        if(empty($code))
        {
            return json(['status'=>0,'msg'=>'code不能为空']);
        }else {

            $check  = Db::name('kuaixin_code')->where(['code'=>$code])->find();

            if(!$check){
                return json(['status'=>0,'msg'=>'code不存在']);
            }

            if($check['status']==1){
                return json(['status'=>0,'msg'=>'code无效']);
            }

            //修改code状态
            $arr = array(
                'status'=>1,
                'use_time'=>time(),
                'sign_key'=>$sign,
            );

            Db::name('kuaixin_code')->where(['code'=>$code])->update($arr);
            return json(['status'=>1,'msg'=>'code验证成功']);
        }

    }

    public function get_test_code()
    {
        $code = create_password(12);
        $data = array(
            'code'=>$code,
            'create_time'=>time(),
        );
        Db::name('kuaixin_code')->insert($data);
        $key = 'zhongmahui';
        echo '生成code：'.$code;
    }

}