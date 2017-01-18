<?php
include_once "3rd/WxpayAPI_php_v3/lib/WxPay.Api.php";

/**
 * Description
 * @author lixinguo@vpubao.com
 * @date 2017/1/16
 */
class WxPayNotifyCb extends WxPayNotify
{
    public $cbFunc;

    public function __construct($cbFunc)
    {
        $this->cbFunc = $cbFunc;
    }

    public function NotifyProcess($data, &$msg)
    {
        return call_user_func($this->cbFunc, $data, $msg);
    }
}

class WxPay
{
    public $lastErrMsg = "";

    /**
     * 初始化配置
     * @param $cfg
     */
    public function __construct($cfg)
    {
        WxPayConfig::setConfig($cfg);
    }

    /**
     * 被扫支付，即刷卡支付
     * @param $auth_code 扫码串
     * @param $out_trade_no 订单号
     * @param $body 商品描述
     * @param $amount 金额 单位为 分
     * @return bool|array
     */
    public function microPay($auth_code, $out_trade_no, $body, $amount)
    {
        $input = new WxPayMicroPay();
        $input->SetAuth_code($auth_code);
        $input->SetBody($body);
        $input->SetTotal_fee($amount);
        $input->SetOut_trade_no($out_trade_no);
        try {
            return $this->submitMicroPay($input);
        } catch (Exception $e) {
            $this->lastErrMsg = $e->getMessage();
            return false;
        }
    }

    /**
     * 生成扫描支付URL,模式一
     * 流程：
     * 1、组装包含支付信息的url，生成二维码
     * 2、用户扫描二维码，进行支付
     * 3、确定支付之后，微信服务器会回调预先配置的回调地址，在【微信开放平台-微信支付-支付配置】中进行配置
     * 4、在接到回调通知之后，用户进行统一下单支付，并返回支付信息以完成支付
     * 5、支付完成之后，微信服务器会通知支付成功
     * 6、在支付成功通知中需要查单确认是否真正支付成功（见：notify.php）
     * @param $productId
     * @return bool|string
     */
    public function getPrePayUrl($productId)
    {
        $biz = new WxPayBizPayUrl();
        $biz->SetProduct_id($productId);
        try {
            $values = WxpayApi::bizpayurl($biz);
            $url = "weixin://wxpay/bizpayurl?" . WxpayApi::ToUrlParams($values);
            return $url;
        } catch (Exception $e) {
            $this->lastErrMsg = $e->getMessage();
            return false;
        }
    }


    /**
     * 生成直接支付url，支付url有效期为2小时,模式二
     * 流程：
     * 1、调用统一下单，取得code_url，生成二维码
     * 2、用户扫描二维码，进行支付
     * 3、支付完成之后，微信服务器会通知支付成功
     * 4、在支付成功通知中需要查单确认是否真正支付成功
     * @param string $productId 商品ID 32位
     * @param string $body 商品描述 128位
     * @param string $out_trade_no 商户订单号 32位
     * @param int $amount 金额，单位分
     * @param string $notifyUrl 通知回调URL 256位
     * @param string $attach 附加数据 127位
     * @param string $limit_pay 上传此参数no_credit--可限制用户不能使用信用卡支付
     * @param string $timeStart 订单生成时间，格式为yyyyMMddHHmmss
     * @param string $timeEnd 订单失效时间，格式为yyyyMMddHHmmss
     * @param string $goodsTag 商品标记
     * @return bool|string
     */
    public function getNativePayUrl2($productId, $body, $out_trade_no, $amount, $notifyUrl,
                                     $attach = null, $limit_pay = null, $timeStart = null, $timeEnd = null, $goodsTag = null)
    {
        $input = new WxPayUnifiedOrder();
        $input->SetProduct_id($productId);
        $input->SetBody($body);
        $input->SetOut_trade_no($out_trade_no);
        $input->SetTotal_fee($amount);
        $input->SetTrade_type("NATIVE");
        $input->SetNotify_url($notifyUrl);
        if ($attach) $input->SetAttach($attach);
        if ($limit_pay) $input->SetLimitPay($limit_pay);
        if ($timeStart) $input->SetTime_start($timeStart);
        if ($timeEnd) $input->SetTime_expire($timeEnd);
        if ($goodsTag) $input->SetGoods_tag($goodsTag);
        try {
            $result = WxPayApi::unifiedOrder($input);
            if ($result["code_url"] == "") {
                $this->lastErrMsg = "result:" . print_r($result, true);
                return false;
            } else {
                return $result["code_url"];
            }
        } catch (Exception $e) {
            $this->lastErrMsg = $e->getMessage();
            return false;
        }
    }

    /**
     * 生成JSAPI参数，用于JSAPI支付
     * 流程：
     * 1、调用统一下单，取得code_url，生成二维码
     * 2、用户扫描二维码，进行支付
     * 3、支付完成之后，微信服务器会通知支付成功
     * 4、在支付成功通知中需要查单确认是否真正支付成功
     * @param string $openId 微信openid，可以通过wechat获取
     * @param string $body 商品描述 128位
     * @param string $out_trade_no 商户订单号 32位
     * @param int $amount 金额，单位分
     * @param string $notifyUrl 通知回调URL 256位
     * @param string $attach 附加数据 127位
     * @param string $limit_pay 上传此参数no_credit--可限制用户不能使用信用卡支付
     * @param string $timeStart 订单生成时间，格式为yyyyMMddHHmmss
     * @param string $timeEnd 订单失效时间，格式为yyyyMMddHHmmss
     * @param string $goodsTag 商品标记
     * @return bool|string
     */
    public function getJsApiPayParams($openId, $body, $out_trade_no, $amount, $notifyUrl,
                                      $attach = null, $limit_pay = null, $timeStart = null,
                                      $timeEnd = null, $goodsTag = null, $productId = null)
    {
        $input = new WxPayUnifiedOrder();
        $input->SetProduct_id($productId);
        $input->SetBody($body);
        $input->SetOut_trade_no($out_trade_no);
        $input->SetTotal_fee($amount);
        $input->SetTrade_type("JSAPI");
        $input->SetNotify_url($notifyUrl);
        $input->SetOpenid($openId);
        if ($attach) $input->SetAttach($attach);
        if ($limit_pay) $input->SetLimitPay($limit_pay);
        if ($timeStart) $input->SetTime_start($timeStart);
        if ($timeEnd) $input->SetTime_expire($timeEnd);
        if ($goodsTag) $input->SetGoods_tag($goodsTag);
        try {
            $order = WxPayApi::unifiedOrder($input);
            $jsApi = new WxPayJsApiPay();
            $jsApi->SetAppid($order["appid"]);
            $timeStamp = time();
            $jsApi->SetTimeStamp("$timeStamp");
            $jsApi->SetNonceStr(WxPayApi::getNonceStr());
            $jsApi->SetPackage("prepay_id=" . $order['prepay_id']);
            $jsApi->SetSignType("MD5");
            $jsApi->SetPaySign($jsApi->MakeSign());
            $parameters = json_encode($jsApi->GetValues());
            return $parameters;
        } catch (Exception $e) {
            $this->lastErrMsg = $e->getMessage();
            return false;
        }
    }

    /**
     * 获取微信地址本js参数
     * @param $appId
     * @param $url 当前URL
     * @param $accessToken oauth后获取的令牌
     * @return string
     */
    public function getJsEditAddressParams($appId, $url, $accessToken)
    {
        $data = array();
        $data["appid"] = $appId;
        $data["url"] = $url;
        $time = time();
        $data["timestamp"] = "$time";
        $data["noncestr"] = WxPayApi::getNonceStr();
        $data["accesstoken"] = $accessToken;
        ksort($data);
        $params = WxPayApi::ToUrlParams($data);
        $addrSign = sha1($params);

        $afterData = array(
            "addrSign" => $addrSign,
            "signType" => "sha1",
            "scope" => "jsapi_address",
            "appId" => $appId,
            "timeStamp" => $data["timestamp"],
            "nonceStr" => $data["noncestr"]
        );
        $parameters = json_encode($afterData);
        return $parameters;
    }

    /**
     * 获取下载对账单
     * @param $billDate 下载对账单的日期，格式：20140603
     * @param $billType 账单类型 ALL，返回当日所有订单信息，默认值 SUCCESS，返回当日成功支付的订单  REFUND，返回当日退款订单
     * @param $tarType 压缩账单 非必传参数，固定值：GZIP，返回格式为.gzip的压缩包账单。不传则默认为数据流形式。
     * @return bool|array
     */
    public function getBill($billDate, $billType, $tarType = null)
    {
        $input = new WxPayDownloadBill();
        $input->SetBill_date($billDate);
        $input->SetBill_type($billType);
        if ($tarType) $input->SetBill_tar_type($tarType);
        try {
            return WxPayApi::downloadBill($input);
        } catch (Exception $e) {
            $this->lastErrMsg = $e->getMessage();
            return false;
        }
    }

    /**
     * 查询订单
     * @param string $transactionIdOrOutTradeNo 微信的订单号或者商户订单号，建议优先使用微信的订单号
     * @param int $type 单号类型 0 微信的订单号,1 商户订单号
     * @return bool|array
     */
    public function queryOrder($transactionIdOrOutTradeNo, $type = 0)
    {
        $queryOrderInput = new WxPayOrderQuery();
        if ($type == 0)
            $queryOrderInput->SetTransaction_id($transactionIdOrOutTradeNo);
        else
            $queryOrderInput->SetOut_trade_no($transactionIdOrOutTradeNo);
        try {
            $result = WxPayApi::orderQuery($queryOrderInput);
            return $result;
        } catch (Exception $e) {
            $this->lastErrMsg = $e->getMessage();
            return false;
        }
    }

    /**
     * 微信退款
     * @param $totalFee 订单总金额
     * @param $refundFee 退款金额
     * @param $refundNo  退款号
     * @param $transactionIdOrOutTradeNo 微信的订单号或者商户订单号
     * @param int $type 单号类型 0 微信的订单号,1 商户订单号
     * @return bool|array
     */
    public function refundOrder($totalFee, $refundFee, $refundNo, $transactionIdOrOutTradeNo, $type = 0)
    {
        $input = new WxPayRefund();
        if ($type == 0)
            $input->SetTransaction_id($transactionIdOrOutTradeNo);
        else
            $input->SetOut_trade_no($transactionIdOrOutTradeNo);
        $input->SetTotal_fee($totalFee);
        $input->SetRefund_fee($refundFee);
        $input->SetOut_refund_no($refundNo);
        $input->SetOp_user_id(WxPayConfig::$MCHID);
        try {
            $result = WxPayApi::refund($input);
            return $result;
        } catch (Exception $e) {
            $this->lastErrMsg = $e->getMessage();
            return false;
        }
    }

    /**
     * 退款查询
     * @param $refundQueryKey 退款查询标示，可以是退款单号refund_id、out_refund_no、out_trade_no、transaction_id其中一个。
     * @param int $type 退款查询标示类型 0 refund_id 1 out_refund_no 2transaction_id 3 out_trade_no
     * @return bool|array
     */
    public function refundOrderQuery($refundQueryKey, $type = 0)
    {
        $input = new WxPayRefundQuery();
        switch ($type) {
            case 0: {
                $input->SetRefund_id($refundQueryKey);
                break;
            }
            case 1: {
                $input->SetOut_refund_no($refundQueryKey);
                break;
            }
            case 2: {
                $input->SetTransaction_id($refundQueryKey);
                break;
            }
            case 3: {
                $input->SetOut_trade_no($refundQueryKey);
                break;
            }
            default:
                $this->lastErrMsg = "退款查询接口中，标示类型不正确！";
                return false;
        }
        try {
            $result = WxPayApi::refundQuery($input);
            return $result;
        } catch (Exception $e) {
            $this->lastErrMsg = $e->getMessage();
            return false;
        }

    }

    /**
     * 实现支付回调
     * @param $callBackFunc 回调函数 实现 $callBackFunc($data,&$msg) return bool
     * $data格式：<xml>
     * <appid><![CDATA[wx2421b1c4370ec43b]]></appid>
     * <attach><![CDATA[支付测试]]></attach>
     * <bank_type><![CDATA[CFT]]></bank_type>
     * <fee_type><![CDATA[CNY]]></fee_type>
     * <is_subscribe><![CDATA[Y]]></is_subscribe>
     * <mch_id><![CDATA[10000100]]></mch_id>
     * <nonce_str><![CDATA[5d2b6c2a8db53831f7eda20af46e531c]]></nonce_str>
     * <openid><![CDATA[oUpF8uMEb4qRXf22hE3X68TekukE]]></openid>
     * <out_trade_no><![CDATA[1409811653]]></out_trade_no>
     * <result_code><![CDATA[SUCCESS]]></result_code>
     * <return_code><![CDATA[SUCCESS]]></return_code>
     * <sign><![CDATA[B552ED6B279343CB493C5DD0D78AB241]]></sign>
     * <sub_mch_id><![CDATA[10000100]]></sub_mch_id>
     * <time_end><![CDATA[20140903131540]]></time_end>
     * <total_fee>1</total_fee>
     * <trade_type><![CDATA[JSAPI]]></trade_type>
     * <transaction_id><![CDATA[1004400740201409030005092168]]></transaction_id>
     * </xml>
     */
    public static function payNotify($callBackFunc)
    {
        $WxPayNotifyCb = new WxPayNotifyCb($callBackFunc);
        $WxPayNotifyCb->Handle(false);
    }

    /**
     * 检查支付回调数据签名是否正确
     * @param $cfg 支付配置是否正确
     * @param $data
     * @return bool
     */
    public static function checkSign($cfg, $data)
    {
        try {
            WxPayConfig::setConfig($cfg);
            $obj = new WxPayResults();
            $obj->FromArray($data);
            return $obj->CheckSign();
        } catch (Exception $e) {
            return false;
        }
    }


    /**
     * 提交刷卡支付，并且确认结果，接口比较慢
     * @param WxPayMicroPay $microPayInput
     * @throws WxpayException
     * @return mixed 返回查询接口的结果
     */
    protected function submitMicroPay($microPayInput)
    {
        //①、提交被扫支付
        $result = WxPayApi::micropay($microPayInput, 5);
        //如果返回成功
        if (!array_key_exists("return_code", $result)
            || !array_key_exists("out_trade_no", $result)
            || !array_key_exists("result_code", $result)
        ) {
            throw new WxPayException("接口调用失败！result:" . print_r($result, true));
        }

        //签名验证
        $out_trade_no = $microPayInput->GetOut_trade_no();

        //②、接口调用成功，明确返回调用失败
        if ($result["return_code"] == "SUCCESS" &&
            $result["result_code"] == "FAIL" &&
            $result["err_code"] != "USERPAYING" &&
            $result["err_code"] != "SYSTEMERROR"
        ) {
            return false;
        }

        //③、确认支付是否成功
        $queryTimes = 10;
        while ($queryTimes > 0) {
            $succResult = 0;
            $queryResult = $this->queryNativeOrder($out_trade_no, $succResult);
            //如果需要等待1s后继续
            if ($succResult == 2) {
                sleep(2);
                continue;
            } else if ($succResult == 1) {//查询成功
                return $queryResult;
            } else {//订单交易失败
                return false;
            }
        }

        //④、次确认失败，则撤销订单
        if (!$this->cancelNativeOrder($out_trade_no)) {
            throw new WxpayException("撤销单失败！");
        }

        return false;
    }

    /**
     *
     * 查询订单情况
     * @param string $out_trade_no 商户订单号
     * @param int $succCode 查询订单结果
     * @return bool|mixed 订单不成功，1表示订单成功，2表示继续等待
     */
    protected function queryNativeOrder($out_trade_no, &$succCode)
    {
        $queryOrderInput = new WxPayOrderQuery();
        $queryOrderInput->SetOut_trade_no($out_trade_no);
        $result = WxPayApi::orderQuery($queryOrderInput);

        if ($result["return_code"] == "SUCCESS"
            && $result["result_code"] == "SUCCESS"
        ) {
            //支付成功
            if ($result["trade_state"] == "SUCCESS") {
                $succCode = 1;
                return $result;
            } //用户支付中
            else if ($result["trade_state"] == "USERPAYING") {
                $succCode = 2;
                return false;
            }
        }

        //如果返回错误码为“此交易订单号不存在”则直接认定失败
        if ($result["err_code"] == "ORDERNOTEXIST") {
            $succCode = 0;
        } else {
            //如果是系统错误，则后续继续
            $succCode = 2;
        }
        return false;
    }

    /**
     * 撤销订单，如果失败会重复调用10次
     * @param string $out_trade_no
     * @param int $depth 调用深度 $depth
     * @return bool
     * @throws WxPayException
     */
    protected function cancelNativeOrder($out_trade_no, $depth = 0)
    {
        if ($depth > 10) {
            return false;
        }

        $clostOrder = new WxPayReverse();
        $clostOrder->SetOut_trade_no($out_trade_no);
        $result = WxPayApi::reverse($clostOrder);

        //接口调用失败
        if ($result["return_code"] != "SUCCESS") {
            return false;
        }

        //如果结果为success且不需要重新调用撤销，则表示撤销成功
        if ($result["result_code"] != "SUCCESS"
            && $result["recall"] == "N"
        ) {
            return true;
        } else if ($result["recall"] == "Y") {
            return $this->cancelNativeOrder($out_trade_no, ++$depth);
        }
        return false;
    }
}