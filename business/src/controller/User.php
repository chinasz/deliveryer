<?php
	/*Desc:商户端
	**Author:sz
	**Date:2019/11/4
	**Time:13:50
	*/
	namespace controller;
	class User extends Controller{
		public function __empty(){
			
			jsonReturn();
		}
		//商户登录
		public function login(){
			$data = [
				'phone'	=>	getvar('phone'),
				'pass'	=>	getvar('pass')
			];
			$validate = new \validate\Business($data);
			$error = $validate->scene('login')->valid();
			
			if($error['errno'] > 0){
				
				$msg = explode(',',$error['message']);
				jsonReturn(43,$msg[0]);	
			}
			$m_Clerk = new \model\Clerk($this->uniacid);
			$clerk = $m_Clerk->manager_login($data['phone'],$data['pass']);
			if(empty($clerk)) jsonReturn(1,'用户名或密码错误');
			$userToken = new \service\UserToken();
			$utoken = $userToken->maketoken($clerk['id']);
			jsonReturn(0,'',['token'=>$utoken]);
		}
		
		//忘记密码
		public function test(){
			
			//pass
			
		}
		//注销登录
		public function logout(){
			$token = getvar('token');
			if(empty($token)) jsonReturn(1,'参数错误');
			$userToken = new \service\UserToken();
			$verifty = $userToken->veriftytoken($token);
			if($verifty){
				cache_delete($token);
			}
			jsonReturn(0,'退出成功');
		}
		
	}