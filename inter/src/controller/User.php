<?php
	/*Desc:
	**Author:sz
	**Date:2019/10/21
	**Time:11:41
	*/
	namespace controller;
	class User{
		public $uniacid;
		
		public function __construct(){
			global $_W,$_GPC;
			$this->uniacid = $_W['uniaccount']['uniacid'];
			// $token = new \service\Token;
			// $header = getHeader();
			// if(empty($header['token'])) jsonReturn(44,'token 错误',[]);
			// $v = $token->veriftytoken($verfity);
			// if(!$v) jsonReturn(44,'token 错误',[]);	
		}
		
		public function __empty(){
			
			jsonReturn(43);
			
		}
		/*Desc:用户登录
		**Author:sz
		**Date:2019/10/21
		**Time:11:43
		*/
		public function login(){
			global $_GPC,$_W;	
			$data['login_type'] = getvar('login_type');
			load()->classs('validator');
			$m_Member = new \model\Member($this->uniacid);
			switch($data['login_type']){
				//密码登录
				case 1:
					$data['phone'] = getvar('phone');
					$data['pass']  = getvar('pass');
					$loginForm = new \validate\User($data);
					$form_error = $loginForm->scene('login')->valid();
					if($form_error['errno'] > 0){

						$msg = explode(',',$form_error['message']);
						jsonReturn(43,$msg[0]);
						
					}
					$user = $m_Member->loginByPass($data,['id']);
					if(empty($user)) jsonReturn(43,'用户名或密码错误');
					
					$userToken = new \service\UserToken();
					$utoken = $userToken->maketoken($user['id']);
					
					jsonReturn(0,'',['utoken'=>$utoken]);
					break;
				//微信登录
				case 2:
					//pass;
					break;
				default:
					$this->__empty();
			}
			
			
		}
		/*Desc:用户注册
		**Author:sz
		**Date:2019/10/21
		**Time:16:34
		*/
		public function register(){
			global $_W,$_GPC;
			$data = [
				'phone'	=>	getvar('phone'),
				'code'	=>	getvar('code'),
				'pass'	=>	getvar('pass')
			];
			$loginForm = new \validate\User($data);
			$form_error = $loginForm->scene('register')->valid();
			if($form_error['errno'] > 0){
				
				$msg = explode(',',$form_error['message']);
				jsonReturn(43,$msg[0]);	
			}
			
			// $status = check_verifycode($data['phone'], $data['code']);
			// if (!$status) {
				// jsonReturn(43,'短信验证码错误');
			// }
			
			$member = pdo_get('rhinfo_service_members', array('uniacid' => $this->uniacid, 'mobile' => $data['phone']));
			if (!empty($member)) {
				jsonReturn(43,'此手机号已注册, 请直接登录');
			}
			$m_Member = new \model\Member($this->uniacid);
			$res = $m_Member->newMember($data);
			
			if(empty($res)){
				jsonReturn(500,'服务器繁忙');
			}
			
			jsonReturn(0,'注册成功');
		}
		/*Desc:发送验证码
		**Author:sz
		**Date:2019/10/21
		**Time:16:50
		*/
		public function code(){
			global $_W, $_GPC;
			mload()->model('sms');
			$data['phone'] = getvar('phone');
			$loginForm = new \validate\User($data);
			$form_error = $loginForm->scene('code')->valid();
			if($form_error['errno'] > 0){
				
				$msg = explode(',',$form_error['message']);
				jsonReturn(43,$msg[0]);	
				
			}
			/*-COPY-*/
			$sql = 'DELETE FROM ' . tablename('uni_verifycode') . ' WHERE `createtime`<' . (TIMESTAMP - 1800);
			pdo_query($sql);

			$sql = 'SELECT * FROM ' . tablename('uni_verifycode') . ' WHERE `receiver`=:receiver AND `uniacid`=:uniacid';
			$pars = array();
			$pars[':receiver'] = $mobile;
			$pars[':uniacid'] = $_W['uniacid'];
			$row = pdo_fetch($sql, $pars);
			$record = array();
			if(!empty($row)) {
				if($row['total'] >= 5) {
					// exit('您的操作过于频繁,请稍后再试');
					jsonReturn(10,'您的操作过于频繁,请稍后再试',[]);
				}
				$code = $row['verifycode'];
				$record['total'] = $row['total'] + 1;
			} else {
				$code = random(6, true);
				$record['uniacid'] = $_W['uniacid'];
				$record['receiver'] = $mobile;
				$record['verifycode'] = $code;
				$record['total'] = 1;
				$record['createtime'] = TIMESTAMP;
			}
			if(!empty($row)) {
				pdo_update('uni_verifycode', $record, array('id' => $row['id']));
			} else {
				pdo_insert('uni_verifycode', $record);
			}
			$content = array(
				'code' => $code,
				//'product' => trim($_GPC['product'])
			);
			$config_sms = $_W['rhinfo_service']['config']['sms']['template'];
			$result = sms_send($config_sms['verify_code_tpl'], $mobile, $content, $sid);
			if(is_error($result)) {
				slog('alidayuSms', '阿里大鱼短信通知验证码', $content, $result['message']);
				// exit($result['message']);
				jsonReturn(500,$result['message'],[]);
			}
			// exit('success');
			jsonReturn(0,'',[]);
			/*-END COPY-*/
		}
		//修改密码
		public function password(){
			global $_W,$_GPC;
			$data = [
				'phone'	=>	getvar('phone'),
				'pass'	=>	getvar('pass'),
				'code'	=>	getvar('code')
			];
			$loginForm = new \validate\User($data);
			$form_error = $loginForm->scene('password')->valid();
			if($form_error['errno'] > 0){
				
				$msg = explode(',',$form_error['message']);
				jsonReturn(43,$msg[0]);	
				
			}
			$m_Member = new \model\Member($this->uniacid);
			$res = $m_Member->RetrievePassword($data);
			if(empty($res)) jsonReturn(1,'修改失败');
			jsonReturn(0,'密码修改成功');
		}
	}