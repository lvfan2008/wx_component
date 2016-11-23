<?php
/**
 * 微信第三方平台授权回调页面
 * @author  lv_fan2008@vpubao.com
 */

include_once "config.php";
include_once dirname(dirname(__FILE__)) . "/src/WxComponentService.class.php";


header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Content-Type: text/html; charset=utf-8');
header('Pragma: no-cache');

echo "GET:" . print_r($_GET, true);

$wxComponentConfig = $GLOBALS['wxComponentConfig'][$_GET['component_appid']];
if (!$wxComponentConfig) die("invalid redirect url!");

$wxComponentService = new WxComponentService($wxComponentConfig, new FileCache($GLOBALS['cacheDir']));
$ret = $wxComponentService->authorizeCallbackProcess($_GET['auth_code'], $_GET['expire_in']);

if ($ret['code'] === 0) {
    echo "授权成功！<br>";
    echo "appAcountInfo:" . print_r($ret['appAcountInfo'], true);
} else {
    echo "授权失败！<br>";
    echo "原因:" . $ret['msg'];
}

