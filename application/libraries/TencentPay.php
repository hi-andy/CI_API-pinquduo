<?php
/**
 * QQ支付类
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/7
 * Time: 10:11
 */

class TencentPay
{
    //统一下单网关地址
    const UNIFY_URL = 'https://qpay.qq.com/cgi-bin/pay/qpay_unified_order.cgi';
    //退款网关地址
    const REFUND_URL = 'https://api.qpay.qq.com/cgi-bin/pay/qpay_refund.cgi';

    private $app;
    private $appId;
    private $appKey;
    private $mchId;
    private $md5Key;
    private $logPath;
    private $certDir;
    private $certFile;
    private $keyFile;
    private $cacertFile;
    private $opUserPassMd5;
    private $notify_url;

    /**
     * 构造函数，支付时：配置支付相关参数、日志路径
     *           退款时：配置证书路径，操作员密码md5值
     * @param array $config [description]
     */
    public function __construct($config = array())
    {
        $this->app = &get_instance();
        $this->app->config->load('pay_settings');
        $config = $this->app->config->item('qq_pay');
        //读取QQ支付配置
        $this->appId = $config['appId'];
        $this->appKey = $config['appKey'];
        $this->mchId = $config['mchId'];
        $this->md5Key = $config['md5Key'];
        $this->logPath = $config['logPath'];
        $this->certDir = $config['cert_dir'];
        $this->certFile = $config['certFile'];
        $this->keyFile = $config['keyFile'];
        $this->cacertFile = $config['cacertFile'];
        $this->opUserPassMd5 = $config['opUserPassMd5'];
        $this->notify_url = $config['notify_url']; //TODO 通知地址需要重写

        foreach ($config as $k => $v) {
            if (!empty($v)) {
                $this->{$k} = $v;
            }
        }
    }

    /**
     * [统一下单接口]
     * @param $orderSn
     * @param $amount
     * @param $notifyUrl
     * @param string $goodsDesc
     * @return array [array]         [array($code, $msg), $code=0表示成功，此时$msg的值为prepay_id]
     *                          $code 为其他表示失败，$msg是错误描述
     * @internal param $ [string]  $orderSn  [订单号]
     * @internal param $ [integer] $amount [支付金额，单位：分]
     * @internal param $ [string] $notifyUrl 回调地址
     */
    public function unifyOrder($orderSn, $amount, $notifyUrl, $goodsDesc = "商品")
    {
        $arr = [
            "appid" => $this->appId,
            "mch_id" => $this->mchId,
            "nonce_str" => $this->getNonceStr(),
            "body" => "拼趣多-" . $goodsDesc,
            "out_trade_no" => $orderSn,
            "fee_type" => "CNY",
            "total_fee" => intval($amount), // 支付金额，单位：分
            "spbill_create_ip" => self::getRealIp(),
            "time_start" => date("YmdHis"),
            "trade_type" => "JSAPI",
            "notify_url" => $notifyUrl
        ];

        $arr["sign"] = $this->createSign($arr);

        $postStr = $this->arrayToXml($arr);
        $this->log($postStr, "pay-request");

        $result = self::remotePost(self::UNIFY_URL, $postStr);

        $this->log($result, "pay-response");

        if (!empty($result)) {
            $xml = simplexml_load_string($result);
            if ($xml->return_code == "SUCCESS") {
                if ($xml->result_code == "SUCCESS") {
                    $data = array(
                        "tokenId" => $xml->prepay_id,
                        "appInfo" => sprintf("appid#%s|bargainor_id#%s|channel#wallet", $this->appId, $this->mchId)
                    );
                    return array(0, $data);
                } else {
                    return array(2, sprintf("[%s]%s", $xml->err_code, $xml->err_code_desc));
                }
            } else {
                return array(1, sprintf("[%s]%s", $xml->retcode, $xml->retmsg));
            }
        } else {
            return array(-1, "network error");
        }
    }

    /**
     * 创建支付脚本
     *
     * @param $params
     * @return string
     */
    public function createPayScript($params)
    {
        $scriptTpl = '<script src="https://open.mobile.qq.com/sdk/qqapi.js?_bid=152"></script>';
        $scriptTpl .= "<script>\n";
        $scriptTpl .= "(function(){\n";
        $scriptTpl .= "    mqq.tenpay.pay({tokenId: '%s',appInfo: '%s'}, \n";
        $scriptTpl .= "        function(result, resultCode){\n";
        $scriptTpl .= "            if ((result && result.resultCode === 0) || (resultCode === 0)) {\n";
        $scriptTpl .= "                 setTimeout(function(){window.location.href='%s';}, 1500);\n";
        $scriptTpl .= "             } else {\n";
        $scriptTpl .= "                  if (result.match(/permission/)) {\n";
        $scriptTpl .= "                     alert('您的QQ钱包需要实名认证才能使用');\n";
        $scriptTpl .= "                  } else {\n";
        $scriptTpl .= "                      alert('支付失败' + result);setTimeout(function(){history.back(-1);},1500);\n";
        $scriptTpl .= "                  }\n";
        $scriptTpl .= "             }\n";
        $scriptTpl .= "         }\n";
        $scriptTpl .= ")})();</script>\n";

        return sprintf($scriptTpl, $params["tokenId"], $params["appInfo"], $params["go_url"]);
    }

    /**
     * QQ支付
     *
     * @param array $order
     */
    public function getQQPay(array $order = [])
    {
        list($code, $data) = $this->unifyOrder($order['order_sn'], $order['order_amount'] * 100, $this->notify_url);

        if ($order['prom_id']) {
            $this->app->load->database();
            $prom_info = $this->app->db->from('group_buy')->where(array('id' => $order['prom_id']))->first_row('array');
            $type = $prom_info['mark'] > 0 ? 1 : 0;
            $go_url = 'https://wx.pinquduo.cn/order_detail.html?order_id=' . $prom_info['order_id'] . '&type=' . $type . '&user_id=' . $order['user_id'];
        } else {
            $go_url = 'https://wx.pinquduo.cn/order_detail.html?order_id=' . $order['order_id'] . '&type=2&user_id=' . $order['user_id'];;
        }
        $data['go_url'] = $go_url;

        if ($code == 0) {
            $script = $this->createPayScript($data);
            echo $script;
            die();
        } else {
            echo sprintf('<script>alert("%s");</script>', $data);
            die();
        }
    }

    /**
     * 支付回调通知处理方法
     *
     * @return [array] [array($code, $data) $code=0表示支付成功，非0表示支付失败，$data为失败错误信息 string]
     * $code=0时，$data是一个数组，包含bank_type、total_fee、cash_fee、out_trade_no、openid等字段
     */
    public function checkNotify()
    {
        $postData = file_get_contents("php://input");
        if (!empty($postData)) {
            $this->log($postData, "pay-notify");

            $xml = simplexml_load_string($postData);
            $arr = [];
            foreach ($xml as $k => $v) {
                $arr[$k] = $v . "";
            }
            if ($arr["trade_state"] == "SUCCESS") {
                if ($arr["mch_id"] != $this->mchId) {
                    return array(2, "mch_id incorrect!");
                } else {
                    $qqSign = $arr["sign"];
                    unset($arr["sign"]);

                    $mySign = $this->createSign($arr);
                    if ($qqSign == $mySign) {
                        $fields = ["bank_type", "total_fee", "cash_fee", "coupon_fee", "out_trade_no", "transaction_id", "openid"];
                        foreach ($fields as $field) {
                            if (!empty($arr[$field])) {
                                $info[$field] = $arr[$field];
                            }
                        }
                        /**
                         * //     "bank_type"      银行类型,
                         * //     "total_fee"      订单总金额，单位：分
                         * //     "cash_fee"       实际支付金额，单位：分
                         * //     "coupon_fee"     本次交易中，QQ钱包提供的优惠金额
                         * //     "out_trade_no"   商户系统内部的订单号
                         * //     "transaction_id" QQ钱包订单号
                         * //     "openid"         用户openid
                         **/
                        return array(0, $info);
                    } else {
                        return array(3, "sing error!" . $mySign);
                    }
                }
            } else {
                return array(1, "Failed!");
            }
        } else {
            return array(-1, "network error");
        }
    }

    /**
     * 向腾讯发送成功通知
     */
    public function successAck()
    {
        return "<xml><return_code>SUCCESS</return_code></xml>";
    }

    /**
     * 向腾讯发送失败通知
     */
    public function failAck()
    {
        return "<xml><return_code>FAIL</return_code></xml>";
    }

    /**
     * 退款
     *
     * @param $orderSn
     * @param $refundSn
     * @param $refundFee
     * @param string $opUserPassMd5
     * @param string $transactionId
     * @return array [type]                [description]
     * @internal param $ [type] $orderSn       [description]
     * @internal param $ [type] $fefundSn      [description]
     * @internal param $ [type] $refundFee     [description]
     * @internal param $ [type] $opUserPassMd5 [description]
     * @internal param $ [type] $outTradeNo    [description]
     */
    public function refund($orderSn, $refundSn, $refundFee, $opUserPassMd5 = '', $transactionId = '')
    {
        $arr = array(
            'appid' => $this->appId,
            'mch_id' => $this->mchId,
            'nonce_str' => $this->getNonceStr(),
            'out_trade_no' => $orderSn,
            'out_refund_no' => $refundSn,
            'refund_fee' => intval($refundFee),
            'op_user_id' => $this->mchId,
            'op_user_password' => $this->opUserPassMd5 ? $this->opUserPassMd5 : $opUserPassMd5
        );
        if (!empty($transactionId)) {
            $arr['transaction_id'] = $transactionId;
        }

        $arr["sign"] = $this->createSign($arr);

        $postStr = $this->arrayToXml($arr);
        $this->log($postStr, "refund-request");
        $header = array('Content-Type: application/xml');
        $certFilePath = $this->certDir . $this->certFile;
        $keyFilePath = $this->certDir . $this->keyFile;
        $cacertFilePath = $this->certDir . $this->cacertFile;

        $result = self::curlHttps(self::REFUND_URL, $postStr, $certFilePath, $keyFilePath, $cacertFilePath, $header);

        $this->log($result, "refund-response");

        if ($result) {
            $xml = simplexml_load_string($result);
            if ($xml->return_code == "SUCCESS") {
                if ($xml->result_code == "SUCCESS") {
                    // $data = array(
                    //     'transactionId' => $xml->transaction_id + '', // QQ钱包订单号
                    //     'orderSn'       => $xml->out_trade_no + '', // 订单号
                    //     'totalFee'      => $xml->total_fee + '', // 订单总金额
                    //     'refundSn'      => $xml->out_refund_no + '', // 商户退款单号
                    //     'refundId'      => $xml->refund_id + '', // QQ钱包退款单号
                    //     'refundFee'     => $xml->refund_fee + '' // 退款金额，单位：分
                    // );
                    $data = [];
                    foreach ($xml as $k => $v) {
                        $data[$k] = $v . '';
                    }
                    return array(0, $data);
                } else {
                    return array(2, sprintf("[%s]%s", $xml->err_code, $xml->err_code_desc));
                }
            } else {
                return array(1, sprintf("[%s]%s", $xml->retcode, $xml->retmsg));
            }
        } else {
            return array(-1, "config or network error");
        }
    }

    /**
     * 执行退款
     *
     * @param $orderSn
     * @param $refundFee
     * @param  string $opUserPassMd5 [description]
     * @param  string $transactionId [description]
     * @return array [type]                [description]
     * @internal param $ [type] $orderSn       退款订单号
     * @internal param $ [type] $refundFee     退款金额（单位：元）
     */
    public function doRefund($orderSn, $refundFee, $opUserPassMd5 = '', $transactionId = '')
    {
        $refundSn = $orderSn . time();

        list ($code, $refundResult) = $this->refund($orderSn, $refundSn, $refundFee * 100);
        if ($code != 0) {
            return array(
                'status' => 0,
                'msg' => '失败：' . $refundResult . "<br/>"
            );
        } else {
            $msg = "业务结果：" . $refundResult['result_code'] . "<br>";
            //$msg .= "错误代码：".$refundResult['err_code']."<br>";
            // $msg .= "错误代码描述：".$refundResult['err_code_des']."<br>";
            $msg .= "公众账号ID：" . $refundResult['appid'] . "<br>";
            $msg .= "商户号：" . $refundResult['mch_id'] . "<br>";

            $msg .= "签名：" . $refundResult['sign'] . "<br>";
            $msg .= "微信订单号：" . $refundResult['transaction_id'] . "<br>";
            $msg .= "商户订单号：" . $refundResult['out_trade_no'] . "<br>";
            $msg .= "商户退款单号：" . $refundResult['out_refund_no'] . "<br>";
            $msg .= "微信退款单号：" . $refundResult['refund_id'] . "<br>";
            $msg .= "退款渠道：" . $refundResult['refund_channel'] . "<br>";
            $msg .= "退款金额：" . $refundResult['refund_fee'] . "<br>";

            return array(
                'status' => 1,
                'msg' => $msg,
                'out_refund_no' => $refundSn
            );
        }
    }

    /**
     * 创建签名
     *
     * @param $arr
     * @return string
     */
    private function createSign($arr)
    {
        ksort($arr);
        $s = "";
        foreach ($arr as $k => $v) {
            if (!empty($v)) {
                $s .= ("$k=$v&");
            }
        }
        $s .= ("key=" . $this->md5Key);
        return strtoupper(md5($s));
    }

    /**
     * 获取随机字符串
     *
     * @param int $len
     * @return bool|string
     */
    private function getNonceStr($len = 16)
    {
        return substr(str_shuffle('1234567890abcdefghijklmnopqrstuvwxyzABCDEFHGIJKLMNOPQRSTUVWXYZ'), 0, $len);
    }


    /**
     *  数组转成XML
     *
     * @param $arr
     * @return string
     */
    private function arrayToXml($arr)
    {
        $xml = "<xml>";
        foreach ($arr as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            }
        }
        $xml .= "</xml>";
        return $xml;
    }


    /**
     * 记录日志
     *
     * @param $str
     * @param string $note
     */
    private function log($str, $note = "request")
    {
        file_put_contents($this->logPath . "qq_pay.log", date("Y-m-d H:i:s") . "\t" . $note . PHP_EOL . $str . PHP_EOL, FILE_APPEND);
    }


    /**
     * 发起cURL请求
     *
     * @param $url
     * @param string $method
     * @param array $postData
     * @return mixed|null|string
     */
    private static function getHttpContent($url, $method = "GET", $postData = array())
    {
        $data = '';
        if (!empty($url)) {
            try {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_HEADER, false);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30); //30秒超时
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                if (strtoupper($method) == "POST") {
                    $curlPost = is_array($postData) ? http_build_query($postData) : $postData;
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
                }
                $data = curl_exec($ch);
                curl_close($ch);
            } catch (Exception $e) {
                $data = null;
            }
        }
        return $data;
    }

    /**
     * 以GET方式获取内容
     *
     * @param $url
     * @param array $data
     * @return mixed|null|string
     */
    public static function remoteGet($url, $data = array())
    {
        if (!empty($data)) {
            if (strrpos($url, "?") === false) {
                $url .= "?";
            }
            $url .= http_build_query($data);
        }
        return self::getHttpContent($url);
    }

    /**
     * 以POST方式获取内容
     *
     * @param $url
     * @param array $data
     * @return mixed|null|string
     */
    public static function remotePost($url, $data = array())
    {
        return self::getHttpContent($url, "POST", $data);
    }

    /**
     * 发起HTTPS请求
     *
     * @param $url
     * @param $data
     * @param $certPemFile
     * @param $keyPemFile
     * @param $cacertPemFile
     * @param array $header
     * @param int $timeout
     * @return mixed
     */
    private static function curlHttps($url, $data, $certPemFile, $keyPemFile, $cacertPemFile, $header = array(), $timeout = 30)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        curl_setopt($ch, CURLOPT_SSLCERT, $certPemFile);
        curl_setopt($ch, CURLOPT_SSLKEY, $keyPemFile);
        curl_setopt($ch, CURLOPT_CAINFO, $cacertPemFile);

        if (!empty($header)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }

        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

        $response = curl_exec($ch);

        if ($error = curl_error($ch)) {
            die($error);
        }

        curl_close($ch);

        return $response;
    }


    /**
     * 获取客户端真实IP
     *
     * @return bool
     */
    public static function getRealIp()
    {
        $ip = false;
        if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(", ", $_SERVER['HTTP_X_FORWARDED_FOR']);
            if ($ip) {
                array_unshift($ips, $ip);
                $ip = FALSE;
            }
            for ($i = 0; $i < count($ips); $i++) {
                if (!preg_match("/^(10|172\.16|192\.168)\./i", $ips[$i])) {
                    $ip = $ips[$i];
                    break;
                }
            }
        }
        return ($ip ? $ip : $_SERVER['REMOTE_ADDR']);
    }

}