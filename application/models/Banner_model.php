<?php

class Banner_model extends CI_Model
{
	/*
	 * 获取各类bnner
	 * $fields  筛选字段
	 * $condition 筛选条件
	 * */
	public function bannerList($fields, $condition)
	{
		$this->load->database();
		
		$bannerList = $this->db
			->select($fields)
			->where($condition)
			->get('ad')
			->result_array();

		return $bannerList;
	}
}