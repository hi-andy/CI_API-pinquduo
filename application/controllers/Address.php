<?php
/**
 * 地址管理控制器
 *
 * Created by PhpStorm.
 * User: Hua
 * Date: 2017/9/3
 * Time: 11:06
 */

class Address extends MY_Controller
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
        $id         = $this->input->get('user_id');
        $address    = $this->db->select('consignee,mobile,address_base,address,is_default')
                                ->get_where('user_address', array('user_id' => $id))
                                ->result_array();
        if (!empty($address)) {
            $this->success($address);
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
        $consignee      = $this->input->get('consignee');
        $mobile         = $this->input->get('mobile');
        $address_base   = $this->input->get('address_base');
        $address        = $this->input->get('address');
        $is_default     = $this->input->get('is_default');
        $data = array(
            'user_id'       => $user_id,
            'consignee'     => $consignee,
            'mobile'        => $mobile,
            'address_base'  => $address_base,
            'address'       => $address,
            'is_default'    => $is_default
        );

        if ($this->db->insert('user_address', $data)) {
            $this->success();
        } else {
            $this->failed('收货地址添加失败');
        }
    }

    /**
     * 更新
     */
    public function update()
    {
        $id             = $this->input->get('address_id');
        $consignee      = $this->input->get('consignee');
        $mobile         = $this->input->get('mobile');
        $address_base   = $this->input->get('address_base');
        $address        = $this->input->get('address');
        $is_default     = $this->input->get('is_default');
        $data = array(
            'consignee' => $consignee,
            'mobile' => $mobile,
            'address_base' => $address_base,
            'address' => $address,
            'is_default' => $is_default
        );

        if ($this->db->where(array('address_id'=>$id))->update('user_address', $data)) {
            $this->success();
        } else {
            $this->failed('收货地址修改失败');
        }
    }

    /**
     * 删除
     */
    public function destroy()
    {
        $id = $this->input->get('address_id');
        $this->db->delete('user_address', array('address_id' => $id));
        $this->success();
    }
}