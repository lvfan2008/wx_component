<?php
/**
 * 启动加载器
 * @author lv_fan2008@sina.com
 * @date 2017/4/26
 */

if ($GLOBALS['is_composer_autoload']) {
    // 当使用composer安装的时候，请打开下面一行注释，目录根据实际情况修改
    require __DIR__ . '/../vendor/autoload.php';
} else {
    // 当使用相对目录加载的时候，请打开下面注释，目录根据实际情况修改
    include_once __DIR__ . "/../src/WxComponentService.class.php";
    include_once __DIR__ . "/../src/WxPay.class.php";
}


