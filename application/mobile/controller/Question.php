<?php
/**
 * Created by PhpStorm.
 * User: Colin
 * Date: 2018/4/25
 * Time: 16:34
 */

namespace app\mobile\controller;
use think\Controller;
use think\Session;
use think\Db;
use think\Request;
use app\mobile\controller\MobileBase;

class Question  extends MobileBase
{
    protected function _initialize()
    {
        parent::_initialize();
        $this->openid=session('openid');
        $this->user=session('user');
        $this->user_id=session('user')['id'];
    }

    //问卷页
    public function index(){
        $id=input('id');

        $questionnaire=Db::name('questionnaire')->where(['id'=>$id,'status'=>1])->find();

        $question = Db::name('question')->alias('question')
//            ->join('sp_questionnaire questionnaire','questionnaire.id=question.questionnaire_id','left')
            ->field('question.id,question.title,question.type,question.option')
            ->order('question.create_time')
            ->where(['questionnaire_id'=>$id])
            ->select();

        if(!empty($questionnaire) ){
            if( time() < strtotime($questionnaire['begin_time']) ){
                $this->redirect('/mobile/common/prompt2?msg=活动未开始');
            }
            else if( time() >= strtotime($questionnaire['end_time']) ){
                $this->redirect('/mobile/common/prompt2?msg=活动已结束');
            }
        }
        if( Db::name('questionrecord')->where(['questionnaire_id'=>$id,'user_id'=>$this->user_id])->count()>0 ){
//            return json(['status'=>0,'info'=>'本问卷活动每人仅限一次参与机会！']);
            $this->redirect('/mobile/question/resultView?msg=本问卷活动每人仅限一次参与机会！');
        }

        return view('',['question'=>json_encode($question),'title'=>$questionnaire['title']]);
    }
    //活动到期页
    public function promptView()
    {
        return view();
    }
    //活动提示页
    public function resultView($msg='')
    {
        return view('',['msg'=>$msg]);
    }
    //提交问卷
    public function submit()
    {
        $id=input('id'); //问卷id
        $answer=input('answer');
        $answer=json_decode($answer,true);

        if( Db::name('questionrecord')->where(['questionnaire_id'=>$id,'user_id'=>$this->user_id])->count()>0 ){
            return json(['status'=>0,'info'=>'本问卷活动每人仅限一次参与机会！']);
        }

        $data_questionrecord=[
            'questionnaire_id'=>$id,
            'user_id'=>$this->user_id,
            'answer_time'=>time(),
            'create_time'=>time()
        ];
        Db::name('questionrecord')->insert($data_questionrecord);

        foreach ($answer as $k=>$v)
        {
            if($v['option_id']==0){
                $option_id='';
            }else{
                $option_id=$v['option_id'];
            }
            $remark=empty($v['remark'])?'':$v['remark'];
            $data_detailrecord=[
                'question_id'=>$v['id'],
                'user_id'=>$this->user_id,
                'option_id'=>$option_id,
                'remark'=>$remark,
                'create_time'=>time()
            ];

            Db::name('question_detailrecord')->insert($data_detailrecord);
        }

        return json(['status'=>1,'info'=>'提交成功']);
    }

}