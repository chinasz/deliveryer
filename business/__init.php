<?php
	/*Desc:商户端接口入口文件
	**Author:sz
	**Date:2019/11/4
	**Time:11:55
	*/
	// header('content-type:application/json;charset=utf-8');
	defined('IN_IA') or exit('Access Denied');

	global $_W,$_GPC;	

	mload()->func('api');
	mload()->func('inter');
	mload()->func('business');

	strip_gpc($_GPC, $type = 'g');

	$ctrl = ucfirst(trim($_GET['ctrl']));

	$act = trim($_GET['act']);

	// $base_path = RHINFO_SERVICE_PATH . 'inc/inter/';

	// $file_path = $base_path.$act.'.act.php';

	// 接口验签

	// if(empty($_GET['sign'])) jsonReturn(24,'签名错误');

	// $api_check = api_check_sign($_GET,$_GET['sign']);

	

	// if(!$api_check) jsonReturn(25,'签名错误');

	//

	//加载文件


	spl_autoload_register('busclassLoad');

	$controller_name = '\\controller\\'.$ctrl;

	if(class_exists($controller_name)){

		$controller = new $controller_name;

		if(method_exists($controller,$act)){

			$controller->$act();

			exit();

		}else{

			$controller->__empty();

		}

	}
