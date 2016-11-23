<?php
/**
 * 第三方平台发起公众号授权
 * @author  lv_fan2008@vpubao.com
 */

include_once "config.php";
include_once dirname(dirname(__FILE__)) . "/src/WxComponentService.class.php";
$cfg_arr = array_values($GLOBALS['wxComponentConfig']);
$wxComponentConfig = $cfg_arr[0];

$wxComponentService = new WxComponentService($wxComponentConfig,new FileCache($GLOBALS['cacheDir']));
$url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$url_path = substr($url,strrpos($url,"/"));
$redirectUrl = $url_path."/component_auth_cb.php?param1=param1value";
$auth_cb_url = $wxComponentService->getAuthorizeUrl($redirectUrl);
?>

<html>
<head>
    <title></title>
    <meta charset="UTF-8">
</head>
<body>

<p>
    <a href="<?php echo $auth_cb_url; ?>" target="_blank">测试点击授权公众号</a>
</p>

</body>
</html>
