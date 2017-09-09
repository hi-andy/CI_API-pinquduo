<?php
/**
 * Created by PhpStorm.
 * User: Hua
 * Date: 2017/9/9
 * Time: 09:42
 */

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