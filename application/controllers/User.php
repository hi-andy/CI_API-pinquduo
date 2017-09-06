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
    public function user_center()
    {
        $user_id = $this->input->get('user_id');

        $data['daifahuo']   = $this->db->from('order')->where('`user_id` = ' . $user_id . ' and `the_raise` = 0 and (order_type = 2 or order_type = 14)')->count_all_results(); //　待发货
        $data['daishouhuo'] = $this->db->from('order')->where('`user_id` = ' . $user_id . ' and `the_raise` = 0 and (order_type = 3 or order_type = 15)')->count_all_results(); //　待收货
        $data['daifukuan']  = $this->db->from('order')->where('`user_id` = ' . $user_id . ' and `the_raise` = 0 and (order_type = 1 or order_type = 10)')->count_all_results(); //　待付款
        $data['refund']     = $this->db->from('order')->where('`user_id`=' . $user_id . ' and `the_raise` = 0 and (`order_type`=6 or `order_type`=7 or `order_type`=8 or `order_type`=9 or `order_type`=12 or `order_type`=13)')->count_all_results(); //　退款/售后
        $data['in_prom']    = $this->db->from('order')->where('`user_id`=' . $user_id . ' and `the_raise` = 0 and (order_type = 11 or order_type = 10)')->count_all_results(); //　拼团中

        $this->success($data);
    }

    /**
     * 订单
     */
    public function get_order_lists()
    {
        $user_id    = $this->input->get('user_id');
        $type       = $this->input->get('type') ?: 0; //0.全部 1.拼团中 2.待发货 3.待收货 4.待付款 5.已完成
        $page       = $this->input->get('page') ?: 1;
        $page_size  = $this->input->get('pagesiaze') ?: 20;
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
         * 获取订单信息
         */
        $order = $this->db->select('order_id, goods_id, order_status, shipping_status, pay_status, prom_id,
                                    order_amount, store_id, num, order_type')
                            ->where($condition)
                            ->order_by('order_id desc')
                            ->limit($page_size, $offset)
                            ->get('order')
                            ->result_array();

        /**
         * 拼接处理商品 ID 和店铺 ID
         */
        $order_ids = $store_ids = '';
        foreach ($order as $subArr) {
            $order_ids .= $subArr['order_id'] . ',';
            $store_ids .= $subArr['store_id'] . ',';
        }
        $order_ids = rtrim($order_ids, ',');
        $store_ids = rtrim($store_ids, ',');

        /**
         * 获取订单商品信息
         * goods_thumbnail original 商品缩略图
         */
        $order_goods = $this->db->select(' goods_id, goods_name, spec_key_name key_name, goods_thumbnail original ')
                                ->where('order_id in (' . $order_ids . ')')
                                ->get('order_goods')
                                ->result_array();
        /**
         * 获取店铺摘要信息
         */
        $store_info = $this->db->select('id,store_name,store_logo')
                                ->where('id in (' . $store_ids . ')')
                                ->get('merchant')
                                ->result_array();

        /**
         * 合并数据
         */
        for ($i = 0; $i < count($order); $i++) {

            for ($j = 0; $j < count($order_goods); $j++) {
                if ($order[$i]['goods_id'] == $order_goods[$j]['goods_id']) {
                    $order[$i]['goodsInfo'] = $order_goods[$j];
                }
            }

            for ($k = 0; $k < count($store_info); $k++) {
                if ($order[$i]['store_id'] == $store_info[$k]['id']) {
                    $order[$i]['goodsInfo']['store'] = $store_info[$k];
                }
            }
            $order[$i]['annotation'] = $this->formatOrderStatus($order[$i]['order_type']);
        }

        if (!empty($order)) {
            $this->success($order);
        } else {
            $this->failed();
        }
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

    /**
     * 格式化订单显示
     *
     * @param $type
     * @return string
     */
    private function formatOrderStatus($type)
    {
        switch ($type) {
            case 1 :
                $status = '待付款';
                break;
            case 2 :
                $status = '待发货';
                break;
            case 3 :
                $status = '待收货';
                break;
            case 4 :
                $status = '已完成';
                break;
            case 5 :
                $status = '已取消';
                break;
            case 6 :
                $status = '待换货';
                break;
            case 7 :
                $status = '已换货';
                break;
            case 8 :
                $status = '待退货';
                break;
            case 9 :
                $status = '已退货';
                break;
            case 10 :
                $status = '拼团中,未付款';
                break;
            case 11 :
                $status = '拼团中,已付款';
                break;
            case 12 :
                $status = '未成团,待退款';
                break;
            case 13 :
                $status = '未成团,已退款';
                break;
            case 14 :
                $status = '已成团,待发货';
                break;
            case 15 :
                $status = '已成团,待收货';
                break;
            case 16 :
                $status = '拒绝受理';
                break;
            default :
                $status = '订单状态异常';
                break;
        }
        return $status;
    }

}