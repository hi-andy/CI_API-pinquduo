<?php
/**
 * Created by PhpStorm.
 * User: admin_wu
 * Date: 2017/9/3
 * Time: 10:43
 */

class Goods extends MY_Controller{
//	public function
	public function xx() {
		$this->load->model('goods_model');

		$goodsList = $this->goods_model->getGoodsList(array(), 10);
		
		var_dump($goodsList);
	}
}