<?php

namespace app\member\controller;
use think\Controller;
use think\Session;
use think\Db;
use think\Request;

class UserInfo extends Controller
{
    const CStatus = 'Status';
    const CStatusOk = 'OK';
    const CStatusInvalidWeChatConfig = 'InvalidWeChatConfig';
    const CStatusOperationDenied = 'OperationDenied';
    const CStatusGrantAccessTokenFailed = 'GrantAccessTokenFailed';
    const CStatusScopeSnsapiUserinfoNotFound = 'ScopeSnsapiUserinfoNotFound';
    const CStatusGetUserInfoFailed = 'GetUserInfoFailed';

    private function SendAjax($Url, $Data = [], $Method = 'GET') {
        $mPostData = http_build_query($Data);

        $mOptions = [
            'header' => 'Content-type:application/x-www-form-urlencoded',
            'content' => $mPostData,
            'timeout' => 60
        ];
        if ($Method == 'POST') {
            $mOptions['method'] = $Method;
        }
        $mContext = stream_context_create(['http' => $mOptions]);
        if ($Method == 'POST') {
            $mResult = file_get_contents($Url, false, $mContext);
        }
        else {
            $mResult = file_get_contents("{$Url}?{$mPostData}", false, $mContext);    
        }
        
        return @json_decode($mResult, true);
    }

    public function index() {
        $mCode = I('get.code', '');
        $mState = I('get.state', '');

        if ($mCode == '') {
            $this->ajaxReturn([self::CStatus => self::CStatusOperationDenied]);
            exit();
        }

        $mWechatConfig = M('WechatConfig')
            ->field('appid,appsecret')
            ->where(['id' => 1])
            ->find();

        if (($mWechatConfig === false) || ($mWechatConfig === null)) {
            $this->ajaxReturn([self::CStatus => self::CStatusInvalidWeChatConfig]);
            exit();
        }
        
        $mResult = $this->SendAjax('https://api.weixin.qq.com/sns/oauth2/access_token', [
            'appid' => $mWechatConfig['appid'],
            'secret' => $mWechatConfig['appsecret'],
            'code' => $mCode,
            'grant_type' => 'authorization_code'
        ]);

        if (isset($mResult['errcode'])) {
            $this->ajaxReturn([
                self::CStatus => self::CStatusGrantAccessTokenFailed,
                'Error' => $mResult
            ]);
            exit();
        }

        $mAccessToken = $mResult['access_token'];
        $mOpenId = $mResult['openid'];
        $mScopes = explode(',', $mResult['scope']);

        if (!in_array('snsapi_userinfo', $mScopes)) {
            $this->ajaxReturn([
                self::CStatus => self::CStatusScopeSnsapiUserinfoNotFound,
                'Scopes' => $mScopes
            ]);
            exit();
        }

        $mResult = $this->SendAjax('https://api.weixin.qq.com/sns/userinfo', [
            'access_token' => $mAccessToken,
            'openid' => $mOpenId,
            'lang' => 'zh_CN'
        ]);

        if (isset($mResult['errcode'])) {
            $this->ajaxReturn([
                self::CStatus => self::CStatusGetUserInfoFailed,
                'Error' => $mResult
            ]);
            exit();
        }

        $this->ajaxReturn([
            self::CStatus => self::CStatusOk,
            'State' => $mState,
            'UserInfo' => $mResult
        ]);
    }
}
