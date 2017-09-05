<?php

class Goods_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->load->database();
	}

	/*
	 * 获取商品列表
	 *
	 * */
	function getGoodsList($condition = array(), $field, $limit = PHP_INT_MAX, $offset = 0, $orderBy = 'sales DESC')
	{
		$goods = $this->db
			->select($field)
			->where($condition)
			->limit($limit, $offset)
			->order_by($orderBy)
			->get('goods')
			->result_array();

		return $goods;
	}
}