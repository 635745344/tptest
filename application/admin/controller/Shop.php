<?php
/**
 * Created by PhpStorm.
 * User: Colin
 * Date: 2018/4/10
 * Time: 10:39
 */

namespace app\admin\controller;
use app\common\controller\Base;


class Shop extends Base
{
    public function __construct(){
        parent::__construct();
        $this->M = new \app\common\model\Shop;
    }
    //首页
    public function index(){

        return view();
    }
    //添加视图
    public function addView(){
        return view();
    }

}