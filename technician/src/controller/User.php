<?php
	/*Desc:
	**Author:sz
	**Date:2019/11/07
	**Time:16:03
	*/
	namespace controller;
	class User extends Controller{
		
		public function __empty(){
			
			jsonReturn(43,'url 错误');
		}
		//技师登录
		public function login(){
			$data = [
				'phone'	=>	getvar('phone'),
				'pass'	=>	getvar('pass'),
			];
			$validate = new \validate\Deliveryer($data);
			$error = $validate->scene('login')->valid();
			if($error['errno'] > 0){
				
				$msg = explode(',',$error['message']);
				jsonReturn(43,$msg[0]);	
			}
			$m_deliveryer = new \model\Deliveryer($this->uniacid);
			$deliveryer = $m_deliveryer->deliveryer_login($data['phone'],$data['pass']);
			if(empty($deliveryer)) jsonReturn(1,'用户名或密码错误');
			$userToken = new \service\UserToken();
			$utoken = $userToken->maketoken($deliveryer['id']);
			jsonReturn(0,'',['token'=>$utoken]);
		}
		//技师注册
		public function register(){
			$data = [
				'phone'	=>	getvar('phone'),
				'pass'	=>	getvar('pass'),
				'check'	=>	getvar('check'),
				'real'	=>	getvar('real'),
				'sex'	=>	getvar('sex'),
				'year'	=>	getvar('year'),
				'cid'	=>	getvar('cid')
			];
			$validate = new \validate\Deliveryer($data);
			$error = $validate->scene('register')->valid();
			if($error['errno'] > 0){
				$msg = explode(',',$error['message']);
				jsonReturn(43,$msg[0]);	
			}
			$m_category = new \model\StoreCategory($this->uniacid);
			$cate = $m_category->storeOneCategory($data['cid'],['id']);
			if(empty($cate)) jsonReturn(1,'服务类别不合法');
			
			$deliveryer = pdo_get('rhinfo_service_deliveryer', array('uniacid' => $this->uniacid, 'mobile' => $data['phone']));
			if(!empty($deliveryer)) jsonReturn(1,'此手机号已注册, 请直接登录');
			$m_deliveryer = new \model\Deliveryer($this->uniacid);
			$res = $m_deliveryer->newDeliveryer($data);
			if(empty($res)){
				jsonReturn(1,'服务繁忙,请重试');
			}
			jsonReturn(0,'注册成功');
		}
	}