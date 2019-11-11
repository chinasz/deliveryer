<?php
	/*Desc:
	**Author:sz
	**Date:2019/11/06
	**Time:13:08
	*/
	namespace controller;
	class Manager extends Auth{
		
		//修改密码
		public function password(){
			$data = [
				'pass'	=>	getvar('pass'),
				'newpass'	=>	getvar('new'),
				'checkpass'	=>	getvar('check'),
			];
			$validate = new \validate\Business($data);
			
			$error = $validate->scene('pass')->valid();
			if($error['errno'] > 0){
				$msg = explode(',',$error['message']);
				jsonReturn(43,$msg[0]);	
			}
			$salt = pdo_getcolumn('rhinfo_service_clerk',['id'=>$this->uid,'uniacid'],'salt');
			$password = md5(md5($salt . $data['pass']).$salt);
			$is_exists = pdo_get('rhinfo_service_clerk',['uid'=>$this->uid,'uniacid'=>$this->uniacid,'password'=>$password]);
			if(empty($is_exists)) jsonReturn(1,'原密码错误');
			$m_Clerk = new \model\Clerk($this->uniacid);
			
			$res = $m_Clerk->clerkEditPass($this->uid,$data['newpass']);
			if(empty($res)) jsonReturn(1,'服务繁忙,请重试');
			
			jsonReturn(0,'修改成功');	
		}
		
		
		
	}