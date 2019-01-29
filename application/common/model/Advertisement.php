<?php
/**
 * Created by PhpStorm.
 * User: Colin
 * Date: 2018/4/11
 * Time: 11:25
 */

namespace app\common\model;
use think\Model;
use think\Request;
use think\Response;
use think\Session;
use think\Db;
use app\library\AdminHelper;
use think\Config;


class Advertisement  extends Model
{
    protected $autoWriteTimestamp = true;
    protected $updateTime = false;


}