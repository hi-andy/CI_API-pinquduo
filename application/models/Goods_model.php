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
	function getGoodsList($condition = array(), $limit = PHP_INT_MAX, $offset = 0, $orderBy = 'sales DESC')
	{
		$goods = $this->db
			->select('goods_id,goods_name,addtime,market_price,shop_price,original_img as original,prom,prom_price,is_special,list_img as original_img')
			->where($condition)
			->limit($limit, $offset-1)
			->order_by($orderBy)
			->get('goods')
			->result_array();

		$count = $this->db
			->where($condition)
			->count_all_results('goods');

		$goodsIds = array_column($goods,'goods_id');
		$goods = $this->getActivityIcon($goods, $goodsIds);

		$my_controller = new MY_Controller();
		$goods = $my_controller->listPageData($count, $goods, $limit);

		$goods = $this->goodsImage($goods);

		return $goods;
	}

	/*
	 *  获取商品独有的活动Icon
	 * */
	public function getActivityIcon($goods = array(),$goodsIds)
	{

		$goodsIcons = $this->db
			->select('goods_id,src')
			->where_in('goods_id',$goodsIds)
			->get('promote_icon')
			->result_array();

		foreach ($goods as $k=>$v)
		{
			foreach ($goodsIcons as $k1=>$v1)
			{
				if($v['goods_id'] == $v1['goods_id'])
				{
					$goods[$k]['icon_src'] = $v1['src'];
					break;
				}
			}
		}

		return $goods;
	}

	/* *
	 * 兼容新老版本的类目主图和幅图的显示问题
	 * */
	public function goodsImage($goods)
	{
		foreach ($goods['items'] as &$v) {
			if (!empty($v['original_img'])) {
				$imgArray = getimagesize($v['original_img']);
				if ((int)$imgArray[0] == (int)$imgArray[1]){
					$temp = $v['original'];
					$v['original'] = $v['original_img'];  //正方形
					$v['original_img'] = $temp; 	      //长方形
				}
			} else {
				$v['original_img'] = empty($v['original_img'])?$v['original']:$v['original_img'];
			}
		}

		return $goods;
	}
}