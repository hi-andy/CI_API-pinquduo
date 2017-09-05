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
     * 个人中心首页
     */
    public function userCenter()
    {

    }

    /**
     * 订单
     */
    public function order()
    {

    }

    /**
     * 优惠券
     */
    public function coupons()
    {

    }

    /**
     * 我的免单
     */
    public function freeOrder()
    {

    }

    /**
     * 我的拼团
     */
    public function groupBuy()
    {

    }

    /**
     * 收货地址列表
     */
//    public function address()
//    {
//        $user_id = $this->input->get('user_id');
//        $address= new Address();
//        $address->lists($user_id);
//    }

    /**
     * 商品收藏
     */
    public function collects()
    {
        $user_id = $this->input->get('user_id');
        $data = $this->db->select()->get_where('goods_collect', array('user_id' => $user_id));
        if (!empty($data->result_array())) {
            $this->success($data);
        } else {
            $this->failed();
        }

    }

    public function forMe()
    {

    }
}