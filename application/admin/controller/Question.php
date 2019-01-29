<?php
/**
 * Created by PhpStorm.
 * User: Colin
 * Date: 2018/4/25
 * Time: 17:57
 */

namespace app\admin\controller;
use think\Controller;
use think\Session;
use think\Db;
use think\Request;
use app\common\controller\Base;

class Question extends Base
{
    //问卷列表页面
    public function index()
    {
        return view();
    }
    //添加问卷页面
    public function addView()
    {
        $id=input('id');

        $title='asd';
        $begin_time='';
        $end_time='';
        $action_id='';
        $good_id = '';
        $question=[];
        if(!empty($id)){
            $questionnaire = Db::name('questionnaire')->where(['id'=>$id])->find();

            $title=$questionnaire['title'];
            $begin_time=$questionnaire['begin_time'];
            $end_time=$questionnaire['end_time'];
            $action_id=$questionnaire['action_id'];
            $good_id=$questionnaire['goods_id'];

            $question=Db::name('question')->where(['questionnaire_id'=>$id])->field('id,title,type,option')->select();
        }
        $good_list = Db::name('goods')->where(['status'=>1])->field('id,name')->select();
        $action_list=Db::name('action')->where(['status'=>1,'max_questionnaire_count'=>['neq',0]])->field('id,name,max_questionnaire_count')->select();
        foreach ($action_list as $k=>$v)
        {
            if($v['max_questionnaire_count']!='-1'){
                if(Db::name('questionnaire')->where(['action_id'=>$v['id']])->count()>=$v['max_questionnaire_count'])
                {
                    unset($action_list[$k]);
                }
            }
        }

        return view('',['action_list'=>json_encode($action_list),'title'=>$title,'begin_time'=>$begin_time,
            'end_time'=>$end_time,'action_id'=>$action_id,'good_id'=>$good_id,'question'=>json_encode($question),'good_list'=>json_encode($good_list)]);
    }
    //统计页面
    public function statisticsView()
    {
        return view();
    }
    //问卷列表
    public function lists($page=1,$limit=10)
    {
        $data = Db::name('questionnaire')
            ->field('id,title,status,begin_time,end_time,FROM_UNIXTIME(create_time) create_time')
            ->order('create_time desc')
            ->page($page,$limit)
            ->select();

        $count=Db::name('questionnaire')->count();
        return json(['status'=>1,'info'=>'','data'=>$data,'count'=>$count]);
    }
    //添加问卷
    public function add()
    {
        $params=input('post.');
        if( isset($params['title']) ){
            if(trim($params['title'])==''){
                return json(['status'=>0,'info'=>'标题不能为空！']);
            }
        }else{
            return json(['status'=>0,'info'=>'标题不能为空！']);
        }
        if(empty($params['action_id'])){
            return json(['status'=>0,'info'=>'请选择活动！']);
        }
        if(empty($params['begin_time'])){
            return json(['status'=>0,'info'=>'请选择开始时间！']);
        }
        if(empty($params['end_time'])){
            return json(['status'=>0,'info'=>'请选择结束时间时间！']);
        }

        $max_questionnaire_count=Db::name('action')->where(['id'=>$params['action_id']])->find()['max_questionnaire_count'];
        if( $max_questionnaire_count!=-1 && Db::name('questionnaire')->where(['action_id'=>$params['action_id'],'status'=>1])->count()>=$max_questionnaire_count){
            return json(['status'=>0,'info'=>'该活动最多发布'.$max_questionnaire_count.'份问卷！']);
        }
        $data=[
            'title'=>$params['title'],
            'action_id'=>$params['action_id'],
            'begin_time'=>$params['begin_time'],
            'end_time'=>$params['end_time'],
            'goods_id'=>@$params['good_id'],
            'status'=>0,
            'update_time'=>time(),
            'create_time'=>time(),
        ];
        $questionnaire_id =Db::name('questionnaire')->insertGetId($data);

        $question=json_decode($params['question'],true);

        foreach ($question as $k=>$v)
        {
            unset($question[$k]['id']);

            if(!empty($question[$k]['option'])){
                $question[$k]['option']=json_encode($v['option']);
            }else{
                $question[$k]['option']='';
            }
            $question[$k]['type']=$v['type'];
            $question[$k]['questionnaire_id']=$questionnaire_id;
            $question[$k]['create_time']=time();
            Db::name('question')->insert($question[$k]);
        }
        return json(['status'=>1,'info'=>'添加成功']);
    }
    //修改问卷
    public function edit()
    {
        $params=input('post.');
        $data=[
            'title'=>$params['title'],
            'action_id'=>$params['action_id'],
            'goods_id'=>@$params['good_id'],
            'begin_time'=>$params['begin_time'],
            'end_time'=>$params['end_time'],
            'update_time'=>time(),
        ];

        if(Db::name('questionnaire')->where(['id'=>$params['id'],'status'=>1])->count()>0){
            return json(['status'=>1,'info'=>'已发布文章不能修改']);
        }

        Db::name('questionnaire')->where(['id'=>$params['id']])->update($data);

        Db::name('question')->where(['questionnaire_id'=>$params['id']])->delete();

        $question=json_decode($params['question'],true);
        foreach ($question as $k=>$v)
        {
            unset($question[$k]['id']);
            
            if(!empty($question[$k]['option'])){
                $question[$k]['option']=json_encode($v['option']);
            }else{
                $question[$k]['option']='';
            }
            $question[$k]['type']=$v['type'];
            $question[$k]['questionnaire_id']=$params['id'];
            $question[$k]['create_time']=time();
            Db::name('question')->insert($question[$k]);
        }
        return json(['status'=>1,'info'=>'修改成功']);
    }

    //发布问卷
    public function release($id)
    {
        Db::name('questionnaire')->where(['id'=>$id])->update(['status'=>1,'update_time'=>time()]);
        return json(['status'=>1,'info'=>'发布成功']);
    }

    //删除问卷
    public function del($id)
    {
        if(Db::name('questionnaire')->where(['id'=>$id,'status'=>1])->count()>0){
            return json(['status'=>1,'info'=>'已发布文章不能删除']);
        }
        Db::name('questionnaire')->where(['id'=>$id])->delete();
        return json(['status'=>1,'info'=>'删除成功']);
    }

    //统计
    public function statistics()
    {
        $id=input('id');
        $questionnaire=Db::name('questionnaire')->where(['id'=>$id])->find();
        $question=Db::name('question')->where(['questionnaire_id'=>$id])
                ->order('create_time')
                ->field('id,title,type,option')
                ->select();

        $data=[];
        foreach ($question as $k=>$v)
        {
            if($v['type']==1 || $v['type']==2) //1:单选 2:多选
            {
                $option=json_decode($v['option'],true);
                $statistics = Db::name('question_detailrecord')
                    ->where(['question_id'=>$v['id'],'option_id'=>['<>',''] ])
                    ->group('option_id')
                    ->field('option_id,count(1) count')
                    ->select();

                foreach ($option as $k_option=>$v_option)
                {
                    $option[$k_option]['count']=0;
                }
                foreach ($statistics as $k_statistics=>$v_statistics)
                {
                    foreach (explode(',',$v_statistics['option_id']) as $k_option_id=>$v_option_id)
                    {
                        foreach ($option as $k_option=>$v_option)
                        {
                            if($v_option['id']==$v_option_id){
                                $option[$k_option]['count'] += $v_statistics['count'];
                            }
                        }
                    }
                }
                $data[]=['id'=>$v['id'],'title'=>$v['title'],'type'=>$v['type'],'option'=>$option];
            }
            else if($v['type']==3 || $v['type']==4) //3:文本 4:地区
            {
                $statistics = Db::name('question_detailrecord')
                    ->where(['question_id'=>$v['id'],'option_id'=>''])
                    ->count();
                $data[]=['id'=>$v['id'],'title'=>$v['title'],'type'=>$v['type'],'count'=>$statistics];
            }
        }

        return json(['status'=>1,'info'=>'','title'=>$questionnaire['title'],'data'=>$data]);
    }
}