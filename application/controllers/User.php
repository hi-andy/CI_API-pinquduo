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
    public function orderLists()
    {
        $status = $this->input->get('status');
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

    public function forMe()
    {

    }
}