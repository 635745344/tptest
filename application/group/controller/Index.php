<?php
namespace app\group\controller;
use think\Db;
use think\Session;
use think\Config;
use app\library\AdminHelper;

class Index extends Check
{
    public function __construct()
    {
        $this->group_id = session('group_id');
        $this->power_id = session('power_id');
        $this->group_user_id = session('group_user.id');
        parent::__construct();
    }

    public function index()
    {
//        print_r(1);exit;
        $this->redirect('/group/admin/index');
    }
}