<?php
	/*Desc:控制器基类
	**Author:sz
	**Date:2019/11/07
	**Time:15:48
	*/
	namespace controller;
	class Controller{
		public $uniacid;
		public function __construct(){
			global $_W;
			$this->uniacid = $_W['uniaccount']['uniacid'];
			$sysset = pdo_getcolumn('rhinfo_service_config',['uniacid'=>$this->uniacid],'sysset');
			
			if(!empty($sysset)){
				$sysset = iunserializer($sysset);
				$_W['sys'] =  $sysset;
				$_W['delivery']['sys'] = $sysset['delivery'];
			}else{
				$_W['delivery']['sys'] = '';
			}
			
		}
	}