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

		$page     = $this->input->get_post('page');     // 页数
		$pagesize = $this->input->get_post('pagesize'); // 返回数据数量
		$version  = $this->input->get_post('version');  // 版本号

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
		$recommendList = $this->goods_model->getGoodsList($condition, $pagesize, $page, 'is_recommend desc,sort asc');


		$this->success(array('goodsList'=>$recommendList, 'activity'=>$activity, 'ad'=>$banner, 'cat'=>$icon));
	}

	/*
	 *  海淘首页
	 */
	public function haiTao()
	{
		$this->load->model('goods_model');
		$this->load->model('class_model');

		$page     = $this->input->get_post('page');     // 页数
		$pagesize = $this->input->get_post('pagesize'); // 返回数据数量
		$version  = $this->input->get_post('version');  // 版本号
		//头部分类
		$directory = $this->db
			->get('haitao_style')
			->result_array();

		//中间分类
		$directory2 = array('id' => 0, 'name' => '海淘专区', 'logo' => 'https://cdn2.pinquduo.cn/Public/upload/category/img_international@3x.png');
		$directory2['cat2'] = $this->db
			->select('id, name, img, logo')
			->where('`parent_id` = 0')
			->limit('4')
			->get('haitao')
			->result_array();

		$catArray = $this->db
			->select('id, name, parent_id')
			->where('`parent_id` != 0')
			->get('haitao')
			->result_array();
		
		// 获取完整的分类数组
		$directory2 = $this->class_model->getNextLevel($directory2, $catArray, $directory2['cat2'], 'id', 'parent_id', 'cat2', 'cat3', 1);

		$conditon = '`show_type`=0 and is_special=1 and `is_on_sale`=1 and is_audit=1 and `is_show`=1 and haitao_cat != 65 ';
		$goodsList = $this->goods_model->getGoodsList($conditon, $pagesize, $page, 'is_recommend desc,sort asc');
		$json = array('goods' => $goodsList, 'directory' => $directory, 'directory2' => $directory2);
		$this->success($json);
	}

	/* *
	 * 获取99专区首页数据
	 * */
	public function jiuJiu()
	{
		$this->load->model('goods_model');
		$this->load->model('banner_model');

		$page     = $this->input->get_post('page');     // 页数
		$pagesize = $this->input->get_post('pagesize'); // 返回数据数量
		$version  = $this->input->get_post('version');  // 版本号
		//头部banner
		$banner = $this->banner_model->bannerList(array('ad_name', 'ad_code', 'type'),'pid = 2 and `enabled`=1');
		//中间小分区展示
		$exclusive = $this->db
			->get('exclusive')
			->result_array();
		// 获取相对应的商品列表
		$condition = '`show_type`=0 and is_special = 4 and `is_on_sale`=1 and `is_show`=1 and `is_audit`=1 ';
		$field = 'goods_id,goods_name,addtime,market_price,shop_price,original_img as original,prom,prom_price,is_special,list_img as original_img';
		$order = 'sales DESC';

		$goodsList = $this->goods_model->getGoodsList($condition, $field, $pagesize, $page, $order);
		$json = array('banner' => $banner, 'banner2' => $exclusive, 'goods' => $goodsList);
		$this->success($json);
	}

	/**
	 * 点击99专区分类获取对应的商品列表
	 */
	public function jiuJiuCategory()
	{
		$this->load->model('goods_model');
		$this->load->model('banner_model');

		$id       = $this->input->get_post('id');
		$page     = $this->input->get_post('page');     // 页数
		$pagesize = $this->input->get_post('pagesize'); // 返回数据数量
		$version  = $this->input->get_post('version');  // 版本号
		// 展示头部的banner
		$banner = $this->db
			->select('banner')
			->where("id = {$id}")
			->get('exclusive')
			->result_array();
		//获取对应的商品列表
		$codition = '`show_type`=0 and `is_special`=4  and `is_show`=1 and `is_on_sale`=1 and `is_audit`=1 and `exclusive_cat` = ' . $id ;
		$order = 'sales desc';
		$goodsList = $this->goods_model->getGoodsList($codition, $pagesize, $page, $order);
		
		$json = array('banner' => $banner, 'goods' => $goodsList);

		$this->success($json);
	}

	/**
	 * 获取探索页面分类数据
	 */
	public function xplore()
	{
		$this->load->database();
		$this->load->model('class_model');

		$category = $this->db
			->select('id, name, logo, level')
			->where('`parent_id` = 0 and `id` != 10044')
			->order_by('sort_order asc')
			->get('goods_category')
			->result_array();
		
		$cat_ids = array_column($category, 'id');
		$cat_ids = join($cat_ids,',');

		$cat2_arr = $this->db
			->select('id, name, logo, level, parent_id')
			->where("`parent_id` in ({$cat_ids})")
			->order_by('sort_order asc')
			->get('goods_category')
			->result_array();


		$cat2_ids = array_column($cat2_arr, 'id');
		$cat2_ids = join($cat2_ids,',');
		$cat3_arr = $this->db
			->select('id, name, logo, level, parent_id')
			->where("`parent_id` in ({$cat2_ids})")
			->order_by('sort_order asc')
			->get('goods_category')
			->result_array();

		var_dump(count($cat3_arr));
	}
}