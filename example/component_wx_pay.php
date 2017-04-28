<?php
/**
 * 第三方平台时，实现公众号微信支付样例
 * @author  lv_fan2008@sina.com
 */

include_once "config.php";
include_once "qrcode.php";
include_once "bootstrap.php";

class WxPayExample
{
    /**
     * @var FileCache
     */
    public $cache;

    /**
     * @var WxPay
     */
    public $wxPay;

    /**
     * @var WxComponentService
     */
    public $wxComponentService;

    public $payCfg;
    public $out_trade_no;
    public $msg;
    public $baseUrl;
    public $notify_url;
    public $refundNo;

    public function init()
    {
        $cfg_arr = array_values($GLOBALS['wxComponentConfig']);
        $wxComponentConfig = $cfg_arr[0];
        $this->cache = new FileCache($GLOBALS['cacheDir']);
        $this->wxComponentService = new WxComponentService($wxComponentConfig, $this->cache);
        $this->payCfg = $GLOBALS['wxTestPayCfg'];
        $this->wxPay = new WxPay($this->payCfg);

        $url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $this->baseUrl = substr($url, 0, strrpos($url, "/"));
        $this->notify_url = $this->baseUrl . "/component_wx_pay_notify.php";
        $this->out_trade_no[] = "A" . time() . rand(1, 999);
        $this->out_trade_no[] = "B" . time() . rand(1, 999);
        $this->out_trade_no[] = "C" . time() . rand(1, 999);
        $this->refundNo = "R" . time() . rand(1, 999);
    }

    public function run()
    {
        $this->init();
        if (method_exists($this, $_REQUEST['act'])) {
            try {
                $this->$_REQUEST['act']();
            } catch (Exception $e) {
                die("404");
            }
        } else {
            $this->def_act();
        }
    }

    public function def_act()
    {
        if ($_GET['qr_url']) {
            QRcode::png($_GET['qr_url']);
            exit;
        }
    }

    public function microPay()
    {
        $result = $this->wxPay->microPay($_POST['authCode'], $_POST['out_trade_no'], $_POST['body'], intval($_POST['amount'] * 100));
        if ($result === false) {
            $this->msg = "支付失败，原因：" . $this->wxPay->lastErrMsg;
        } else {
            $this->msg = "被扫支付结果：" . print_r($result, true);
        }
        $this->msg = "<p>post:" . print_r($_POST, true) . "</p>" . $this->msg;
    }

    public function nativePay()
    {
        $result = $this->wxPay->getNativePayUrl2($_POST['productId'], $_POST['body'], $_POST['out_trade_no'],
            intval($_POST['amount'] * 100), $this->notify_url);
        if ($result === false) {
            $this->msg = "获取扫码URL失败，原因：" . $this->wxPay->lastErrMsg;
        } else {
            $qr_url = $this->baseUrl . "/component_wx_pay.php?qr_url=" . urlencode($result);
            $this->msg = "生成扫码URL:{$result}<br>二维码：<img src='{$qr_url}' style='width: 200px;height: 200px;'>";
        }
        $this->msg = "<p>post:" . print_r($_POST, true) . "</p>" . $this->msg;
    }

    public function jsApiPayCode()
    {
        $this->jsApiPay();
    }

    public function jsApiPay()
    {
        if ($_POST['act'] == 'jsApiPay') {
            $this->cache->setCache('jsApiPay', json_encode($_POST), -1);
            $callbackUrl = $this->baseUrl . "/component_wx_pay.php?act=jsApiPayCode";
            $this->wxComponentService->getOauthOpenId($this->payCfg['AppId'], $callbackUrl);
        } else {

            $_POST = json_decode($this->cache->getCache('jsApiPay'), true);

            $openId = $this->wxComponentService->getOauthOpenId($this->payCfg['AppId']);
            $jsApiParameters = $this->wxPay->getJsApiPayParams($openId, $_POST['body'], $_POST['out_trade_no'],
                intval($_POST['amount'] * 100), $this->notify_url);

            if ($jsApiParameters === false) {
                $this->msg = "获取getJsApiPayParams失败，原因：" . $this->wxPay->lastErrMsg;
            } else {

                $url = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
                $accessToken = $this->wxComponentService->getOauthAccessToken($this->payCfg['AppId']);
                $jsEditAddress = $this->wxPay->getJsEditAddressParams($this->payCfg['AppId'], $url, $accessToken);

                $this->msg = <<<EOF
<script type="text/javascript">
	//调用微信JS api 支付
	function jsApiCall()
	{
		WeixinJSBridge.invoke(
			'getBrandWCPayRequest',
			{$jsApiParameters},
			function(res){
				WeixinJSBridge.log(res.err_msg);
				var msg = res.err_code == undefined ? '': res.err_code ;
				msg += res.err_desc == undefined ? '': res.err_desc ;
				msg += res.err_msg;
				alert(msg);
			}
		);
	}

	function callpay()
	{
		if (typeof WeixinJSBridge == "undefined"){
		    if( document.addEventListener ){
		        document.addEventListener('WeixinJSBridgeReady', jsApiCall, false);
		    }else if (document.attachEvent){
		        document.attachEvent('WeixinJSBridgeReady', jsApiCall);
		        document.attachEvent('onWeixinJSBridgeReady', jsApiCall);
		    }
		}else{
		    jsApiCall();
		}
	}
	//获取共享地址
	function editAddress()
	{
		WeixinJSBridge.invoke(
			'editAddress',
			{$jsEditAddress},
			function(res){
				var value1 = res.proviceFirstStageName;
				var value2 = res.addressCitySecondStageName;
				var value3 = res.addressCountiesThirdStageName;
				var value4 = res.addressDetailInfo;
				var tel = res.telNumber;
              if(value1=='' || value1 == undefined){
                 alert("cancle editAddress");
              }else{
				    alert(value1 + value2 + value3 + value4 + ":" + tel);
				}
			}
		);
	}

	var callEditAddress = function(){
		if (typeof WeixinJSBridge == "undefined"){
		    if( document.addEventListener ){
		        document.addEventListener('WeixinJSBridgeReady', editAddress, false);
		    }else if (document.attachEvent){
		        document.attachEvent('WeixinJSBridgeReady', editAddress);
		        document.attachEvent('onWeixinJSBridgeReady', editAddress);
		    }
		}else{
			editAddress();
		}
	};
	</script>
EOF;
                $msg = "<p>post:" . print_r($_POST, true) . "</p>";
                $msg .= "<p><input type='button' value='获取微信地址' onclick='callEditAddress();'></p>";
                $msg .= "<p><input type='button' value='立即支付' onclick='callpay();'></p>";
                $this->msg = $msg . $this->msg;
            }
        }
    }

    public function queryOrder()
    {
        $tradeNo = $_POST['transaction_id'];
        $type = 0;
        if ($tradeNo == '') {
            $type = 1;
            $tradeNo = $_POST['out_trade_no'];
        }
        $result = $this->wxPay->queryOrder($tradeNo, $type);
        if ($result === false) {
            $this->msg = "查询失败，原因：" . $this->wxPay->lastErrMsg;
        } else {
            $this->msg = print_r($result, true);
        }
        $this->msg = "<p>post:" . print_r($_POST, true) . "</p>" . $this->msg;
    }

    public function refundOrder()
    {
        $transactionIdOrOutTradeNo = $_POST['transaction_id'];
        $type = 0;
        if ($transactionIdOrOutTradeNo == '') {
            $type = 1;
            $transactionIdOrOutTradeNo = $_POST['out_trade_no'];
        }
        $totalFee = intval($_POST['amount'] * 100);
        $refundFee = intval($_POST['refundAmount'] * 100);
        $refundNo = $_POST['refundNo'];
        $result = $this->wxPay->refundOrder($totalFee, $refundFee, $refundNo, $transactionIdOrOutTradeNo, $type);
        if ($result === false) {
            $this->msg = "查询失败，原因：" . $this->wxPay->lastErrMsg;
        } else {
            log_ex("refund", print_r($result, true));
            $this->msg = print_r($result, true);
        }
        $this->msg = "<p>post:" . print_r($_POST, true) . "</p>" . $this->msg;
    }

    public function refundOrderQuery()
    {
        $refundQueryKey = $_POST['refund_id'];
        $type = 0;
        if ($refundQueryKey == '') {
            $type = 1;
            $refundQueryKey = $_POST['refundNo'];
        }
        if ($refundQueryKey == '') {
            $type = 2;
            $refundQueryKey = $_POST['transaction_id'];
        }
        if ($refundQueryKey == '') {
            $type = 3;
            $refundQueryKey = $_POST['out_trade_no'];
        }
        $result = $this->wxPay->refundOrderQuery($refundQueryKey, $type);
        if ($result === false) {
            $this->msg = "退款订单查询失败，原因：" . $this->wxPay->lastErrMsg;
        } else {
            $this->msg = print_r($result, true);
        }
        $this->msg = "<p>post:" . print_r($_POST, true) . "</p>" . $this->msg;
    }

    public function downloadBill()
    {
        $result = $this->wxPay->getBill($_POST['bill_date'], $_POST['bill_type'], $_POST['tar_type']);
        if ($result === false) {
            $this->msg = "退款订单查询失败，原因：" . $this->wxPay->lastErrMsg;
        } else {
            if (substr($result, 0, 5) == "<xml>") {
                $this->msg = htmlspecialchars($result);
                $this->msg = "<p>post:" . print_r($_POST, true) . "</p>" . $this->msg;
            } else {
                $filename = $_POST['bill_date'] . $_POST['bill_type'] . ($_POST['tar_type'] == "GZIP" ? ".gzip" : ".csv");
                header("Content-Type: APPLICATION/OCTET-STREAM");
                header("Content-Disposition: attachment;filename=\"" . $filename . "\"");
                header("Cache-Control: max-age=0");
                echo $result;
                exit;
            }
        }
    }
}

$WxPayExample = new WxPayExample();
$WxPayExample->run();

?>

<html>
<head>
    <title>第三方平台代公众号使用微信支付样例</title>
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
        第三方平台代公众号使用微信支付样例
    </h2>
</div>

<?php
if ($WxPayExample->msg) {
    echo "<div class='panel'> <h2>提交结果:</h2><p>{$WxPayExample->msg}</p></div>";
}
?>

<div class="panel">
    <h3>被扫支付</h3>

    <form action="component_wx_pay.php" method="post">
        <input type="hidden" name="act" value="microPay">

        <p>
            请输入微信付款码串:<input type="text" placeholder="请输入付款码串" value="" name="authCode">
        </p>

        <p>
            请输入商户订单号:<input type="text" placeholder="请输入商户订单号" value="<?php echo $WxPayExample->out_trade_no[0] ?>" name="out_trade_no">
        </p>

        <p>
            请输入商品名称:<input type="text" placeholder="请输入商品名称" value="测试商品001" name="body">
        </p>

        <p>
            请输入商品金额:<input type="text" placeholder="请输入商品金额" value="0.01" name="amount">
        </p>
        <input type="submit" value="提交支付">
    </form>
</div>


<div class="panel">
    <h3>扫码支付</h3>

    <form action="component_wx_pay.php" method="post">
        <input type="hidden" name="act" value="nativePay">

        <p>
            请输入商品标示串:<input type="text" placeholder="请输入商品标示串" value="G0000000001" name="productId">
        </p>

        <p>
            请输入商户订单号:<input type="text" placeholder="请输入商户订单号" value="<?php echo $WxPayExample->out_trade_no[1]; ?>" name="out_trade_no">
        </p>

        <p>
            请输入商品名称:<input type="text" placeholder="请输入商品名称" value="测试商品002" name="body">
        </p>

        <p>
            请输入商品金额:<input type="text" placeholder="请输入商品金额" value="0.01" name="amount">
        </p>

        <p>
            支付回调URL:<?php echo $WxPayExample->notify_url; ?>
        </p>
        <input type="submit" value="提交生成扫码URL">
    </form>
</div>


<div class="panel">
    <h3>JSAPI支付，微信下测试，提交后可测试微信地址</h3>

    <form action="component_wx_pay.php" method="post">
        <input type="hidden" name="act" value="jsApiPay">

        <p>
            请输入商户订单号:<input type="text" placeholder="请输入商户订单号" value="<?php echo $WxPayExample->out_trade_no[2]; ?>" name="out_trade_no">
        </p>

        <p>
            请输入商品名称:<input type="text" placeholder="请输入商品名称" value="测试商品003" name="body">
        </p>

        <p>
            请输入商品金额:<input type="text" placeholder="请输入商品金额" value="0.01" name="amount">
        </p>

        <p>
            支付回调URL:<?php echo $WxPayExample->notify_url; ?>
        </p>
        <input type="submit" value="提交JSAPI支付">
    </form>
</div>

<div class="panel">
    <h3>微信订单查询</h3>

    <form action="component_wx_pay.php" method="post">
        <input type="hidden" name="act" value="queryOrder">

        <p>
            请输入微信交易单号:<input type="text" placeholder="请输入微信交易单号" value="" name="transaction_id">
        </p>

        <p>
            请输入商户订单号:<input type="text" placeholder="请输入商户订单号" value="" name="out_trade_no">
        </p>

        <p>
            微信交易单号和商户订单号，二选一填写。
        </p>
        <input type="submit" value="提交微信订单查询">
    </form>
</div>

<div class="panel">
    <h3>微信订单退款</h3>

    <form action="component_wx_pay.php" method="post">
        <input type="hidden" name="act" value="refundOrder">

        <p>
            请输入微信交易单号:<input type="text" placeholder="请输入微信交易单号" value="" name="transaction_id">
        </p>

        <p>
            请输入商户订单号:<input type="text" placeholder="请输入商户订单号" value="" name="out_trade_no">
        </p>

        <p>
            请输入订单金额:<input type="text" placeholder="请输入订单金额" value="0.01" name="amount">
        </p>

        <p>
            请输入退款金额:<input type="text" placeholder="请输入退款金额" value="0.01" name="refundAmount">
        </p>

        <p>
            请输入退款单号:<input type="text" placeholder="请输入退款单号" value="<?php echo $WxPayExample->refundNo; ?>" name="refundNo">
        </p>

        <p>
            微信交易单号和商户订单号，二选一填写。
        </p>
        <input type="submit" value="微信订单退款">
    </form>
</div>

<div class="panel">
    <h3>微信订单退款查询</h3>

    <form action="component_wx_pay.php" method="post">
        <input type="hidden" name="act" value="refundOrderQuery">

        <p>
            请输入refund_id:<input type="text" placeholder="请输入refund_id" value="" name="refund_id">
        </p>

        <p>
            请输入退款单号:<input type="text" placeholder="请输入退款单号" value="" name="refundNo">
        </p>

        <p>
            请输入微信交易单号:<input type="text" placeholder="请输入微信交易单号" value="" name="transaction_id">
        </p>

        <p>
            请输入商户订单号:<input type="text" placeholder="请输入商户订单号" value="" name="out_trade_no">
        </p>

        <p>
            refund_id、退款单号、微信交易单号、商户订单号，四选一填写。
        </p>
        <input type="submit" value="微信订单退款查询">
    </form>
</div>

<div class="panel">
    <h3>微信对账单下载</h3>

    <form action="component_wx_pay.php" method="post">
        <input type="hidden" name="act" value="downloadBill">

        <p>
            请输入日期:<input type="text" placeholder="请输入日期" value="<?php echo date('Ymd'); ?>" name="bill_date">
        </p>

        <p>
            账单类型:<select name="bill_type">
                <option value="ALL">所有订单</option>
                <option value="SUCCESS">仅支付订单</option>
                <option value="REFUND">仅退款订单</option>
            </select>
        </p>

        <p>
            压缩类型:<select name="tar_type">
                <option value="">不压缩</option>
                <option value="GZIP">GZIP压缩</option>
            </select>
        </p>
        <input type="submit" value="提交微信对账单下载">
    </form>
</div>

</body>
</html>
