<?php
/**
 * 第三方平台代公众号发起网页授权
 * @author  lv_fan2008@sina.com
 */

include_once "config.php";
include_once dirname(dirname(__FILE__)) . "/src/WxComponentService.class.php";
$cfg_arr = array_values($GLOBALS['wxComponentConfig']);
$wxComponentConfig = $cfg_arr[0];

$appId = "wxd00b671bf4239248"; // 改为自己授权的认证服务号appId
$wxComponentService = new WxComponentService($wxComponentConfig,new FileCache($GLOBALS['cacheDir']));
if(isset($_GET['code']) && $_GET['state'] == 'fromoauth'){
    log_ex("component_oauth","get:".print_r($_GET,true));
    print_r($_GET);
    $ret = $wxComponentService->getOauthAccessTokenForCode($appId);
    log_ex("component_oauth","getOauthAccessTokenForCode ret:".print_r($ret,true));
    if($ret){
        print_r($ret);
        $user = $wxComponentService->getOauthUserinfo($ret['access_token'],$ret['openid']);
        log_ex("component_oauth","getOauthUserinfo ret:".print_r($user,true));
        print_r($user);
    }
    exit;
}else{
    $appId = "wxd00b671bf4239248";; // 改为自己授权的认证服务号appId
    $url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $url_path = substr($url,0,strrpos($url,"/"));
    $redirectUrl = $url_path."/component_oauth.php";
    $oauth_url = $wxComponentService->getOauthRedirect($appId,$redirectUrl,"fromoauth","snsapi_userinfo");
}

?>

<html>
<head>
    <title></title>
    <meta charset="UTF-8">
</head>
<body>

<p>
    <a href="<?php echo $oauth_url; ?>" target="_blank">测试代公众号发起网页授权</a>
</p>

</body>
</html>
