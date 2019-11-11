<?php 
/*Desc:用户基类
**Author：sz
**Date：2019/10/24
**Time:15:57
*/
    namespace controller;
	class Auth{
		
		public $uniacid;
		public $uid;
		
		public function __construct(){
			global $_W,$_GPC;
			$this->uniacid = $_W['uniaccount']['uniacid'];
			$token = new \service\UserToken;
			$m_Member = new \model\Member($this->uniacid);
			// $header = getHeader();
			$header['token'] = getvar('token');
			if(empty($header['token'])) jsonReturn(44,'请登录',[]);
			$v = $token->veriftytoken($header['token']);
			if(!$v) jsonReturn(44,'登录状态已过期,请重新登录',[]);	
			$this->uid = cache_load($_GPC['token'])['uid'];
			$member = $m_Member->getMemeberInfo($this->uid,['*']);
			if(empty($member)) jsonReturn(44,'token 错误',[]);
				
			$_W['member'] = $member;
		}
		public function __empty(){
			
			jsonReturn(43,'url 错误');
			
		}
	}