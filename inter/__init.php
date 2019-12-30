<?php

	/*Desc:路由重定向

	**Author:sz

	**Date:2019/10/18

	**Time:17:00

	*/
	// header('content-type:application/json;charset=utf-8');
	defined('IN_IA') or exit('Access Denied');

	global $_W,$_GPC;

	mload()->func('api');

	mload()->func('inter');

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
	// $sysset = pdo_getcolumn('rhinfo_service_config',['uniacid'=>$this->uniacid],'sysset');
	
	// if(!empty($sysset)){
		// $sysset = iunserializer($sysset);
		// $_W['sys'] =  $sysset;
		// $_W['delivery']['sys'] = $sysset['delivery'];
	// }else{
		// $_W['delivery']['sys'] = '';
	// }
	$_W['inter_config'] = get_system_config();
	$_W['rhinfo_service']['config'] = get_system_config();
	$_W['agentid'] = 0;

	//加载文件

	

	spl_autoload_register('sclassLoad');

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

		

	

	// if(!is_file($file_path) || !file_exists($file_path)){

		// jsonReturn([404]);

	// }else{

		

		// // require_once $file_path;

		

	// }