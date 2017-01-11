<?php
/**
 * 授权后代替公众号实现业务
 * @author  lv_fan2008@sina.com
 */

include_once "config.php";
include_once dirname(dirname(__FILE__)) . "/wx_component/WxComponentService.class.php";

$cfg = get_wx_config();
if (!$cfg) {
    die("no access!");
}

$wxComponentConfig = $cfg['component_cfg'];
log_ex('wx_auth_msg', " cfg:" . print_r($cfg, true));

$wxComponentService = new WxComponentService($wxComponentConfig, new FileCache($GLOBALS['cacheDir']));
$appId = $cfg['app_id'];

// 如果为全网发布接入检测的专用测试公众号，转入自动化测试代码
if ($appId == 'wx570bc396a51b8ff8') {
    test_auto_case($wxComponentService, $appId);
    exit;
}

// 正常业务处理
if (!$wxComponentService->isValidAuthorizedAppId($appId)) { // 判断公众号授权是否有效
    log_ex('wx_auth_msg', "appId:{$appId}, not valid authroized appId param:" . print_r($_GET, true));
    die('no access');
}

$weObj = $wxComponentService->getWechat($appId);
if (!$weObj) {
    log_ex('wx_auth_msg', "appId:{$appId}, not valid authroized appId param:" . print_r($_GET, true));
    die('no access');
    return false;
}

$ret = $weObj->valid(true);
if ($ret === false) {
    log_ex('wx_auth_msg', "appId:{$appId}, auth valid failed! param:" . print_r($_GET, true) . " weObj:" . print_r($weObj, true));
    die('no access');
} else if ($ret !== true) {
    log_ex('wx_auth_msg', "appId:{$appId}, only die echostr:" . $ret);
    die($ret);
}
$weObj->getRev();
log_ex('wx_auth_msg', "appId:{$appId} receive data:" . $weObj->getRevData());
$weObj->text("success")->reply('', true); // 简单测试回复success


/**
 * 得到地址对应的配置信息以及appId
 * @return bool
 */
function get_wx_config()
{
    $url = is_https() ? "https://" : "http://" . $_SERVER['HTTP_HOST']
        . ($_SERVER["SERVER_PORT"] != 80 ? ":" . $_SERVER["SERVER_PORT"] : "")
        . $_SERVER['REQUEST_URI'];
    $url_param = parse_url($url);
    $url_path_arr = explode("/", $url_param['path']);
    foreach ($GLOBALS['wxComponentConfig'] as $wxComponentConfig) {
        $cfg_url_param = parse_url($wxComponentConfig['receive_app_event_url']);
        if ($url_param['scheme'] == $cfg_url_param['scheme'] && $url_param['host'] == $cfg_url_param['host']
            && $url_param['port'] == $cfg_url_param['port']
        ) {
            $cfg_path_arr = explode("/", $cfg_url_param['path']);
            $appId = "";
            foreach ($cfg_path_arr as $i => $v) {
                if ($v != '$APPID$' && $v != $url_path_arr[$i]) {
                    $appId = "";
                    break;
                } elseif ($v == '$APPID$') {
                    $appId = $url_path_arr[$i];
                }
            }
            if ($appId) {
                return array('component_cfg' => $wxComponentConfig, 'app_id' => $appId);
            }
        }
    }
    return false;
}

function is_https()
{
    if (!isset($_SERVER['HTTPS']))
        return FALSE;
    if ($_SERVER['HTTPS'] === 1) {  //Apache
        return TRUE;
    } elseif ($_SERVER['HTTPS'] === 'on') { //IIS
        return TRUE;
    } elseif ($_SERVER['SERVER_PORT'] == 443) { //其他
        return TRUE;
    }
    return FALSE;
}

/**
 * 全网发布接入检测自动化测试代码
 * @param WxComponentService $wxComponentService
 * @param string $appId
 */
function test_auto_case(&$wxComponentService, $appId)
{
    $weObj = $wxComponentService->getWechat($appId);
    $ret = $weObj->valid(true);
    if ($ret === false) {
        log_ex('wx_auth_msg', "appId:{$appId}, auth valid failed! param:" . print_r($_GET, true) . " weObj:" . print_r($weObj, true));
        die('no access');
    } else if ($ret !== true) {
        log_ex('wx_auth_msg', "appId:{$appId}, only die echostr:" . $ret);
        die($ret);
    }
    $weObj->getRev();
    log_ex('wx_auth_msg', "appId:{$appId} receive data:" . $weObj->getRevData());

    if ($weObj->getRevType() == Wechat2::MSGTYPE_TEXT) {
        $recv_txt = $weObj->getRevContent();
        if ($recv_txt == 'TESTCOMPONENT_MSG_TYPE_TEXT') {
            log_ex('wx_auth_msg', "test_auto_case send TESTCOMPONENT_MSG_TYPE_TEXT_callback");
            $weObj->text('TESTCOMPONENT_MSG_TYPE_TEXT_callback')->reply('', false);
            exit;
        } else if (preg_match('#QUERY_AUTH_CODE:(.*)#', $recv_txt, $matches)) {
            log_ex('wx_auth_msg', "test_auto_case send QUERY_AUTH_CODE");

            $weObj->text('')->reply('', false);

            $ret = $wxComponentService->authorizeCallbackProcess($matches[1], 10);
            if ($ret['code'] != 0) {
                log_ex('wx_auth_msg', 'test_auto_case authorizeCallbackProcess  failed! ret:' . print_r($ret, true));
                exit;
            }
            $weObj->access_token = $wxComponentService->getAppAccessToken($ret['appAcountInfo']['authorization_info']['authorizer_appid']);
            $res_arr = array('touser' => $weObj->getRevFrom(), 'msgtype' => 'text', 'text' => array('content' => $matches[1] . "_from_api"));

            log_ex('wx_auth_msg', 'test_auto_case sendCustomMessage data:' . print_r($res_arr, true));
            $ret = $weObj->sendCustomMessage($res_arr);
            log_ex('wx_auth_msg', 'test_auto_case sendCustomMessage: ret:' . print_r($weObj, true));
            if (!$ret) {
                log_ex('wx_auth_msg', 'test_auto_case sendCustomMessage failed');
            }
            exit;
        }
    } else if ($weObj->getRevType() == Wechat2::MSGTYPE_EVENT) {
        $data = $weObj->getRevData();
        $ev = $data['Event'];
        log_ex('wx_auth_msg', 'test_auto_case send ' . $ev . "from_callback");
        $weObj->text($ev . "from_callback")->reply('', false);
        log_ex('wx_auth_msg', 'test_auto_case send ' . $ev . "from_callback done.");
    } else {
        die("no access");
    }
    exit;
}

