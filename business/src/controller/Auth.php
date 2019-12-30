<?php
 /*Desc:用户基类
 **Author:sz
 **Date:2019/11/4
 **Time:13:29
 */
	namespace controller;
	class Auth extends Controller{
		
		public function __construct(){
			global $_W,$_GPC;
			parent::__construct();
			$token = new \service\UserToken;
			$m_Clerk = new \model\Clerk($this->uniacid);
			// $header = getHeader();
			$header['token'] = getvar('token');
			if(empty($header['token'])) jsonReturn(44,'请登录',[]);
			$v = $token->veriftytoken($header['token']);
			if(!$v) jsonReturn(44,'登录状态已过期,请重新登录',[]);	
			$this->uid = cache_load($_GPC['token'])['uid'];
			$store = $m_Clerk->clerk2sid($this->uid);
			$this->store_id = $store['sid'];

			$m_Store = new \model\Store($this->uniacid);
			$store = $m_Store->storeInfo($this->store_id);
			if(empty($store)) jsonReturn(1,'您的店铺被封禁');
			$_W['business'] = $store;
			$_W['role'] = 'clerk';
			$_W['role_cn'] = '店员:'.$store['title'];
		}
		
		
	}
	 