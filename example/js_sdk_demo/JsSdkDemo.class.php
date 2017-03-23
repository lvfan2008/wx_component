<?php

/**
 * Description
 * @author lixinguo@vpubao.com
 * @date 2017/3/23
 */
class JsSdkDemo
{
    /**
     * 开发平台公众号服务
     * @var WxComponentService
     */
    protected $wxComponentService;

    /**
     * 测试的公众号appid
     * @var string
     */
    protected $appId;

    /**
     * 微信支付实例
     * @var WxPay
     */
    protected $wxPay;

    /**
     * 测试卡券ID，这里用的代金券
     */
    const TestCardId = "p8HyHjsx_sWpn4o6eTm17CzDPsbw";

    /**
     * JsSdkDemo constructor.
     * @param WxComponentService $wxComponentService
     * @param string $appId
     * @param WxPay $wxPay
     */
    public function __construct($wxComponentService, $appId, $wxPay)
    {
        $this->wxComponentService = $wxComponentService;
        $this->appId = $appId;
        $this->wxPay = $wxPay;
    }

    /**
     * 得到JSSDK配置的签名包信息
     * @param string $url
     * @return array|bool
     */
    public function getWxConfigJsSignPackage($url = "")
    {
        $url = $url ? $url : $url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        return $this->wxComponentService->getJsSign($this->appId, $url);
    }

    /**
     * JSSDK微信支付
     * @param $notify_url 支付异步通知URL
     * @return bool|string
     */
    public function getWxPaySign($notify_url)
    {
        $callbackUrl = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $openId = $this->wxComponentService->getOauthOpenId($this->appId, $callbackUrl);
        $payInfo = ['body' => '测试商品001', 'out_trade_no' => "T" . time() . rand(1, 999), 'amount' => '0.01'];

        $jsApiParameters = $this->wxPay->getJsApiPayParams($openId, $payInfo['body'], $payInfo['out_trade_no'],
            intval($payInfo['amount'] * 100), $notify_url);

        if ($jsApiParameters === false) {
            $this->errMsg = $this->wxPay->lastErrMsg;
            return false;
        }
        return json_decode($jsApiParameters, true);
    }

    /**
     * 获取添加卡券的签名信息 用于js sdk的wx.addCard
     * @return array|bool
     */
    public function getAddCardInfo()
    {
        $card_id = self::TestCardId; // 可以在卡券后台找一个填写到这里，第一个券ID
        $code = '';
        $openid = '';
        $balance = "";
        $cardExt = $this->wxComponentService->getAddCardExt($this->appId, $card_id, $code, $openid, $balance);
        if (!$cardExt) return false;
        $ret = ['cardId' => $card_id, 'cardExt' => json_encode($cardExt)];
        return $ret;
    }

    /**
     * 获取拉取适用卡券列表的签名包 用于js sdk 的 wx.chooseCard
     * @return array|bool
     */
    public function getChooseCardSign()
    {
        $card_type = 'CASH'; // 选择代金券
        $card_id = '';
        $code = '';
        $location_id = '';
        return $this->wxComponentService->getChooseCardSign($this->appId, $card_type, $card_id, $code, $location_id);
    }

    /**
     * 解密卡券的密码
     * @param $code
     * @return array|bool
     */
    public function decryptCardCode($code)
    {
        $weObj = $this->wxComponentService->getWechat($this->appId);
        $ret = $weObj->decryptCardCode($code);
        if ($ret == false) {
            $ret = ['errcode' => $weObj->errCode, 'errmsg' => $weObj->errMsg];
        }
        return $ret;
    }

}