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
}