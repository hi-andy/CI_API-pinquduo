<?php
/**
 * 支付宝支付类
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/5
 * Time: 15:39
 * Notice: 字符串型密钥会出现警告
 */

use Omnipay\Omnipay;

defined('BASEPATH') OR exit('No direct script access allowed');

class Alipay
{
    /**
     * @var CI_Controller
     */
    private $app;

    /**
     * @var null|string
     */
    private $alipay_config;

    /**
     * Alipay constructor.
     */
    public function __construct()
    {
        $this->app = &get_instance();
        //读取支付宝配置
        $this->app->config->load('pay_settings');
        $this->alipay_config = $this->app->config->item('alipay_config');
    }


    /**
     * 支付宝退款
     * @param $orderNo
     * @param $totalFee
     * @param $refundFee
     * @return array
     */
    public function aliRefund($orderNo, $totalFee, $refundFee)
    {
        //网关初始化
        $gateway = Omnipay::create('Alipay_AopApp');
        $gateway->setSignType($this->alipay_config['sign_type']); //RSA/RSA2
        $gateway->setAppId($this->alipay_config['partner']);
        $gateway->setPrivateKey($this->alipay_config['private_key_path']);
        $gateway->setAlipayPublicKey($this->alipay_config['ali_public_key_path']);
        $gateway->setNotifyUrl($this->alipay_config['notify_url']);
        //发起请求
        $request = $gateway->refund();
        $request->setBizContent([
            'out_trade_no' => strval($orderNo),
            'trade_no' => '',
            'refund_amount' => $refundFee,
            'out_request_no' => strval($orderNo) . '0001'
        ]);
        $response = $request->send();
        $data = $response->getData()['alipay_trade_refund_response'];
        if ($data['code'] == 1000) {
            return ['status' => 1, 'msg' => '操作成功'];
        } else {
            return ['status' => 0, 'msg' => $data['msg']];
        }
    }

    /**
     * APP创建订单 TODO 需要完善ORDER参数
     * @param array $order
     * @return mixed
     */
    public function purchase(array $order=[])
    {
        $gateway = Omnipay::create('Alipay_AopApp');
        $gateway->setSignType($this->alipay_config['sign_type']); //RSA/RSA2
        $gateway->setAppId($this->alipay_config['partner']);
        $gateway->setPrivateKey($this->alipay_config['private_key_path']);
        $gateway->setAlipayPublicKey($this->alipay_config['ali_public_key_path']);
        $gateway->setNotifyUrl($this->alipay_config['notify_url']);
        $request = $gateway->purchase();
        $request->setBizContent([
            'subject' => 'test',
            'out_trade_no' => date('YmdHis') . mt_rand(1000, 9999),
            'total_amount' => '0.01',
            'product_code' => 'QUICK_MSECURITY_PAY',
        ]);
        $response = $request->send();
        $orderString = $response->getOrderString();
        return $orderString;
//        测试结果    2017-9-6 09:50:28
//        alipay_sdk=lokielse%2Fomnipay-alipay&app_id=2088521292269473&biz_content=%7B%22subject%22%3A%22test%22%2C%22out_trade_no%22%3A%22201709060349136554%22%2C%22total_amount%22%3A%220.01%22%2C%22product_code%22%3A%22QUICK_MSECURITY_PAY%22%7D&charset=UTF-8&format=JSON&method=alipay.trade.app.pay¬ify_url=http%3A%2F%2Fpinquduo.cn%2FStore%2FAlipayapi%2Fnotify_url&sign_type=RSA×tamp=2017-09-06+03%3A49%3A13&version=1.0&sign=
    }

}