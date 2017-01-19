<?php

/**
 * 网站应用微信登录，统一开放账号
 * @author lv_fan2008@sina.com
 * @date 2017/1/18
 */
class WxLogin
{
    /**
     * 网站应用appId
     * @var string
     */
    public $appId;

    /**
     * 网站应用appSecret
     * @var string
     */
    public $appSecret;

    public $debug = false;

    public $errCode = 40001;

    public $errMsg = "no access";

    public $logCallback;

    const WX_LOGIN_CONNECT_URL = 'https://open.weixin.qq.com/connect/qrconnect?';
    const WX_LOGIN_ACCESS_TOKEN_URL = 'https://api.weixin.qq.com/sns/oauth2/access_token?';
    const WX_LOGIN_REFRESH_TOKEN_URL = 'https://api.weixin.qq.com/sns/oauth2/refresh_token?';
    const WX_LOGIN_USERINFO_URL = 'https://api.weixin.qq.com/sns/userinfo?';

    /**
     * 初始化
     * @param $appId 网站应用appId
     * @param $appSecret 网站应用appSecret
     * @param $logCallback log回调函数，开启debug模式可用
     */
    public function __construct($appId, $appSecret, $logCallback = null)
    {
        $this->appId = $appId;
        $this->appSecret = $appSecret;
        $this->logCallback = $logCallback;
    }

    /**
     * 网站应用微信登录，获取授权登录跳转URL
     * @param string $callback 跳转URL
     * @param string $state 状态信息，最多128字节
     * @return string
     */
    public function getWxLoginRedirectUrl($callback, $state = "")
    {
        return self::WX_LOGIN_CONNECT_URL . 'appid=' . $this->appId . '&redirect_uri=' . urlencode($callback) .
        '&response_type=code&scope=snsapi_login&state=' . $state . '#wechat_redirect';
    }

    /**
     * 网站应用微信登录，打开授权登录跳转URL后，回调URL时，通过code获取Access Token
     * @return bool|array {access_token,expires_in,refresh_token,openid,scope}
     */
    public function getWxLoginTokenInfoFromCodeCb()
    {
        $code = isset($_GET['code']) ? $_GET['code'] : '';
        if (!$code) return false;
        $result = $this->http_post(self::WX_LOGIN_ACCESS_TOKEN_URL . 'appid=' . $this->appId
            . '&secret=' . $this->appSecret . '&code=' . $code . '&grant_type=authorization_code');
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return false;
            }
            return $json;
        }
        return false;
    }

    /**
     * 网站应用微信登录,刷新access token并续期
     * @param string $refreshToken 刷新令牌
     * @return boolean|mixed
     */
    public function getWxLoginRefreshToken($refreshToken)
    {
        $result = $this->http_post(self::WX_LOGIN_REFRESH_TOKEN_URL
            . 'appid=' . $this->appId . '&grant_type=refresh_token&refresh_token=' . $refreshToken
        );
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return false;
            }
            return $json;
        }
        return false;
    }


    /**
     * 获取授权后的用户资料
     * @param string $accessToken
     * @param string $openId
     * @return array {openid,nickname,sex,province,city,country,headimgurl,privilege,[unionid]}
     * 注意：unionid字段 只有在用户将公众号绑定到微信开放平台账号后，才会出现。建议调用前用isset()检测一下
     */
    public function getWxLoginUserInfo($accessToken, $openId)
    {
        $result = $this->http_post(self::WX_LOGIN_USERINFO_URL . 'access_token=' . $accessToken . '&openid=' . $openId);
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return false;
            }
            return $json;
        }
        return false;
    }

    private function log($log)
    {
        if ($this->debug && function_exists($this->logCallback)) {
            if (is_array($log)) $log = print_r($log, true);
            return call_user_func($this->logCallback, $log);
        }
    }

    /**
     * POST 请求
     * @param string $url
     * @param string $param
     * @param boolean $post_file 是否文件上传
     * @return string content
     */
    private function http_post($url, $param = "", $post_file = false)
    {
        $oCurl = curl_init();
        if (stripos($url, "https://") !== FALSE) {
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
        }
        if (is_string($param) || $post_file) {
            $strPOST = $param;
        } else {
            $aPOST = array();
            foreach ($param as $key => $val) {
                $aPOST[] = $key . "=" . urlencode($val);
            }
            $strPOST = join("&", $aPOST);
        }
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
        if ($strPOST != "") {
            curl_setopt($oCurl, CURLOPT_POST, true);
            curl_setopt($oCurl, CURLOPT_POSTFIELDS, $strPOST);
        }
        $sContent = curl_exec($oCurl);
        $aStatus = curl_getinfo($oCurl);
        curl_close($oCurl);
        if (intval($aStatus["http_code"]) == 200) {
            $this->log("wxlogin http_post {$url} param:{$param} recv:" . $sContent);
            return $sContent;
        } else {
            $this->log("wxlogin http_post recv error {$url}, param:{$param} aStatus:" . print_r($aStatus, true));
            return false;
        }
    }
}