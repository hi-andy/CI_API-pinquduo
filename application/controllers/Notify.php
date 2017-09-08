<?php

use EasyWeChat\Foundation\Application;
use Omnipay\Omnipay;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/5
 * Time: 17:06
 */

class Notify extends MY_Controller
{
    /**
     * Notify constructor.
     */
    public function __construct()
    {
        parent::__construct();
        //挂载支付配置
        $this->config->load('pay_settings');
    }
    /**
     * 支付宝支付回调地址 TODO 伪代码
     */
    public function alipayNotify(){
        //测试加载自定义类库
//        $this->load->library('alipay');
//        $this->alipay->purchase();
//        配置文件可用
//        $this->config->load('pay_settings');
//        $alipay_config=$this->config->item('alipay_config');
//        echo '<pre>';
//        var_dump($alipay_config);die();
        $alipayConfig=$this->config->item('alipay_config');
        $gateway = Omnipay::create('Alipay_AopApp');
        $gateway->setSignType($alipayConfig['sign_type']); //RSA/RSA2
        $gateway->setAppId($alipayConfig['partner']);
        $gateway->setPrivateKey($alipayConfig['private_key_path']);
        $gateway->setAlipayPublicKey($alipayConfig['ali_public_key_path']);
        $gateway->setNotifyUrl($alipayConfig['notify_url']);
        $request = $gateway->completePurchase();
        $request->setParams($_POST);//Optional
        try {
            $response = $request->send();
            if($response->isPaid()){
//                Payment is successful
                die('success'); //The response should be 'success' only
            }else{
//                Payment is not successful
                die('fail');
            }
        } catch (Exception $e) {
//            Payment is not successful
            die('fail');
        }

    }

    /**
     * 微信支付通知地址 TODO 伪代码
     */
    public function wechatPayNotify(){
        $this->load->library('alipay');
        $res=$this->alipay->purchase();
        var_dump($res);die();
        $wxConfig=$this->config->item('wx_js_pay');
        $options = [
            'app_id' => $wxConfig['app_id'],
            'payment' => [
                'merchant_id'        => $wxConfig['merchant_id'],
                'key'                => $wxConfig['key'],
                'cert_path'          => $wxConfig['cert_path'], // XXX: 绝对路径！！！！
                'key_path'           => $wxConfig['key_path'],      // XXX: 绝对路径！！！！
                'notify_url'         => $wxConfig['notify_url']
            ],
        ];
        $app = new Application($options);
        $response = $app->payment->handleNotify(function($notify, $successful){
            // 使用通知里的 "微信支付订单号" 或者 "商户订单号" 去自己的数据库找到订单
            $order = 查询订单($notify->out_trade_no);
            if (!$order) { // 如果订单不存在
                return 'Order not exist.'; // 告诉微信，我已经处理完了，订单没找到，别再通知我了
            }
            // 如果订单存在
            // 检查订单是否已经更新过支付状态
            if ($order->paid_at) { // 假设订单字段“支付时间”不为空代表已经支付
                return true; // 已经支付成功了就不再更新了
            }
            // 用户是否支付成功
            if ($successful) {
                // 不是已经支付状态则修改为已经支付状态
                $order->paid_at = time(); // 更新支付时间为当前时间
                $order->status = 'paid';
            } else { // 用户支付失败
                $order->status = 'paid_fail';
            }
            $order->save(); // 保存订单
            return true; // 返回处理完成
        });
        return $response;
    }


}