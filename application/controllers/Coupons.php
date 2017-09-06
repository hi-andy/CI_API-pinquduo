<?php
/**
 * Created by PhpStorm.
 * User: Hua
 * Date: 2017/9/5
 * Time: 11:49
 */

class Coupons extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * 优惠券列表：用于商品详情页展示，商家发放的优惠券。
     *
     */
    public function lists()
    {
        $store_id   = $this->input->get('store_id'); // 店铺/商家ID
        $coupons    = $this->db->select('id,
                                        name,
                                        type,
                                        money,
                                        condition,
                                        use_start_time,
                                        use_end_time,
                                        limit_take,
                                        store_id,
                                        use_crowd
                                        ')
                                ->from('coupon')
                                ->where(array('store_id'=>$store_id,'status'=>1))
                                ->where('use_start_time <= ' . time())
                                ->where('use_end_time > ' . time())
                                ->where('send_num < createnum')
                                ->get()
                                ->result_array();
        if (!empty($coupons)) {
            $this->success( $coupons);
        } else {
            $this->failed();
        }
    }

    /**
     * 领取优惠券
     */
    public function get_coupon()
    {
        $user_id    = $this->input->get('user_id');
        $coupon_id  = $this->input->get('coupon_id');
        $store_id   = $this->input->get('store_id');

        /**
         * 优惠券的领取限制
         */
        $coupon_info = $this->db->select('limit_take')->get_where('coupon', 'id=' . $coupon_id)->row_array();
        if ($coupon_info['limit_take'] > 0) {
            $get_num = $this->db->where('cid=' . $coupon_id . ' AND uid=' . $user_id)->count_all_results('coupon_list');
            if ($get_num >= $coupon_info['limit_take']) {
                $this->failed('领取失败，您已领取过此优惠券！');
            }
        }

        $data['uid']        = $user_id;
        $data['cid']        = $coupon_id;
        $data['store_id']   = $store_id;
        $data['send_time']  = time(); // 领取时间

        /**
         * 事务处理：领取逻辑
         */
        $this->db->trans_begin();

        $this->db->insert('coupon_list', $data);
        $this->db->set('send_num','send_num+1', false)->where('id=' . $coupon_id)->update('coupon');

        if ($this->db->trans_status() !== FALSE) {
            $this->db->trans_commit();
            $this->success();
        } else {
            $this->db->trans_rollback();
            $this->failed('领取失败！');
        }
    }

    /**
     * 我的优惠券：用户优惠券列表
     */
    public function user_lists()
    {
        $user_id = $this->input->get('user_id');

        /**
         * 获取用户领取的优惠券数据
         */
        $coupon_list    = $this->db->select("
                                        is_use,
                                        uid,
                                        id user_coupon_id,
                                        cid,
                                        store_id,
                                        (select store_name from tp_merchant where id=store_id) as store_name
                                        ")
                                ->where(array('uid'=>$user_id))
                                ->get('coupon_list')
                                ->result_array();
        /**
         * 拼接处理优惠券 ID
         */
        $cids = '';
        foreach ($coupon_list as $subArr) {
            $cids .= $subArr['cid'] . ',';
        }
        $cids = rtrim($cids, ',');

        /**
         * 获取优惠券详细信息
         */
        $coupons = $this->db->select('id, name, type, money, condition, use_start_time, use_end_time')
                            ->where('id in (' . $cids . ')')
                            ->get('coupon')
                            ->result_array();

        /**
         * 合并数据
         */
        for ($i=0; $i<count($coupon_list); $i++) {
            for ($j=0; $j<count($coupons); $j++) {
                if ($coupon_list[$i]['cid'] == $coupons[$j]['id']) {
                    $coupon_list[$i]['name'] = $coupons[$j]['name'];
                    $coupon_list[$i]['type'] = $coupons[$j]['type'];
                    $coupon_list[$i]['money'] = $coupons[$j]['money'];
                    $coupon_list[$i]['condition'] = $coupons[$j]['condition'];
                    $coupon_list[$i]['use_start_time'] = $coupons[$j]['use_start_time'];
                    $coupon_list[$i]['use_end_time'] = $coupons[$j]['use_end_time'];
                }
            }
        }

        if (!empty($coupon_list)) {
            $this->success($coupon_list);
        } else {
            $this->failed();
        }
    }

    /**
     * 使用优惠券
     *
     * 更新用户优惠券表 coupon_list：is_use
     * 更新优惠券表 coupon : use_num
     */
    public function use_coupon()
    {
        $user_id        = $this->input->get('user_id');
        $user_coupon_id = $this->input->get('user_coupon_id');
        $coupon_id      = $this->input->get('coupon_id');

        /**
         * 事务处理：使用逻辑
         */
        $this->db->trans_begin();

        $this->db->set('is_use', 1)->where('id='. $user_coupon_id .' AND uid='.$user_id . ' AND cid='.$coupon_id)->update('coupon_list');
        $this->db->set('use_num','use_num+1', false)->where('id=' . $coupon_id)->update('coupon');

        if ($this->db->trans_status() !== FALSE) {
            $this->db->trans_commit();
            $this->success();
        } else {
            $this->db->trans_rollback();
            $this->failed();
        }
    }

    /**
     * 删除优惠券：用户删除个人已领取的优惠券。
     */
    public function destroy()
    {
        $id = $this->input->get('id'); // 优惠券 ID
        $user_id = $this->input->get('user_id');
        $this->db->delete('coupon_list', array('id'=>$id, 'uid'=>$user_id));
        $this->success();
    }
}