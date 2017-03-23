<?php
/**
 * Description
 * @author lixinguo@vpubao.com
 * @date 2017/3/23
 */
include_once "init.php";

if (!isset($_GET['code'])) {
    die("parameters error.");
}

$code = $jsSdkDemo->decryptCardCode($_GET['code']);
echo json_encode($code);