<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/5
 * Time: 15:37
 */
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
        $this->app = & get_instance();
        //读取微信支付配置 TODO ?  读取公众号orAPP
        $this->app->config->load('pay_settings');
    }


    /**
     * 微信退款方法
     * @param $orderNo
     * @param $totalFee
     * @param $refundFee
     * @param $is_jsapi
     */
    public function wxRefund($orderNo,$totalFee,$refundFee,$is_jsapi){
        switch ($is_jsapi){
            case 0://原生支付配置
                $config=$this->app->config->item('wx_js_pay');
                break;
            case 1:        //JS支付配置
                $config=$this->app->config->item('wx_app_pay');
                break;
            default:
                return ['status'=>0,'msg'=>'不支持此种支付方式'];
                break;
        }
        $options = [
            'app_id'  => $config['app_id'],         // AppID
            'secret'  => $config['secret'],     // AppSecret
            'payment' => [
                'merchant_id'        => $config['merchant_id'],
                'key'                => $config['key'],
                'cert_path'          => $config['cert_path'], // XXX: 绝对路径！！！！
                'key_path'           => $config['key_path'],      // XXX: 绝对路径！！！！
            ],
        ];
        $app=new \EasyWeChat\Foundation\Application($options);
        $payment=$app->payment;
        $refundNo=strval($orderNo).'0001';//只能退款一次
        $result=$payment->refund( $orderNo, $refundNo, $totalFee,$refundFee)->toArray();
        if($result['result_code']=='SUCCESS'){
            return ['status'=>1,'msg'=>'操作成功'];
        }else{
            return ['status'=>0,'msg'=>$result['err_code_des']];
        }
    }


}