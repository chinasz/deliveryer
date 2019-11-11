<?php
	/*Desc:技师端公共函数
	**Author:sz
	**Date:2019/11/07
	**Time:15:29
	*/
	function techClassLoader($class){
		
		$path = str_replace('\\', DIRECTORY_SEPARATOR, $class);
		$file =RHINFO_SERVICE_PATH . 'inc/technician/src/'.$path. '.php';
		if(file_exists($file) && is_file($file)){
			require_once $file;
			
		}
		
	}
	
	//技师订单状态
	function deliveryer_order_types(){
		$order_type = [
			['id'=>3,'title'=>'待抢单'],
			['id'=>7,'title'=>'待服务'],
			['id'=>4,'title'=>'服务中'],
			['id'=>5,'title'=>'已完成'],
			['id'=>6,'title'=>'未完成']
		];
		return $order_type;
	}