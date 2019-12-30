<?php
	/*Desc:商户端公共函数
	**Author:sz
	**Date:2019/11/4
	**Time:11:59
	*/
	//加载函数 
	function busclassLoad($class){
		$path = str_replace('\\', DIRECTORY_SEPARATOR, $class);
		$file =RHINFO_SERVICE_PATH . 'inc/business/src/'.$path. '.php';
		if(file_exists($file) && is_file($file)){
			require_once $file;
			
		}
		
	}
	