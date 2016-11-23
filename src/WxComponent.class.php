<?php
if (!class_exists("WXBizMsgCrypt")) {
    include_once "3rd/aes/wxBizMsgCrypt.php";
}

/**
 *    微信公众号授权服务SDK,
 * @author  lv_fan2008@sina.com
 */
class WxComponent
{
    const API_URL_PREFIX = 'https://api.weixin.qq.com/cgi-bin/component';
    const GET_ACCESS_TOKEN_URL = '/api_component_token';
    const GET_PREAUTHCODE_URL = '/api_create_preauthcode?component_access_token=';
    const GET_WX_AUTH_INFO_URL = '/api_query_auth?component_access_token=';
    const GET_WX_ACCESS_TOKEN_URL = '/api_authorizer_token?component_access_token=';
    const GET_WX_ACCOUNT_INFO_URL = '/api_get_authorizer_info?component_access_token=';
    const GET_WX_OPTION_INFO_URL = '/api_get_authorizer_option?component_access_token=';
    const SET_WX_OPTION_INFO_URL = '/api_set_authorizer_option?component_access_token=';
    const WX_AUTH_CB_URL = 'https://mp.weixin.qq.com/cgi-bin/componentloginpage?';

    //  代公众号发起网页授权相关
    //  在“{网页开发域名}”或者下级域名“$APPID$.{网页开发域名}” 的形式,可以代公众号发起网页授权。
    const OAUTH_PREFIX = 'https://open.weixin.qq.com/connect/oauth2';
    const OAUTH_AUTHORIZE_URL = '/authorize?';
    const API_BASE_URL_PREFIX = 'https://api.weixin.qq.com'; //以下API接口URL需要使用此前缀
    const OAUTH_TOKEN_URL = '/sns/oauth2/component/access_token?';
    const OAUTH_REFRESH_URL = '/sns/oauth2/component/refresh_token?';
    const OAUTH_USERINFO_URL = '/sns/userinfo?';
    const OAUTH_AUTH_URL = '/sns/auth?';

    public $component_appid;
    public $component_appsecret;
    public $component_verify_ticket;
    public $encodingAesKey = "";
    public $token = "";

    public $debug = false;
    public $errCode = 40001;
    public $errMsg = "no access";
    private $_logcallback;

    /**
     * 构造函数，填入配置信息
     * @param $component_appid 平台appId
     * @param $component_appsecret 平台appsecret
     * @param $component_verify_ticket 平台票据，微信服务器定时推送过来
     * @param $encodingAesKey 公众号消息加解密Key
     * @param $token 公众号消息校验Token
     */
    public function __construct($component_appid, $component_appsecret, $component_verify_ticket, $encodingAesKey, $token)
    {
        $this->component_appid = $component_appid;
        $this->component_appsecret = $component_appsecret;
        $this->component_verify_ticket = $component_verify_ticket;
        $this->encodingAesKey = $encodingAesKey;
        $this->token = $token;
    }

    /**
     * 设置新的票据
     * @param $component_verify_ticket
     */
    public function set_component_verify_ticket($component_verify_ticket)
    {
        $this->component_verify_ticket = $component_verify_ticket;
    }

    /**
     * 得到公众号服务授权的URL
     * @param $pre_auth_code
     * @param $redirect_uri
     * @return string
     */
    public function get_auth_cb_url($pre_auth_code, $redirect_uri)
    {
        return self::WX_AUTH_CB_URL . "component_appid=" . urlencode($this->component_appid)
        . "&pre_auth_code=" . urlencode($pre_auth_code) . "&redirect_uri=" . urlencode($redirect_uri);
    }

    /**
     * 获得服务访问授权key
     * @return bool|mixed {
     *    "component_access_token":"61W3mEpU66027wgNZ_MhGHNQDHnFATkDa9-2llqrMBjUwxRSNPbVsMmyD-yq8wZETSoE5NQgecigDrSHkPtIYA",
     *    "expires_in":7200
     *    }
     */
    public function get_access_token()
    {
        $arr = array('component_appid' => $this->component_appid,
            'component_appsecret' => $this->component_appsecret,
            'component_verify_ticket' => $this->component_verify_ticket
        );
        $result = $this->http_post(self::API_URL_PREFIX . self::GET_ACCESS_TOKEN_URL, json_encode($arr));
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
     * 获得预授权码
     * @param $access_token
     * @return bool|mixed{
     *    "pre_auth_code":"Cx_Dk6qiBE0Dmx4EmlT3oRfArPvwSQ-oa3NL_fwHM7VI08r52wazoZX2Rhpz1dEw",
     *    "expires_in":600
     *    }
     */
    public function get_preauth_code($access_token)
    {
        $arr = array('component_appid' => $this->component_appid);
        $result = $this->http_post(self::API_URL_PREFIX . self::GET_PREAUTHCODE_URL . $access_token, json_encode($arr));
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
     * 使用授权码换取公众号的授权信息
     * @param $access_token
     * @param $auth_code
     * @return bool|mixed{ "authorization_info": {
     *    "authorizer_appid": "wxf8b4f85f3a794e77",
     *    "authorizer_access_token": "QXjUqNqfYVH0yBE1iI_7vuN_9gQbpjfK7hYwJ3P7xOa88a89-Aga5x1NMYJyB8G2yKt1KCl0nPC3W9GJzw0Zzq_dBxc8pxIGUNi_bFes0qM",
     *    "expires_in": 7200,
     *    "authorizer_refresh_token": "dTo-YCXPL4llX-u1W1pPpnp8Hgm4wpJtlR6iV0doKdY",
     *    "func_info": [{	"funcscope_category": {	"id": 1	}    },
     *    {"funcscope_category": {"id": 2	}},
     *    {"funcscope_category": {"id": 3}}]
     *    }
     */
    public function get_wx_auth_info($access_token, $auth_code)
    {
        $arr = array('component_appid' => $this->component_appid, 'authorization_code' => $auth_code);
        $result = $this->http_post(self::API_URL_PREFIX . self::GET_WX_AUTH_INFO_URL . $access_token, json_encode($arr));
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
     * 获取（刷新）授权公众号的令牌
     * @param $access_token
     * @param $authorizer_appid
     * @param $authorizer_refresh_token
     * @return bool|mixed {
     *    "authorizer_access_token": "aaUl5s6kAByLwgV0BhXNuIFFUqfrR8vTATsoSHukcIGqJgrc4KmMJ-JlKoC_-NKCLBvuU1cWPv4vDcLN8Z0pn5I45mpATruU0b51hzeT1f8",
     *    "expires_in": 7200,
     *    "authorizer_refresh_token": "BstnRqgTJBXb9N2aJq6L5hzfJwP406tpfahQeLNxX0w"
     *    }
     */
    public function get_wx_access_token($access_token, $authorizer_appid, $authorizer_refresh_token)
    {
        $arr = array('component_appid' => $this->component_appid,
            'authorizer_appid' => $authorizer_appid,
            'authorizer_refresh_token' => $authorizer_refresh_token);
        $result = $this->http_post(self::API_URL_PREFIX . self::GET_WX_ACCESS_TOKEN_URL . $access_token, json_encode($arr));
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
     * 获取授权方的账户信息
     * @param $access_token
     * @param $authorizer_appid
     * @return bool|mixed {"authorizer_info": {
     *    "nick_name": "微信SDK Demo Special",
     *    "head_img": "http://wx.qlogo.cn/mmopen/GPyw0pGicibl5Eda4GmSSbTguhjg9LZjumHmVjybjiaQXnE9XrXEts6ny9Uv4Fk6hOScWRDibq1fI0WOkSaAjaecNTict3n6EjJaC/0",
     *    "service_type_info": { "id": 2 },
     *    "verify_type_info": { "id": 0 },
     *    "user_name":"gh_eb5e3a772040",
     *    "alias":"paytest01"
     *    },
     *    "authorization_info": {
     *    "appid": "wxf8b4f85f3a794e77",
     *    "func_info": [    { "funcscope_category": { "id": 1 } },    { "funcscope_category": { "id": 2 } },    { "funcscope_category": { "id": 3 } }]
     *    }}
     */
    public function get_wx_account_info($access_token, $authorizer_appid)
    {
        $arr = array('component_appid' => $this->component_appid,
            'authorizer_appid' => $authorizer_appid);
        $result = $this->http_post(self::API_URL_PREFIX . self::GET_WX_ACCOUNT_INFO_URL . $access_token, json_encode($arr));
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
     * 获取授权方的选项信息
     * @param $access_token
     * @param $authorizer_appid
     * @param $option_name
     * @return bool|mixed {	"authorizer_appid":"wx7bc5ba58cabd00f4",
     *    "option_name":"voice_recognize",
     *    "option_value":"1"    }
     */
    public function get_wx_option_info($access_token, $authorizer_appid, $option_name)
    {
        $arr = array('component_appid' => $this->component_appid,
            'authorizer_appid' => $authorizer_appid,
            'option_name' => $option_name);
        $result = $this->http_post(self::API_URL_PREFIX . self::GET_WX_OPTION_INFO_URL . $access_token, json_encode($arr));
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
     * 设置授权方的选项信息
     * @param $access_token
     * @param $authorizer_appid
     * @param $option_name
     * @param $option_value
     * @return bool|mixed  {	"errcode":0,	"errmsg":"ok"	}
     */
    public function set_wx_option_info($access_token, $authorizer_appid, $option_name, $option_value)
    {
        $arr = array('component_appid' => $this->component_appid,
            'authorizer_appid' => $authorizer_appid,
            'option_name' => $option_name,
            'option_value' => $option_value);
        $result = $this->http_post(self::API_URL_PREFIX . self::SET_WX_OPTION_INFO_URL . $access_token, json_encode($arr));
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || $json['errcode'] > 0) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return false;
            }
            return $json;
        }
        return false;
    }

    /**
     * 处理component_verify_ticket
     *
     */

    /**
     * @return array|bool
     * <xml>
     *    <AppId> </AppId>
     *    <CreateTime>1413192605 </CreateTime>
     *    <InfoType> </InfoType>
     *    <ComponentVerifyTicket> </ComponentVerifyTicket>
     *    </xml>
     */
    public function process_event_notify()
    {
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $dec_msg = "";
            $postStr = file_get_contents("php://input");
            if (!$postStr) $postStr = $GLOBALS['HTTP_RAW_POST_DATA'];
            file_put_contents(dirname(__FILE__) . "/post_str.txt", "poststr:" . $postStr);
            $pc = new WXBizMsgCrypt($this->token, $this->encodingAesKey, $this->component_appid);
            $ret = $pc->decryptMsg($_GET['msg_signature'], $_GET['timestamp'], $_GET['nonce'], $postStr, $dec_msg);
            if ($ret === 0) {
                $arr = (array)simplexml_load_string($dec_msg, 'SimpleXMLElement', LIBXML_NOCDATA);
                return $arr;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function response_event()
    {
        die("success");
    }

    /**
     * 代公众号发起网页授权 oauth 授权跳转接口
     * @param $appid 公众号appId
     * @param $callback 跳转URL
     * @param string $state 状态信息，最多128字节
     * @param string $scope 授权作用域 snsapi_base或者snsapi_userinfo 或者 snsapi_base,snsapi_userinfo
     * @return string
     */
    public function getOauthRedirect($appid, $callback, $state = '', $scope = 'snsapi_base')
    {
        return self::OAUTH_PREFIX . self::OAUTH_AUTHORIZE_URL . 'appid=' . $appid . '&redirect_uri=' . urlencode($callback) .
        '&response_type=code&scope=' . $scope . '&state=' . $state . '&component_appid=' . urlencode($this->component_appid)
        . '#wechat_redirect';
    }

    /**
     * 代公众号发起网页授权 回调URL时，通过code获取Access Token
     * @return array {access_token,expires_in,refresh_token,openid,scope}
     */
    public function getOauthAccessToken($appid, $component_access_token)
    {
        $code = isset($_GET['code']) ? $_GET['code'] : '';
        if (!$code) return false;
        $result = $this->http_post(self::API_BASE_URL_PREFIX . self::OAUTH_TOKEN_URL . 'appid=' . $appid
            . '&code=' . $code . '&grant_type=authorization_code'
            . '&component_appid=' . urlencode($this->component_appid)
            . '&component_access_token=' . $component_access_token);
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
     * 代公众号发起网页授权  刷新access token并续期
     * @param string $refresh_token
     * @return boolean|mixed
     */
    public function getOauthRefreshToken($appId, $refresh_token, $component_access_token)
    {
        $result = $this->http_post(self::API_BASE_URL_PREFIX . self::OAUTH_REFRESH_URL
            . 'appid=' . $appId . '&grant_type=refresh_token&refresh_token=' . $refresh_token
            . '&component_appid=' . urlencode($this->component_appid)
            . '&component_access_token=' . $component_access_token
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
     * @param string $access_token
     * @param string $openid
     * @return array {openid,nickname,sex,province,city,country,headimgurl,privilege,[unionid]}
     * 注意：unionid字段 只有在用户将公众号绑定到微信开放平台账号后，才会出现。建议调用前用isset()检测一下
     */
    public function getOauthUserinfo($access_token, $openid)
    {
        $result = $this->http_post(self::API_BASE_URL_PREFIX . self::OAUTH_USERINFO_URL . 'access_token=' . $access_token . '&openid=' . $openid);
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
     * 检验授权凭证是否有效
     * @param string $access_token
     * @param string $openid
     * @return boolean 是否有效
     */
    public function getOauthAuth($access_token, $openid)
    {
        $result = $this->http_post(self::API_BASE_URL_PREFIX . self::OAUTH_AUTH_URL . 'access_token=' . $access_token . '&openid=' . $openid);
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return false;
            } else
                if ($json['errcode'] == 0) return true;
        }
        return false;
    }


    private function log($log)
    {
        if ($this->debug && function_exists($this->_logcallback)) {
            if (is_array($log)) $log = print_r($log, true);
            return call_user_func($this->_logcallback, $log);
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
            return $sContent;
        } else {
            return false;
        }
    }

    /**
     * 微信api不支持中文转义的json结构
     * @param array $arr
     */
    static function json_encode($arr)
    {
        $parts = array();
        $is_list = false;
        //Find out if the given array is a numerical array
        $keys = array_keys($arr);
        $max_length = count($arr) - 1;
        if (($keys [0] === 0) && ($keys [$max_length] === $max_length)) { //See if the first key is 0 and last key is length - 1
            $is_list = true;
            for ($i = 0; $i < count($keys); $i++) { //See if each key correspondes to its position
                if ($i != $keys [$i]) { //A key fails at position check.
                    $is_list = false; //It is an associative array.
                    break;
                }
            }
        }
        foreach ($arr as $key => $value) {
            if (is_array($value)) { //Custom handling for arrays
                if ($is_list)
                    $parts [] = self::json_encode($value); /* :RECURSION: */
                else
                    $parts [] = '"' . $key . '":' . self::json_encode($value); /* :RECURSION: */
            } else {
                $str = '';
                if (!$is_list)
                    $str = '"' . $key . '":';
                //Custom handling for multiple data types
                if (!is_string($value) && is_numeric($value) && $value < 2000000000)
                    $str .= $value; //Numbers
                elseif ($value === false)
                    $str .= 'false'; //The booleans
                elseif ($value === true)
                    $str .= 'true';
                else
                    $str .= '"' . addslashes($value) . '"'; //All other things
                // :TODO: Is there any more datatype we should be in the lookout for? (Object?)
                $parts [] = $str;
            }
        }
        $json = implode(',', $parts);
        if ($is_list)
            return '[' . $json . ']'; //Return numerical JSON
        return '{' . $json . '}'; //Return associative JSON
    }
}
