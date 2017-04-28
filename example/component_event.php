<?php
/**
 * 第三方平台事件通知
 * @author  lv_fan2008@sina.com
 */

include_once "config.php";
include_once "bootstrap.php";

$wxComponentConfig = get_wx_component_config();
if (!$wxComponentConfig) {
    log_ex("component_event", "not found wxComponentConfig");
    die("success");
}
$wxComponentService = new WxComponentService($wxComponentConfig,new FileCache($GLOBALS['cacheDir']));
$ret = $wxComponentService->onComponentEventNotify();
log_ex("component_event", print_r($ret, true));

/**
 * 得到当前回调地址对应的配置信息
 * @return bool
 */
function get_wx_component_config()
{
    $url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    log_ex("component_event", "url:" . $url);
    foreach ($GLOBALS['wxComponentConfig'] as $wxComponentConfig) {
        log_ex("component_event", "cfg_url:" . $wxComponentConfig['receive_component_event_url']);
        if (strpos($url, $wxComponentConfig['receive_component_event_url']) !== false)
            return $wxComponentConfig;
    }
    return false;
}


