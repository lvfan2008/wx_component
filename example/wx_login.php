<?php
/**
 * 微信开放平台 网站应用微信登录开发样例
 * @author  lv_fan2008@sina.com
 */

include_once "config.php";
include_once "bootstrap.php";

$appId = "wx972be05f09e5714f"; // 微信开放平台 网站应用appId
$appSecret = "2e1d51a74f0497b9b2a747ccf5809984"; // 微信开放平台 网站应用appSecret

$wxLogin = new WxLogin($appId, $appSecret);
$msg = "";
if ($_POST['act'] == "wx_login") {
    $url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $redirectUrl = $wxLogin->getWxLoginRedirectUrl($url, "state_me");
    header("Location: {$redirectUrl}");
    exit;
} elseif ($_GET['state'] == "state_me") {
    if (isset($_GET['code'])) {
        $authInfo = $wxLogin->getWxLoginTokenInfoFromCodeCb();
        if ($authInfo === false) {
            $msg = "获取Token出错，原因：" . $wxLogin->errMsg;
        } else {
            $userInfo = $wxLogin->getWxLoginUserInfo($authInfo['access_token'], $authInfo['openid']);
            if ($userInfo === false) {
                $msg = "获取用户信息出错，原因：" . $wxLogin->errMsg;
            } else {
                $msg = print_r($userInfo, true);
            }
        }
    } else {
        $msg = "异常出现，code参数未发现！";
    }
}

?>

<html>
<head>
    <title>微信开放平台 网站应用微信登录开发样例</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0,user-scalable=no">
    <style>
        body {
            background: #feefef;
            font-size: 14px;
        }

        p {
            word-break: break-all; /*支持IE，chrome，FF不支持*/
            word-wrap: break-word; /*支持IE，chrome，FF*/
            margin: 0 0 10px 0;
        }

        .panel {
            margin: 0 10px 10px 10px;
            padding: 15px;
            border: 1px solid #999;
            border-radius: 5px;
            background: #fff;
        }
    </style>
</head>

<body>

<div class='panel'>
    <h2>
        微信开放平台 网站应用微信登录开发样例
    </h2>
</div>

<?php
if ($msg) {
    echo "<div class='panel'> <h2>微信登录用户信息：</h2><p>{$msg}</p></div>";
}
?>

<div class="panel">
    <form action="wx_login.php" method="post">
        <input type="hidden" name="act" value="wx_login">
        <input type="submit" value="测试微信登录">
    </form>
</div>


</body>
</html>
