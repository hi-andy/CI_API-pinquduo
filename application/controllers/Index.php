<?PHP

class Index extends  MY_Controller
{
	/*
	 * APP首页
	 * 吴银海
	 * */
	public function home() {
		$this->load->model('banner_model');
		$this->load->model('goods_model');

		$page = $this->input->get('page');
		$pagesize = $this->input->get('pagesize');
		$version = $this->input->get('version');

		// banner
		$banner = $this->banner_model->bannerList(['ad_link', 'ad_name', 'ad_code', 'type'], ['pid' => 1, 'enabled'=> 1]);
		// icon
		$icon = $this->db
			->select(['id','cat_img','cat_name'])
			->where('id not in (8,9)')
			->get('group_category')
			->result_array();
		$icon[0]['id'] = 'http://wx.pinquduo.cn/likes.html';// 本来是iconid，后期换成H5页面，多以将地址放在该位置让前端获取跳转

		//活动模块
		$activity = null;

		// recom
		$condition = '`show_type`=0 and `is_show` = 1 and `is_on_sale` = 1 and `is_recommend`=1 and `is_special` in (0,1) and `is_audit`=1 ';
		$field = 'goods_id,goods_name,addtime,market_price,shop_price,original_img as original,prom,prom_price,is_special,list_img as original_img';
		$recommendList = $this->goods_model->getGoodsList($condition, $field, $page, $pagesize, 'is_recommend desc,sort asc');

		$json = array('status'=>1, 'msg'=>'获取成功', 'result'=>array('goodsList'=>$recommendList, 'activity'=>$activity, 'ad'=>$banner, 'cat'=>$icon));

		exit(json_encode($json));
	}

	/*
	 *  海淘首页
	 */
	public function haiTao()
	{
		$this->load->model('goods_model');

		$page = $this->input->get('page');
		$pagesize = $this->input->get('pagesize');
		$version = $this->input->get('version');

		$directory = $this->db
			->get('haitao_style')
			->result_array();
		var_dump($directory);

	}
}