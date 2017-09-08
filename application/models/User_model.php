<?php
/**
 * Created by PhpStorm.
 * User: Hua
 * Date: 2017/9/7
 * Time: 11:32
 */

class User_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * @param $type 查询类型：0 用户ID, 1 手机号码，2 第三方 ID
     * @param $id
     * @param $unionid
     * @return mixed
     */
    public function user_info($id, $type, $unionid='')
    {
        switch ($type) {
            case 0 :
                $condition['user_id'] = $id;
                break;
            case 1 :
                $condition['mobile'] = $id;
                break;
            case 2 :
                if (!empty($unionid)) {
                    $condition['unionid'] = $unionid;
                } else {
                    $condition['openid'] = $id;
                }
                break;
        }

        $userInfo = $this->db->where($condition)->order_by('user_id asc')->get('users')->row_array();
        return $userInfo;
    }
}