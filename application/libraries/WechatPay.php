<?php
/**
 * 微信支付类
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/5
 * Time: 15:37
 * Notice: 只需要将下载好的 CA 证书放置到您的服务器上某个位置，然后修改 php.ini 的 curl.cainfo 为该路径（绝对路径！），重启 php-fpm 服务即可。curl.cainfo = /path/to/downloaded/cacert.pem
 */

use EasyWeChat\Foundation\Application;
use EasyWeChat\Payment\Order;

defined('BASEPATH') OR exit('No direct script access allowed');

class WechatPay
{

    /**
     * @var CI_Controller
     */
    private $app;

    /**
     * Alipay constructor.
     */
    public function __construct()
    {
        $this->app = &get_instance();
        //读取微信支付配置
        $this->app->config->load('pay_settings');
    }


    /**
     * 微信退款方法
     * @param $orderNo
     * @param $totalFee
     * @param $refundFee
     * @param $is_jsapi
     */
    public function wxRefund($orderNo, $totalFee, $refundFee, $is_jsapi)
    {
        switch ($is_jsapi) {
            case 0://原生支付配置
                $config = $this->app->config->item('wx_app_pay');
                break;
            case 1:        //JS支付配置
                $config = $this->app->config->item('wx_js_pay');
                break;
            default:
                return ['status' => 0, 'msg' => '不支持此种支付方式'];
                break;
        }
        $options = [
            'app_id' => $config['app_id'],         // AppID
            'secret' => $config['secret'],     // AppSecret
            'payment' => [
                'merchant_id' => $config['merchant_id'],
                'key' => $config['key'],
                'cert_path' => $config['cert_path'], // XXX: 绝对路径！！！！
                'key_path' => $config['key_path'],      // XXX: 绝对路径！！！！
            ],
        ];
        $app = new \EasyWeChat\Foundation\Application($options);
        $payment = $app->payment;
        $refundNo = strval($orderNo) . '0001';//只能退款一次
        $result = $payment->refund($orderNo, $refundNo, $totalFee, $refundFee)->toArray();
        if ($result['result_code'] == 'SUCCESS') {
            return ['status' => 1, 'msg' => '操作成功'];
        } else {
            return ['status' => 0, 'msg' => $result['err_code_des']];
        }
    }

    /**
     * 统一下单 TODO 需要完善ORDER参数
     *
     * @param array $order
     */
    public function unifiedOrder(array $order=[])
    {
        //必定采用APP配置
        $config = $this->app->config->item('wx_app_pay');
        $options = [
            'app_id' => $config['app_id'],
            'secret' => $config['secret'],     // AppSecret
            'payment' => [
                'merchant_id' => $config['merchant_id'],
                'key' => $config['key'],
                'cert_path' => $config['cert_path'], // XXX: 绝对路径！！！！
                'key_path' => $config['key_path'],      // XXX: 绝对路径！！！！
                'notify_url' => 'https://m.baidu.com',       // 你也可以在下单时单独设置来想覆盖它
            ],
        ];
        $app = new Application($options);
        $payment = $app->payment;
        $attributes = [
            'trade_type' => 'APP', // JSAPI，NATIVE，APP...
            'body' => 'iPad mini 16G 白色',
            'detail' => 'iPad mini 16G 白色',
            'out_trade_no' => '1217752501201407033233368018',
            'total_fee' => 5388, // 单位：分
            'notify_url' => 'http://xxx.com/order-notify', // 支付结果通知网址，如果不设置则会使用配置里的默认地址
//            'openid'           => '当前用户的 openid', // trade_type=JSAPI，此参数必传，用户在商户appid下的唯一标识，
        ];
        $order = new Order($attributes);
        $result = $payment->prepare($order);
        //测试结果 2017-9-6 09:44:24
//        array(2) {
//            ["return_code"]=>
//          string(4) "FAIL"
//                    ["return_msg"]=>
//          string(24) "invalid spbill_create_ip" 不在支付授权目录导致
//        }
        if ($result->return_code == 'SUCCESS' && $result->result_code == 'SUCCESS') {
            $prepayId = $result->prepay_id;
            return $config = $payment->configForAppPayment($prepayId);//生成APP支付配置  数组格式 可以返回给APP调用
        }
    }

    /**
     * 公众号JS支付 TODO 需要完善ORDER参数
     *
     * @param array $order
     */
    public function jsOrder(array $order=[])
    {
        //必定采用APP配置
        $config = $this->app->config->item('wx_js_pay');
        $options = [
            'app_id' => $config['app_id'],
            'secret' => $config['secret'],     // AppSecret
            'payment' => [
                'merchant_id' => $config['merchant_id'],
                'key' => $config['key'],
                'cert_path' => $config['cert_path'], // XXX: 绝对路径！！！！
                'key_path' => $config['key_path'],      // XXX: 绝对路径！！！！
                'notify_url' => 'https://m.baidu.com',       // 你也可以在下单时单独设置来想覆盖它
            ],
        ];
        $app = new Application($options);
        $payment = $app->payment;
        $attributes = [
            'trade_type' => 'JSAPI', // JSAPI，NATIVE，APP...
            'body' => 'iPad mini 16G 白色',
            'detail' => 'iPad mini 16G 白色',
            'out_trade_no' => '1217752501201407033233368018',
            'total_fee' => 5388, // 单位：分
            'notify_url' => 'http://xxx.com/order-notify', // 支付结果通知网址，如果不设置则会使用配置里的默认地址
            'openid' => '当前用户的 openid', // trade_type=JSAPI，此参数必传，用户在商户appid下的唯一标识，
        ];
        $order = new Order($attributes);
        $result = $payment->prepare($order);
        if ($result->return_code == 'SUCCESS' && $result->result_code == 'SUCCESS') {
            $prepayId = $result->prepay_id;
            return $json = $payment->configForPayment($prepayId); // 返回 json 字符串，如果想返回数组，传第二个参数 false WeixinJSBridge前端模式 TODO 二选一
//            return $config = $payment->configForJSSDKPayment($prepayId); // 返回数组  JSSDK前端模式 TODO 二选一
        }
    }

}