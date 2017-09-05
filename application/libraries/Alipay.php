<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/5
 * Time: 15:39
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
        $this->app = & get_instance();
        //读取支付宝配置
        $this->app->config->load('pay_settings');
        $this->alipay_config=$this->app->config->item('alipay_config');
    }


    /**
     * 支付宝退款 TODO 配置信息纳入配置文件
     * @param $orderNo
     * @param $totalFee
     * @param $refundFee
     * @return array
     */
    public function aliRefund($orderNo,$totalFee,$refundFee){
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
            'out_trade_no' =>strval($orderNo),
            'trade_no' => '',
            'refund_amount' => $refundFee,
            'out_request_no' => strval($orderNo).'0001'
        ]);
        $response=$request->send();
        $data=$response->getData()['alipay_trade_refund_response'];
        if($data['code']==1000){
            return ['status'=>1,'msg'=>'操作成功'];
        }else{
            return ['status'=>0,'msg'=>$data['msg']];
        }
    }

    /**
     * APP创建订单
     * @return mixed
     */
    public function purchase(){
        echo '<pre>';
        var_dump($this->alipay_config);die();
        $request = $gateway->purchase();
        $request->setBizContent([
            'subject'      => 'test',
            'out_trade_no' => date('YmdHis') . mt_rand(1000, 9999),
            'total_amount' => '0.01',
            'product_code' => 'QUICK_MSECURITY_PAY',
        ]);

        /**
         * @var AopTradeAppPayResponse $response
         */
        $response = $request->send();
        $orderString = $response->getOrderString();
        return $orderString;
    }

}