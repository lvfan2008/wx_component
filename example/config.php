<?php
/**
 * 微信配置文件
 * @author  lv_fan2008@sina.com
 */
error_reporting(E_ERROR | E_WARNING | E_PARSE);
date_default_timezone_set('Asia/Shanghai');

/**
 * 微信公众号第三方平台配置信息
 */
$GLOBALS['wxComponentConfig'] = [
    'wxxxxxxxxxxxxxxxxx'/* 平台AppId */ => [
        'component_appid' => 'wxxxxxxxxxxxxxxxxx', /* 平台AppId */
        'component_appsecret' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx', /* 平台AppSecret */
        'encodingAesKey' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx', /* 平台授权后公众号消息加解密Key */
        'token' => 'xxxxxxxxxxxxx', /* 平台公众号消息校验Token */
        'receive_component_event_url' => 'http://www.xxx.com/example/component_event.php', /* 平台授权公众号消息与事件接收URL */
        'receive_app_event_url' => 'http://www.xxx.com/example/appevent/$APPID$' /* 平台授权事件接收URL,需要rewrite到component_app_event.php */
    ],
];
$GLOBALS['cacheDir'] = dirname(__FILE__) . "/cache/";

/**
 * 当前目录的cache目录记录日志
 * @param $filename
 * @param $msg
 */
function log_ex($filename, $msg)
{
    $file_path = dirname(__FILE__) . "/cache/";
    if (!file_exists($file_path)) @mkdir($file_path);
    $file_path .= $filename;
    file_put_contents($file_path, date("Y-m-d H:i:s") . "\t" . $msg . "\n", FILE_APPEND);
}
