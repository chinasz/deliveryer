<?php
	/*Desc:控制器基类
	**Author:sz
	**Date:2019/11/4
	**Time:13:32
	*/
	namespace controller;
	class Controller {
		public $uniacid;
		public function __construct(){
			global $_W;
			$this->uniacid = $_W['uniaccount']['uniacid'];
			
		}
		
	}