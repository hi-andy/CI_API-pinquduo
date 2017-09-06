<?php
class MY_Controller extends CI_Controller {
	public function __construct() {
		parent::__construct();

		// 统一入口控制代码 Donghu

		$this->setCORSHeader();
	}

	/**
	 * 跨域请求头（目前是为了微信商城访问）   
	 */
	public function setCORSHeader() {
		header("Access-Control-Allow-Origin:*");
	}

    /**
     * 客户端返回成功
     *
     * @param array $data
     */
	protected function success(Array $data=array())
    {
        echo json_encode(array('status'=>'1','result'=>$data));
        exit;
    }

    /**
     * 客户端返回失败
     */
    protected function failed($msg='获取失败')
    {
        echo json_encode(array('status'=>'-1', 'msg'=>$msg));
        exit;
    }

	/**
	 * 数据分页处理模型
	 * @param int $total
	 * @param array $items
	 * @return array
	 * author Fox
	 */
	function listPageData($total=0,$items=array(),$pagesize=null) {
		if(empty($pagesize)){
			$pagesize = (int)$this->input->get_post('pagesize');
		}
		$totalpage = ceil($total/$pagesize);
		$currentpage = (int)$this->input->get_post('page');
		if( $currentpage==0){
			$currentpage = 1;
		}
		if(empty($items))
		{
			$items=array();
		}
		if(empty($total))
		{
			$total = 0;
		}
		$currentpage = max(1, $currentpage);
		$currentpage = min($currentpage, $totalpage);
		$nextpage = min($currentpage+1, $totalpage);
		return  compact('total', 'totalpage', 'pagesize', 'currentpage', 'nextpage', 'items');
	}
}