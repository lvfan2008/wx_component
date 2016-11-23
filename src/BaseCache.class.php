<?php

/**
 * 缓存基类
 * @author  lv_fan2008@vpubao.com
 */
abstract class BaseCache
{
    abstract public function setCache($cacheName,$cacheValue,$expireIn);
    abstract public function getCache($cacheName);
    abstract public function removeCache($cacheName);
}