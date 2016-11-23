<?php
include_once "BaseCache.class.php";
include_once "FileCache.class.php";
include_once "WxComponent.class.php";
if (!class_exists("Wechat2")) include_once "3rd/wechat/Wechat2.class.php";

/**
 * 公众号授权服务类
 * @author  lv_fan2008@vpubao.com
 */
class WxComponentService
{

    /**
     * 第三方平台对象
     * @var WxComponent
     */
    protected $wxComponent;

    /**
     * 第三方平台AppId
     * @var string
     */
    protected $wxComponentAppId;

    /**
     * 平台配置
     * @var array 例:array('component_appid'=>,'component_appsecret'=>,'encodingAesKey'=>,'token')
     */
    protected $wxComponentConfig;

    /**
     * 缓存类
     * @var BaseCache
     */
    protected $cache;


    public function __construct($wxComponentConfig, $cache)
    {
        $this->cache = $cache;
        $this->wxComponentAppId = $wxComponentConfig['component_appid'];
        $this->wxComponentConfig = $wxComponentConfig;
    }

    /**
     * 得到第三方对象，配置采用全局配置
     * @return WxComponent
     */
    public function getWxComponent()
    {
        $cfg = $this->wxComponentConfig;
        $cfg['component_verify_ticket'] = $this->getComponentVerifyTicket();
        if (!$this->wxComponent) $this->wxComponent = new WxComponent($cfg['component_appid'],
            $cfg['component_appsecret'], $cfg['component_verify_ticket'],
            $cfg['encodingAesKey'], $cfg['token']);
        return $this->wxComponent;
    }


    /**
     * 得到跳转授权公众号的URL，回调返回时，会有component_appid参数
     * @param $redirectUrl 跳转回来的URL，可以含有参数用于回调识别
     * @return string
     */
    public function getAuthorizeUrl($redirectUrl)
    {
        $linkSymbo = strpos($redirectUrl, "?") ? "&" : "?";
        $redirect_uri = $redirectUrl . $linkSymbo . "component_appid=" . urlencode($this->wxComponentAppId);
        $preAuthCode = $this->getPreAuthCode();
        return $this->getWxComponent()->get_auth_cb_url($preAuthCode, $redirect_uri);
    }

    /**
     * 公众号授权回调处理过程
     * @param $authCode
     * @param $expireIn
     * @return array
     *  错误返回：array('code'=>(!=0),'msg'=>)
     *  成功返回：array('code'=>0,'appAcountInfo'=>$appAccountInfo)
     *  $appAcountInfo 授权的公众号信息，格式如下：{"authorizer_info": {
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
    public function authorizeCallbackProcess($authCode, $expireIn)
    {
        $authName = "wxAppAuthCode" . $this->wxComponentAppId; // 通过authcode换取公众号的接口调用凭据
        $this->cache->setCache($authName, $authCode, $expireIn);
        $componentAccessToken = $this->getComponentAccessTocken();
        $authInfo = $this->getWxComponent()->get_wx_auth_info($componentAccessToken, $authCode);
        if (!$authInfo) {
            return array('code' => $this->getWxComponent()->errCode, 'msg' => $this->getWxComponent()->errMsg);
        }
        $authName = "wxAppAccessToken" . $this->wxComponentAppId . "_" . $authInfo['authorization_info']['authorizer_appid'];
        $this->cache->setCache($authName, $authInfo['authorization_info']['authorizer_access_token'], $authInfo['authorization_info']['expires_in']);

        $authName = "wxAppRefreshToken" . $this->wxComponentAppId . "_" . $authInfo['authorization_info']['authorizer_appid'];
        $this->cache->setCache($authName, $authInfo['authorization_info']['authorizer_refresh_token'], -1);

        $appAccountInfo = $this->getWxComponent()->get_wx_account_info($componentAccessToken, $authInfo['authorization_info']['authorizer_appid']);
        if (!$appAccountInfo) {
            return array('code' => $this->getWxComponent()->errCode, 'msg' => $this->getWxComponent()->errMsg);
        }
        return array('code' => 0, 'appAcountInfo' => $appAccountInfo);
    }


    /**
     * 第三方平台事件接收处理:
     *      1、微信服务器每隔10分钟会向第三方的消息接收地址推送一次component_verify_ticket，用于获取第三方平台接口调用凭据
     *      2、取消授权事件的处理
     * @return array
     * AppId 授权公众号
     * InfoType component_verify_ticket(ticket通知) unauthorized(取消授权通知) authorized(授权成功通知) updateauthorized(授权更新通知)
     * POST数据示例（component_verify_ticket通知）
     * <xml>
     * <AppId> </AppId>
     * <CreateTime>1413192605 </CreateTime>
     * <InfoType>component_verify_ticket</InfoType>
     * <ComponentVerifyTicket> </ComponentVerifyTicket>
     * </xml>
     * POST数据示例（取消授权通知）
     * <xml>
     * <AppId>第三方平台appid</AppId>
     * <CreateTime>1413192760</CreateTime>
     * <InfoType>unauthorized</InfoType>
     * <AuthorizerAppid>公众号appid</AuthorizerAppid>
     * </xml>
     * POST数据示例（授权成功通知）
     * <xml>
     * <AppId>第三方平台appid</AppId>
     * <CreateTime>1413192760</CreateTime>
     * <InfoType>authorized</InfoType>
     * <AuthorizerAppid>公众号appid</AuthorizerAppid>
     * <AuthorizationCode>授权码（code）</AuthorizationCode>
     * <AuthorizationCodeExpiredTime>过期时间</AuthorizationCodeExpiredTime>
     * </xml>
     * POST数据示例（授权更新通知）
     * <xml>
     * <AppId>第三方平台appid</AppId>
     * <CreateTime>1413192760</CreateTime>
     * <InfoType>updateauthorized</InfoType>
     * <AuthorizerAppid>公众号appid</AuthorizerAppid>
     * <AuthorizationCode>授权码（code）</AuthorizationCode>
     * <AuthorizationCodeExpiredTime>过期时间</AuthorizationCodeExpiredTime>
     * </xml>
     */
    public function onComponentEventNotify()
    {
        $ret = $this->getWxComponent()->process_event_notify();
        if (is_array($ret)) {
            switch ($ret['InfoType']) {
                case "component_verify_ticket":
                    $authName = "wxComponentVerifyTicket" . $this->wxComponentAppId;
                    $this->cache->setCache($authName, $ret['ComponentVerifyTicket'], -1);
                    break;
                case "unauthorized":
                    break;
                case "authorized":
                    break;
                case "updateauthorized":
                    break;
            }
        }
        return $ret;
    }

    /**
     * 到授权后的公众号对象，代理处理公众号实现业务操作
     * @param $appId 公众号appId
     * @return Wechat2
     */
    public function getWechat($appId, $authCode = "", $expireIn = 0)
    {
        static $_ins = array();
        if (isset($_ins[$appId])) {
            return $_ins[$appId];
        }
        $cfg = $GLOBALS['wxComponentCfg'][$this->wxComponentAppId];
        $componentAccessToken = $this->getComponentAccessTocken();
        $appAccessToken = $this->getAppAccessToken($appId);
        $Wechat2_options = array(
            'token' => $componentAccessToken,
            'encodingaeskey' => $cfg['encodingAesKey'],
            'appid' => $cfg['component_appid'],
            'appsecret' => $cfg['component_appsecret'],
            'access_token' => $appAccessToken
        );
        $_ins[$appId] = new Wechat2($Wechat2_options);
        return $_ins[$appId];
    }

    /**
     * 得到授权公众号的接口调用凭据
     * @param $appId 授权公众号AppId
     * @return bool|string 接口调用凭据
     */
    public function getAppAccessToken($appId)
    {
        $authName = "wxAppAccessToken" . $this->wxComponentAppId . "_" . $appId;
        $appAccessToken = $this->cache->getCache($authName);
        if ($appAccessToken) return $appAccessToken;
        $componentAccessToken = $this->getComponentAccessTocken();

        $authName = "wxAppRefreshToken" . $this->wxComponentAppId . "_" . $appId;
        $appRefreshToken = $this->cache->getCache($authName);
        $refreshTokenInfo = $this->getWxComponent()->get_wx_access_token($componentAccessToken, $appId, $appRefreshToken);
        if (!$refreshTokenInfo) {
            return false;
        }

        $authName = "wxAppAccessToken" . $this->wxComponentAppId . "_" . $appId;
        $this->cache->setCache($authName, $refreshTokenInfo['authorizer_access_token'], $refreshTokenInfo['expires_in']);

        $authName = "wxAppRefreshToken" . $this->wxComponentAppId . "_" . $appId;
        $this->cache->setCache($authName, $refreshTokenInfo['authorizer_refresh_token'], $refreshTokenInfo['expires_in']);
        return $refreshTokenInfo['authorizer_access_token'];
    }


    /**
     * 得到预授权码
     * @return string
     */
    protected function getPreAuthCode()
    {
        $authName = "wxPreAuthCode" . $this->wxComponentAppId;
        $preAuthCode = $this->cache->getCache($authName);
        if ($preAuthCode) return $preAuthCode;
        $componentAccessToken = $this->getComponentAccessTocken();
        $preAuthCodeArr = $this->getWxComponent()->get_preauth_code($componentAccessToken);
        $this->cache->setCache($authName, $preAuthCodeArr['pre_auth_code'], $preAuthCodeArr['expires_in'] - 10);
        return $preAuthCodeArr['pre_auth_code'];
    }

    /**
     * 得到接口调用凭据
     * @return bool|string
     */
    protected function getComponentAccessTocken()
    {
        $authName = "wxComponentAccessTocken" . $this->wxComponentAppId;
        $componentAccessTocken = $this->cache->getCache($authName);
        if ($componentAccessTocken) return $componentAccessTocken;
        $accessArr = $this->getWxComponent()->get_access_token();
        $this->cache->setCache($authName, $accessArr['component_access_token'], $accessArr['expires_in'] - 10);
        return $accessArr['component_access_token'];
    }

    /**
     * 得到微信服务器定时推过来的component_verify_ticket
     * @return bool|string
     * @throws Exception
     */
    protected function getComponentVerifyTicket()
    {
        $authName = "wxComponentVerifyTicket" . $this->wxComponentAppId;
        $ComponentVerifyTicket = $this->cache->getCache($authName);
        return $ComponentVerifyTicket;
    }

}