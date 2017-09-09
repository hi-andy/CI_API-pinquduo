<?php
/**
 * Created by PhpStorm.
 * User: Hua
 * Date: 2017/9/3
 * Time: 10:49
 */

class User extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * 第三方登录
     */
    public function third_login()
    {
        $openid    = $this->input->get('openid') ? : '';
        $unionid   = $this->input->get('unionid') ? : '';
        $oauth     = $this->input->get('oauth') ? : '';
        $nickname  = $this->input->get('nickname') ? : '';
        $head_pic  = $this->input->get('head_pic') ? : '';
        $version   = $this->input->get('version') ? : '';

        /**
         * 尝试获取用户信息
         */
        $this->load->model('user_model');
        $userInfo = $this->user_model->user_info($openid, 2, $unionid);

        if ($userInfo['user_id'] > 0) {
            $this->success($userInfo);
        } else {
            /**
             * 注册新用户
             */
            $userData['password'] = '';
            $userData['unionid'] = $unionid;
            $userData['nickname'] = $nickname;
            $userData['reg_time'] = time();
            $userData['version'] = $version;
            $userData['head_pic'] = $head_pic;
            $userData['oauth'] = $oauth;
            // 分别保存 openid， 区分微信公众号登录和 App 使用微登录。 2017-8-22 Hua
            if ($oauth == 'wx') {
                $userData['wx_openid'] = $openid;
            } else {
                $userData['openid'] = $openid;
            }
            // 微信公众号和微信 App 登录统一使用相同 oauth 类型识别
            $userData['oauth'] = ($oauth == 'wx' || $oauth == 'weixin') ? 'wx_union' : $oauth;

            $userId = $this->db->insert('users', $userData);
            $userData['user_id'] = $userId;

            // 注册用户到环信
            $hx = new HuanXin();
            $password = md5($userData['user_id'] . $this->config->item('SIGN_KEY'));
            $hx->hx_register($userData['user_id'], $password, $userData['nickname']);

            $this->success($userData);
        }
    }

    /**
     * 发送验证码
     */
    public function get_code()
    {
        $mobile = $this->input->get('mobile');
        $this->load->helper('Tools_helper');
        if (!check_mobile($mobile)) $this->failed('手机号码格式有误');

        /**
         * 获取 session
         */
        $session_key = 'm_' . $mobile;
        $this->load->library('session');
        $session_data = $this->session->userdata($session_key);

        $verify_data = unserialize($session_data);

        if ( time() - $verify_data['time'] > 60) {
            $code = rand(1000, 9999);
            $this->load->library('../controllers/SMS');
            $result = (new SMS())->sendSMS($mobile, "code", $code, "SMS_62265047", "normal", "登录验证", "拼趣多");
        }

        /**
         * 发送成功
         */
        if(!empty($result)) {
            /**
             * 保存到 session
             */
            $this->load->library('session');
            $data = array('m_' . $mobile=>serialize(array('mobile'=>$mobile, 'code'=>$code, 'time'=>time())));
            $this->session->set_userdata($data);

            $this->success();
        } else {
            $this->failed('验证码发送失败');
        }
    }

    /**
     * 手机号码登录
     */
    public function login()
    {
        $mobile = $this->input->get('mobile');
        $code   = $this->input->get('code');

        /**
         * 获取 session
         */
        $session_key = 'm_' . $mobile;
        $this->load->library('session');
        $session_data = $this->session->userdata($session_key);


        $verify_data = unserialize($session_data);
        if (!($mobile == $verify_data['mobile'] && $code == $verify_data['code'])) {
            $this->failed('手机号与验证码不匹配');
        }



        $userInfo = $this->db->select('user_id,mobile,head_pic')->get_where('users', array('mobile' => $mobile))->row_array();
        /**
         * 用户不存在，注册用户
         */
        if (!$userInfo['user_id'])
        {
            $userInfo['reg_time'] = time();
            $userInfo['head_pic'] = CDN.'/Public/upload/logo/logo.jpg';
            $userInfo['nickname'] = $mobile;
            $userInfo['mobile'] = $mobile;
            $user_id = $this->db->insert('users', $userInfo);

            /**
             * 注册到环信
             */
            $hx = new HuanXin();
            $password = md5($user_id . $this->config->item('SIGN_KEY'));
            $hx->hx_register($user_id,$password,$mobile);
            $userInfo['user_id'] = $user_id;
        }

        /*
         * 将手机号码中间四位变成*号
         */
        $userInfo['name'] = substr_replace($userInfo['mobile'], '****', 3, 4);

        /**
         * 获取用户最近订单状态
         */
        $this->load->model('order_model');
        $userInfo['userdetails'] = $this->order_model->user_order_summary($userInfo['user_id']);

        /**
         * 删除 session
         */
        $this->session->unset_userdata($session_key);

        $this->success($userInfo);
    }

    /**
     * 个人中心首页
     */
    public function user_center()
    {
        $user_id = $this->input->get('user_id');

        /**
         * 获取用户最近订单
         */
        $this->load->model('order_model');
        $order_summary = $this->order_model->user_order_summary($user_id);

        $this->success($order_summary);
    }

    /**
     * 全部订单　订单列表
     */
    public function all_order_list()
    {

        $user_id    = $this->input->get('user_id');
        $type       = $this->input->get('type') ?: 0; //0.全部 1.拼团中 2.待发货 3.待收货 4.待付款 5.已完成
        $page       = $this->input->get('page') ?: 1;
        $page_size  = $this->input->get('page_size') ?: 20;
        $offset     = ($page - 1) * $page_size;

        /**
         * 组合查询条件
         */
        switch ($type) {
            case 1 : //拼团中
                $condition = 'user_id =' . $user_id . ' AND the_raise = 0 and (order_type = 11 or order_type = 10)';
                break;
            case 2 : //待发货
                $condition = 'user_id = ' . $user_id . ' AND the_raise = 0 and (order_type = 2 or order_type = 14)';
                break;
            case 3 : //待收货
                $condition = 'user_id = ' . $user_id . ' AND the_raise = 0 and (order_type = 3 or order_type = 15)';
                break;
            case 4 : //待付款
                $condition = 'user_id = ' . $user_id . ' AND the_raise = 0 and (order_type = 1 or order_type = 10)';
                break;
            case 5 : //已完成
                $condition = 'user_id = ' . $user_id . 'AND the_raise = 0 and order_type = 4';
                break;
            default : //全部
                $condition = 'user_id = ' . $user_id . ' AND the_raise = 0 ';
        }

        /**
         * 获取数据
         */
        $this->load->model('order_model');
        $order = $this->order_model->lists($condition, $page_size, $offset);

        if (!empty($order)) {
            $this->success($order);
        } else {
            $this->failed();
        }
    }

    /**
     * 我的拼团 订单列表
     */
    public function group_order_list()
    {

        $user_id    = $this->input->get('user_id');
        $type       = $this->input->get('type') ?: 0; //0全部 1拼团中 2已成团 3拼团失败
        $page       = $this->input->get('page') ?: 1;
        $page_size  = $this->input->get('page_size') ?: 20;
        $offset     = ($page - 1) * $page_size;

        /**
         * 组合查询条件
         */
        switch ($type) {
            case 1 : //拼团中
                $condition = ' user_id = ' . $user_id . ' AND the_raise = 0 and order_status = 8 ';
                break;
            case 2 : //已成团
                $condition = ' user_id = ' . $user_id . ' AND the_raise = 0 and order_status = 11 ';
                break;
            case 3 : //拼团失败
                $condition = ' user_id = ' . $user_id . ' AND the_raise = 0 and pay_status = 1 and (order_status = 9 or order_status = 10) ';
                break;
            default : //全部
                $condition = ' user_id = ' . $user_id . ' AND the_raise = 0 and prom_id > 0 ';
        }

        /**
         * 获取数据
         */
        $this->load->model('order_model');
        $order = $this->order_model->lists($condition, $page_size, $offset);

        if (!empty($order)) {
            $this->success($order);
        } else {
            $this->failed();
        }

    }

    /**
     * 我的免单 订单列表
     */
    public function free_order_list()
    {
        $user_id    = $this->input->get('user_id');
        $page       = $this->input->get('page') ?: 1;
        $page_size  = $this->input->get('page_size') ?: 20;
        $offset     = ($page - 1) * $page_size;

        /**
         * 组合查询条件
         */
        $condition = ' user_id = ' . $user_id . ' AND is_free = 1 and is_pay = 1 ';

        /**
         * 获取数据
         */
        $this->load->model('order_model');
        $order = $this->order_model->lists($condition, $page_size, $offset);

        if (!empty($order)) {
            $this->success($order);
        } else {
            $this->failed();
        }
    }

    /**
     * 退款/售后 订单列表
     */
    public function return_order_list()
    {
        $user_id    = $this->input->get('user_id');
        $page       = $this->input->get('page') ?: 1;
        $page_size  = $this->input->get('page_size') ?: 20;
        $offset     = ($page - 1) * $page_size;

        /**
         * 组合查询条件
         */
        $condition = ' order_type in (6,7,8,9,16) and user_id = ' . $user_id;

        /**
         * 获取数据
         */
        $this->load->model('order_model');
        $order = $this->order_model->lists($condition, $page_size, $offset);

        if (!empty($order)) {
            $this->success($order);
        } else {
            $this->failed();
        }
    }


}