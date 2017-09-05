<?php
/**
 * 收藏商品管理控制器
 *
 * Created by PhpStorm.
 * User: Hua
 * Date: 2017/9/3
 * Time: 11:12
 */

class Collects extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * 列表
     */
    public function lists()
    {
        $user_id         = $this->input->get('user_id');

        /**
         * 获取收藏商品 ID
         */
        $goods_ids  = $this->db->select('goods_id')
                                ->order_by('collect_id', 'DESC')
                                ->get_where('goods_collect', array('user_id' => $user_id));

        /**
         * 拼接处理商品 ID
         */
        $ids = '';
        foreach ($goods_ids->result_array() as $subArray) {
            foreach ($subArray as $id) {
                $ids .= $id . ',';
            }
        }
        $goods_ids = rtrim($ids, ',');

        /**
         * 获取收藏商品信息
         */
        $goods_info = $this->db->select('goods_id,goods_name,prom_price,shop_price,prom')
                                ->from('goods')
                                ->where('goods_id in (' . $goods_ids . ')')
                                ->get()
                                ->result_array();

        if (!empty($goods_info)) {
            $this->success($goods_info);
        } else {
            $this->failed();
        }
    }

    /**
     * 保存
     */
    public function store()
    {
        $user_id        = $this->input->get('user_id');
        $goods_id       = $this->input->get('goods_id');
        $data = array(
            'user_id'       => $user_id,
            'goods_id'      => $goods_id,
            'add_time'      => time()
        );

        if ($this->db->insert('goods_collect', $data)) {
            $this->success('', '商品收藏成功');
        } else {
            $this->failed('商品收藏失败');
        }
    }

    /**
     * 删除
     */
    public function destroy()
    {
        $user_id    = $this->input->get('user_id');
        $goods_id   = $this->input->get('goods_id');
        $this->db->delete('goods_collect', array('goods_id' => $goods_id, 'user_id'=>$user_id));
        $this->success('取消收藏成功');
    }

}