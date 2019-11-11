<?php
	/*Desc:技师
	**Author:sz
	**Date:2019/11/07
	**Time:18:25
	*/
	namespace controller;
	class Auth extends Controller{
		
		public function __construct(){
			global $_W,$_GPC;
			parent::__construct();
			$token = new \service\UserToken;
			$header['token'] = getvar('token');
			if(empty($header['token'])) jsonReturn(44,'请登录',[]);
			$v = $token->veriftytoken($header['token']);
			if(!$v) jsonReturn(44,'登录状态已过期,请重新登录',[]);	
			$this->uid = cache_load($_GPC['token'])['uid'];
			$m_Deliveryer = new \model\Deliveryer($this->uniacid);
			$deliveryer = $m_Deliveryer->deliveryerType($this->uid);
			if(empty($deliveryer)) jsonReturn(1,'请联系平台管理员或店铺管理员分配接单权限');
			//技师类型
			$_W['deliveryer']['deliveryer_type'] = $deliveryer[0];
			//技师店铺
			$_W['deliveryer']['deliveryer_store'] = $deliveryer[1];
		}
		
		public function __empty(){
			
		}
	}