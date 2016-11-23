<?php
/**
 * 微信配置文件
 * @author  lv_fan2008@vpubao.com
 */
error_reporting(E_ERROR | E_WARNING | E_PARSE);
date_default_timezone_set('Asia/Shanghai');

$GLOBALS['wxComponentConfig'] = [
    'wxxxxxxxxxxxxxxxxx' => [
        'component_appid' => 'wxxxxxxxxxxxxxxxxx',
        'component_appsecret' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
        'encodingAesKey' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
        'token' => 'xxxxxxxxxxxxx',
        'receive_component_event_url' => 'xxxxxxxxxxxxx',
        'receive_app_event_url' => 'xxxxxxxxxxxxx'
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
