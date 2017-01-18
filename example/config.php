<?php
/**
 * 微信配置文件，请确认当前目录具有写权限。
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

// 用于测试的认证服务号支付配置信息，可以替换为自己的测试的支付配置
$GLOBALS['wxTestPayCfg'] = array(
    'AppId' => 'wx426b3015555a46be', /* 服务号AppId */
    'wx_v3_key' => '8934e7d15453e97507ef794cf7b0519d', /* 商户号支付密钥 */
    'wx_v3_mhcid' => '1900009851', /* 支付商户号 */
    'wx_v3_apiclient_cert_path' => dirname(__FILE__) . "/cert/apiclient_cert.pem",  /* 商户证书路径 */
    'wx_v3_apiclient_key_path' => dirname(__FILE__) . "/cert/apiclient_key.pem",  /* 商户证书私钥路径 */
);

if (file_exists(dirname(__FILE__) . '/online_cfg.php'))
    include_once dirname(__FILE__) . '/online_cfg.php';


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

// error handler function
function error_handler($errno, $errstr, $errfile, $errline)
{
    if (!(error_reporting() & $errno)) {
        // This error code is not included in error_reporting
        return;
    }
    log_ex("error_msg", "errno:{$errno},errstr:{$errstr}, errfile:{$errfile}, errline:{$errline}");
    return true;
}

set_error_handler("error_handler");
