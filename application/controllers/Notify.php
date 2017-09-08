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
        //挂载数据库
        $this->load->database();
    }

    /**
     * 支付宝支付回调地址 TODO 伪代码
     */
    public function alipayNotify()
    {
        $alipayConfig = $this->config->item('alipay_config');
        $gateway = Omnipay::create('Alipay_AopApp');
        $gateway->setSignType($alipayConfig['sign_type']); //RSA/RSA2
        $gateway->setAppId($alipayConfig['partner']);
        $gateway->setPrivateKey($alipayConfig['private_key_path']);
        $gateway->setAlipayPublicKey($alipayConfig['ali_public_key_path']);
        $gateway->setNotifyUrl($alipayConfig['notify_url']);
        $request = $gateway->completePurchase();
        $request->setParams($this->input->post());//使用POST超全局数组
        try {
            $response = $request->send();
            if ($response->isPaid()) {
//                Payment is successful Payment is success
                die('success'); //The response should be 'success' only
            } else {
//                Payment is not successful
                die('fail');
            }
        } catch (Exception $e) {
//            Payment is not successful
            die('fail');
        }
        ######################################################################TODO 以下待移植
        //商户订单号
        $order_sn = $_POST['out_trade_no'];

        //支付宝交易号
        $out_trade_no = $_POST['trade_no'];

        //交易状态
        $trade_status = $_POST['trade_status'];

        if ($_POST['trade_status'] == 'TRADE_FINISHED') {
            //判断该笔订单是否在商户网站中已经做过处理
            //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
            //如果有做过处理，不执行商户的业务程序

            //注意：
            //退款日期超过可退款期限后（如三个月可退款），支付宝系统发送该交易状态通知

            //调试用，写文本函数记录程序运行情况是否正常
            //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
        }else if ($_POST['trade_status'] == 'TRADE_SUCCESS'){

            $this->db->trans_begin();
            $where=array('order_sn'=>$order_sn);
            $order=$this->db->from('order')->where($where)->first_row('array');

            if($order['pay_status']==1){
                echo "success";        //如果支付成功 直接放回success
                exit();
            }

            $res = $this->changeOrderStatus($order);

            if(!$res)
            {
                $this->db->trans_rollback();
                M()->rollback();
            }

            if($order['prom_id']){
                $res2 = $this->Join_Prom($order['prom_id']);
                if($res2){
                    M()->commit();
                    $group_info = M('group_buy')->where(array('id'=>$order['prom_id']))->find();
                    if($group_info['mark'] > 0){
                        $nums = M('group_buy')->where('(`mark`='.$group_info['mark'].' or `id`='.$group_info['mark'].') and `is_pay`=1')->count();
                        if(($nums)==$group_info['goods_num'])
                        {
                            $Goods = new BaseController();
                            $Goods->getFree($group_info['mark'],$order);
                        }
                        M('group_buy')->where(array('id'=>$group_info['mark']))->setInc('order_num');
                        M('group_buy')->where(array('mark'=>$group_info['mark']))->save(array('order_num'=>$nums+1));
                    }
                }else{
                    M()->rollback();
                    exit();
                }
            }else{
                M()->commit();
            }

        }
        echo "success";        //请不要修改或删除

    }

    /**
     * 微信支付通知地址 TODO 伪代码
     */
    public function wechatPayNotify()
    {
        $wxConfig = $this->config->item('wx_js_pay');
        $options = [
            'app_id' => $wxConfig['app_id'],
            'secret' => $wxConfig['secret'],     // AppSecret
            'payment' => [
                'merchant_id' => $wxConfig['merchant_id'],
                'key' => $wxConfig['key'],
                'cert_path' => $wxConfig['cert_path'], // XXX: 绝对路径！！！！
                'key_path' => $wxConfig['key_path'],      // XXX: 绝对路径！！！！
                'notify_url' => $wxConfig['notify_url']
            ],
        ];
        $app = new Application($options);
        $response = $app->payment->handleNotify(function ($notify, $successful) {
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

        #######################################################################################TODO 以下待移植
        //使用通用通知接口
        $notify = new \Notify_pub();

        //存储微信的回调
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        if(!$xml) $xml=file_get_contents("php://input");
        $notify->saveData($xml);

        //以log文件形式记录回调信息
        $log_ = new \Log_();
        $log_name=dirname(__FILE__)."/notify_url.log";//log文件路径

        $log_->log_result($log_name,"【接收到的notify通知】:\n".$xml."\n");

        if($notify->checkSign() == TRUE)
        {
            $this->db->trans_begin();
            //更新商户状态
            $order_sn = $notify->data['out_trade_no'];

            $where="order_sn = $order_sn";
            $order=M('order')->where($where)->find();

            if($order['pay_status']==1){
                $notify->setReturnParameter("return_code","SUCCESS");
                exit();
            }
            $res = $this->changeOrderStatus($order);

            if(!$res)
            {
                $log_->log_result($log_name,"【修改订单状态】:\n".$res."\n");
                $this->db->trans_rollback();
                exit();
            }

            if($order['prom_id']){
                $res2 = $this->Join_Prom($order['prom_id']);
                $log_->log_result($log_name,"【团修改】:\n".$res2."\n");
                if($res2){
                    $group_info = $this->db->from('group_buy')
                        ->where(array('id'=>$order['prom_id']))
                        ->first_row('array');
                    $this->db->where(array('id'=>$group_info['mark']))
                        ->set('order_num','order_num+1')
                        ->update('group_buy');
                    if($group_info['mark']>0){
                        $nums = $this->db
                            ->group_start()
                                ->where(['mark'=>$group_info['mark']])
                                ->or_where(['id'=>$group_info['mark']])
                            ->group_end()
                            ->where(['is_pay'=>1])
                            ->count_all('group_buy');
                        $this->db->where(array('mark'=>$group_info['mark']))
                            ->update('group_buy',array('order_num'=>$nums));
                        if(($nums)==$group_info['goods_num'])
                        {
                            $Goods = new BaseController();
                            $Goods->getFree($group_info['mark'],$order);
                            $this->db->trans_commit();
                        }
                        $this->db->trans_commit();
                    }
                    $this->db->trans_commit();
                }else{
                    $this->db->trans_rollback();
                    exit();
                }
            }else{
                $this->db->trans_commit();
            }
            $log_->log_result($log_name,"【成功】");
            $notify->setReturnParameter("return_code","SUCCESS");
        }else{
            $log_->log_result($log_name,"签名验证:".$notify->checkSign());
        }
    }

    /**
     * QQ支付通知地址
     */
    public function tencentNotify(){
        //加载QQ支付类
        $this->load->library('tencentpay');
        list($code, $data) = $this->tencentpay->checkNotify();
        if ($code !== 0) {
            echo $this->tencentpay->failAck();
            exit();
        }

        $this->db->trans_begin();
        //更新商户状态
        $order_sn = $data['out_trade_no'];
        $where = array('order_sn' => $order_sn);
        $order = $this->db->from('order')
            ->where($where)
            ->first_row('array');

        if ($order['pay_status'] == 1) {
            echo $this->tencentpay->successAck();
            exit();
        }

        $res = $this->changeOrderStatus($order);

        if (!$res) {
            $this->tencentpay->log("【修改订单状态】:\n" . $res . "\n", "notify");
            $this->db->trans_rollback();
            exit();
        }

        if ($order['prom_id']) {
            $res2 = $this->Join_Prom($order['prom_id']);
            $this->tencentpay->log("【团修改】:\n" . $res2 . "\n", "notify");
            if ($res2) {
                $group_info = $this->db->from('group_buy')
                    ->where(['id' => $order['prom_id']])
                    ->first_row('array');
                $this->db->where(['id' => $group_info['mark']])
                    ->set('order_num', 'order_num+1', FALSE)
                    ->update('group_buy');
                if ($group_info['mark'] > 0) {
                    $nums = $this->db
                        ->group_start()
                            ->where(['mark'=>$group_info['mark']])
                            ->or_where(['id'=>$group_info['mark']] )
                        ->group_end()
                        ->where(['is_pay'=>1])
                        ->count_all('group_buy');
                    $this->db->where(['mark' => $group_info['mark']])
                        ->update('group_buy',array('order_num' => $nums));
                    if (intval($nums) >= $group_info['goods_num']) {
                        $Goods = new BaseController();
                        $Goods->getFree($group_info['mark'], $order);//TODO 需要修改
                    }
                }
                $this->db->trans_start();
            } else {
                $this->db->trans_rollback();
                exit();
            }
        } else {
            $this->db->trans_commit();
        }
        $log_name = '';
        $this->tencentpay->log($log_name, "【成功】notify");
        echo $this->tencentpay->successAck();
    }

    /**
     * 开团 参团的时候在支付完成时将is_pay字段改变，标示加入团成功
     *
     * @param $order_id
     * @return mixed
     */
    public function Join_Prom($order_id)
    {
        $data['is_pay'] = 1;
        $res = $this->db->from('group_buy')->where('`id`=' . $order_id)->update($data);
        return $res;
    }

    /**
     * 修改订单状态 TODO 需要移到MODEL中
     *
     * @param $order
     * @return CI_DB_active_record|CI_DB_result [type]        [description]
     * @internal param $ [type] $order [description]
     */
    public function changeOrderStatus($order)
    {
        $data['pay_status'] = 1;
        if (!empty($order['prom_id'])) {
            $data['order_type'] = 11;
        } else {
            $data['order_type'] = 2;
        }
//        $this->order_redis_status_ref($order['user_id']); //TODO 暂时禁用
        $openid = $this->db->from('users')
            ->where(['user_id'=>$order['user_id']])
            ->select('wx_openid')
            ->first_row('array')['wx_openid'];//TODO getField方法?
        $goods_name = $this->db->from('goods')
            ->where(['goods_id'=>$order['goods_id']])
            ->select('goods_name')
            ->first_row('array')['goods_name'];//TODO getField方法?
        $wxtmplmsg = new WxtmplmsgController();//TODO  需要移到其他位置 发送模板消息
        $wxtmplmsg->order_payment_success($openid, $order['order_amount'], $goods_name);

        //销量、库存
        $this->db->where(['goods_id'=>$order['goods_id']])->set('sales','sales+'.$order['num'])->update('goods');
        $this->db->where(['id'=> $order['store_id']])->set('sales', 'sales+'.$order['num'],false)->update('merchant');
        $res = $this->db->where(['order_id'=>$order['order_id']])->update('order',$data);
        return $res;
    }

    /**
     * 状态置为1, 待刷新缓存
     *
     * @param $user_id
     * @deprecated
     */
    public function order_redis_status_ref($user_id='1')
    {
        $this->load->driver('cache');
        $this->cache->redis->save("getOrderList_status_" . $user_id, '1');
        $this->cache->redis->save("getCountUserOrder_status" . $user_id, '1');
        $this->cache->redis->save("return_goods_list_status" . $user_id, '1');
        $this->cache->redis->save("getUserPromList_status" . $user_id, '1');
        $this->cache->redis->del($this->cache->redis->keys('TuiSong*'));//删除推送缓存
    }

}