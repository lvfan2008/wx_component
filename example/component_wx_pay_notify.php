<?php
/**
 * 第三方平台时，实现公众号微信支付回调样例
 * @author  lv_fan2008@sina.com
 */

include_once "config.php";
include_once dirname(dirname(__FILE__)) . "/src/WxComponentService.class.php";
include_once dirname(dirname(__FILE__)) . "/src/WxPay.class.php";

log_ex("pay_notify", "notify call enter.");

WxPay::payNotify("payNotifyFunc");

log_ex("pay_notify", "notify call done.");

/**
 * 支付回调
 * @param array $data 回调数据
 * @param string $msg 错误时，可写入错误消息
 * @return bool 支付数据正常处理，返回true，否则false
 */
function payNotifyFunc($data, &$msg)
{
    log_ex("pay_notify", print_r($data, true) . "\nmsg:{$msg}");
    if ($GLOBALS['wxTestPayCfg']['AppId'] == $data['appid']) {
        $ret = WxPay::checkSign($GLOBALS['payCfg'], $data);
        if ($ret) {
            log_ex("pay_notify", "checkSign OK");
            return true;
        } else {
            $msg = "签名失败！";
            log_ex("pay_notify", "checkSign Failed!");
            return false;
        }
    } else {
        log_ex("pay_notify", "not found appId:{$data['appid']} pay config");
        return false;
    }
}
