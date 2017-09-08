<?php
/**
 * Created by PhpStorm.
 * User: Hua
 * Date: 2017/9/7
 * Time: 15:56
 */

defined('BASEPATH') OR exit('No direct script access allowed');



if ( ! function_exists('check_mobile')) {

    /**
     * 检查手机号码格式
     *
     * @param $mobile
     * @return bool
     */
    function check_mobile($mobile){
        if(preg_match('/1[34578]\d{9}$/',$mobile))
            return true;
        return false;
    }
}