<?php

/**
 * 文件缓存类
 * @author  lv_fan2008@vpubao.com
 */
class FileCache extends BaseCache
{
    protected $cacheDir = "";

    public function __construct($cacheDir)
    {
        $this->cacheDir = $cacheDir;
        if (!file_exists($this->cacheDir)) @mkdir($this->cacheDir, 0777, true);
    }


    /**
     * 缓存数据
     * @param $cacheName  缓存名称
     * @param $cacheValue 缓存值
     * @param $expireIn 缓存时间，单位秒 如果为-1则为永不过期
     */
    public function setCache($cacheName, $cacheValue, $expireIn)
    {
        $filePath = $this->cacheDir . $cacheName;
        $arr = ['v' => $cacheValue, 'et' => time() + $expireIn];
        if ($expireIn == -1) $arr['et'] = -1;
        file_put_contents($filePath, json_encode($arr));
    }

    /**
     * 得到缓存值
     * @param $cacheName
     * @return bool|string
     */
    public function getCache($cacheName)
    {
        $filePath = $this->cacheDir . $cacheName;
        if (!file_exists($filePath)) return false;
        $arr = json_decode(file_get_contents($filePath), true);
        if ($arr['et'] == -1 || $arr['et'] > time()) return $arr['v'];
        return false;
    }

    /**
     * 益处缓存
     * @param $cacheName
     */
    public function removeCache($cacheName)
    {
        $filePath = $this->cacheDir . $cacheName;
        if (!file_exists($filePath)) @unlink($filePath);
    }
}