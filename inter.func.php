<?php
/*Desc:公共方法
**Author:sz
**Date:2019/10/21
**Time:16:05
*/
	function jsonReturn($code,$msg='',$data=[]){
		
		echo json_encode(['code'=>$code,'msg'=>$msg,'data'=>$data]); 
		exit();
	}
	function sclassLoad($class){
		$path = str_replace('\\', DIRECTORY_SEPARATOR, $class);
		$file =RHINFO_SERVICE_PATH . 'inc/inter/src/'.$path. '.php';
		if(file_exists($file) && is_file($file)){
			require_once $file;
			
		}
		
	}
	//获取http头
	function getHeader() {
		$headers = array(); 
		foreach ($_SERVER as $key => $value) {
			if ('HTTP_' == substr($key, 0, 5)) { 
				$headers[str_replace('_', '-', substr($key, 5))] = $value; 
			}
			if (isset($_SERVER['PHP_AUTH_DIGEST'])) { 
				$header['AUTHORIZATION'] = $_SERVER['PHP_AUTH_DIGEST']; 
			} elseif (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) { 
				$header['AUTHORIZATION'] = base64_encode($_SERVER['PHP_AUTH_USER'] . ':' . $_SERVER['PHP_AUTH_PW']); 
			} 
			if (isset($_SERVER['CONTENT_LENGTH'])) { 
				$header['CONTENT-LENGTH'] = $_SERVER['CONTENT_LENGTH']; 
			} 
			if (isset($_SERVER['CONTENT_TYPE'])) { 
				$header['CONTENT-TYPE'] = $_SERVER['CONTENT_TYPE']; 
			}
		}
		return $headers;
	}
	function getvar($key){
		global $_GPC;
		
		return empty($_GPC[$key])?'':$_GPC[$key];
	}
	
	if (!(function_exists('get_system_config'))) {
		function get_system_config($key = '') {
			global $_W;
			$_W['uniacid'] = intval($_W['uniacid']);
			$config = pdo_get('rhinfo_service_config', array('uniacid' => $_W['uniacid']), array('sysset', 'pluginset', 'id'));
			if (empty($config['id'])) {
				$init_config = array('uniacid' => $_W['uniacid']);
				pdo_insert('rhinfo_service_config', $init_config);
				return array();
			}
			
			$sysset = iunserializer($config['sysset']);
			if (empty($key)) {
				return $sysset;
			}
			$keys = explode('.', $key);
			$counts = count($keys);
			if ($counts == 1) 
			{
				return $sysset[$key];
			}
			if ($counts == 2) 
			{
				return $sysset[$keys[0]][$keys[1]];
			}
			if ($counts == 3) 
			{
				return $sysset[$keys[0]][$keys[1]][$keys[2]];
			}
		}
	}
	//图片上传
	function supload($file){
		load()->func('file');
		return file_upload($file, 'image', '');

	}
	
	
	function pay_types() {
		$pay_types = array(
			'' => '未支付',
			'alipay' => array(
				'css' => 'label label-info',
				'text' => '支付宝',
			),
			'wechat' => array(
				'css' => 'label label-success',
				'text' => '微信支付',
			),
			'yimafu' => array(
				'css' => 'label label-success',
				'text' => '一码付',
			),
			'credit' => array(
				'css' => 'label label-warning',
				'text' => '余额支付',
			),
			'delivery' => array(
				'css' => 'label label-primary',
				'text' => '货到付款',
			),
			'cash' => array(
				'css' => 'label label-primary',
				'text' => '现金支付',
			),
			'qianfan' => array(
				'css' => 'label label-primary',
				'text' => 'APP支付',
			),
			'majia' => array(
				'css' => 'label label-primary',
				'text' => 'APP支付',
			),
			'peerpay' => array(
				'css' => 'label label-primary',
				'text' => '找人代付',
			),
			'eleme' => array(
				'css' => 'label label-primary',
				'text' => '饿了么支付',
			),
			'maituan' => array(
				'css' => 'label label-primary',
				'text' => '美团支付',
			),
		);
		return $pay_types;
	}
	/**
	 * 计算两个坐标之间的距离(米)
	 * @param float $fP1Lat 起点(纬度)
	 * @param float $fP1Lon 起点(经度)
	 * @param float $fP2Lat 终点(纬度)
	 * @param float $fP2Lon 终点(经度)
	 * @return int
	 */
	function distanceBetween2($longitude1, $latitude1, $longitude2, $latitude2){
		$radLat1 = radian2($latitude1);
		$radLat2 = radian2($latitude2);
		$a = radian2($latitude1) - radian2($latitude2);
		$b = radian2($longitude1) - radian2($longitude2);
		$s = 2 * asin(sqrt(pow(sin($a / 2), 2) + (cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2))));
		$s = $s * 6378.1369999999997;
		$s = round($s * 10000) / 10000;
		return $s * 1000;
	}

	function radian2($d){
		return ($d * 3.1415926535898002) / 180;
	}
//订单状态
	function order_status() {
		$data = array(
			'0' => array(
				'css' => '',
				'text' => '所有',
				'color' => ''
			),
			'1' => array(
				'css' => 'label label-default',
				'text' => '待确认',
				'color' => '',
			),
			'2' => array(
				'css' => 'label label-info',
				'text' => '处理中',
				'color' => 'color-primary'
			),
			'3' => array(
				'css' => 'label label-warning',
				'text' => '待服务',
				'color' => 'color-warning'
			),
			'4' => array(
				'css' => 'label label-warning',
				'text' => '服务中',
				'color' => 'color-warning'
			),
			'5' => array(
				'css' => 'label label-success',
				'text' => '已完成',
				'color' => 'color-success'
			),
			'6' => array(
				'css' => 'label label-danger',
				'text' => '已取消',
				'color' => 'color-danger'
			)
		);
		return $data;
	}
	//订单评价状态
	function order_comment_status() {
		$status = array(
			'0' => array(
				'css' => 'color-primary',
				'text' => '待审核',
			),
			'1' => array(
				'css' => 'color-success',
				'text' => '审核通过',
			),
			'2' => array(
				'css' => 'color-danger',
				'text' => '审核未通过',
			),
		);
		return $status;
	}
	//订单类型
	function order_types() {
		$data = array(
			'1' => array(
				'css' => 'label label-success',
				'text' => '上门',
				'color' => 'color-success'
			),
			'2' => array(
				'css' => 'label label-danger',
				'text' => '自提',
				'color' => 'color-danger'
			),
			'3' => array(
				'css' => 'label label-warning',
				'text' => '店内',
				'color' => 'color-info'
			),
			'4' => array(
				'css' => 'label label-info',
				'text' => '预定',
				'color' => 'color-info'
			),
		);
		return $data;
	}
	//取消订单理由
	function order_cancel_types($role = 'clerker') {
		$types = array(
			'clerker' => array(
				'fakeOrder' => '用户信息不符',
				'foodSoldOut' => '服务已经售完',
				'restaurantClosed' => '商家已经打烊',
				'distanceTooFar' => '超出服务范围',
				'restaurantTooBusy' => '商家现在太忙',
				'forceRejectOrder' => '用户申请取消',
				'deliveryFault' => '服务出现问题',
				'notSatisfiedDeliveryRequirement' => '不满足服务要求',
			),
			'manager' => array(
				'fakeOrder' => '用户信息不符',
				'foodSoldOut' => '服务已经售完',
				'restaurantClosed' => '商家已经打烊',
				'distanceTooFar' => '超出服务范围',
				'restaurantTooBusy' => '商家现在太忙',
				'forceRejectOrder' => '用户申请取消',
				'deliveryFault' => '服务出现问题',
				'notSatisfiedDeliveryRequirement' => '不满足服务要求',
			),
			'consumer' => array(),
		);
		return $types[$role];
	}
	//订单服务状态
	function order_delivery_status() {
		$data = array(
			'0' => array(
				'css' => '',
				'text' => '',
				'color' => ''
			),
			'3' => array(
				'css' => 'label label-warning',
				'text' => '待服务',
				'color' => 'color-warning'
			),
			'4' => array(
				'css' => 'label label-warning',
				'text' => '服务中',
				'color' => 'color-warning'
			),
			'5' => array(
				'css' => 'label label-success',
				'text' => '服务完成',
				'color' => 'color-success'
			),
			'6' => array(
				'css' => 'label label-danger',
				'text' => '服务失败',
				'color' => 'color-danger'
			),
			'7' => array(
				'css' => 'label label-danger',
				'text' => '待确认',
				'color' => 'color-danger'
			)
		);
		return $data;
	}