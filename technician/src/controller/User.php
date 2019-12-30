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
		/*发送验证码*/
		public function code(){
			global $_W, $_GPC;
			mload()->model('sms');
			$data['phone'] = getvar('phone');
			$loginForm = new \validate\Deliveryer($data);
			$form_error = $loginForm->scene('code')->valid();
			if($form_error['errno'] > 0){
				
				$msg = explode(',',$form_error['message']);
				jsonReturn(43,$msg[0]);	
				
			}
			$mobile = $data['phone'];
			/*-COPY-*/
			$sql = 'DELETE FROM ' . tablename('uni_verifycode') . ' WHERE `createtime`<' . (TIMESTAMP - 1800);
			pdo_query($sql);

			$sql = 'SELECT * FROM ' . tablename('uni_verifycode') . ' WHERE `receiver`=:receiver AND `uniacid`=:uniacid';
			$pars = array();
			$pars[':receiver'] = $mobile;
			$pars[':uniacid'] = $this->uniacid;
			$row = pdo_fetch($sql, $pars);
			$record = array();
			if(!empty($row)) {
				if($row['total'] >= 5) {
					// exit('您的操作过于频繁,请稍后再试');
					jsonReturn(10,'您的操作过于频繁,请稍后再试');
				}
				$code = $row['verifycode'];
				$record['total'] = $row['total'] + 1;
			} else {
				$code = random(6, true);
				$record['uniacid'] = $this->uniacid;
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
		/**/
		//忘记密码
		public function password(){
			global $_W,$_GPC;
			$data = [
				'phone'	=>	getvar('phone'),
				'pass'	=>	getvar('pass'),
				'code'	=>	getvar('code')
			];
			$loginForm = new \validate\Deliveryer($data);
			
			$form_error = $loginForm->scene('password')->valid();
			if($form_error['errno'] > 0){
				$msg = explode(',',$form_error['message']);
				jsonReturn(43,$msg[0]);	
			}
			$status = check_verifycode($data['phone'], $data['code']);
			if (!$status) {
				jsonReturn(43,'短信验证码错误');
			}
			$m_deliveryer = new \model\Deliveryer($this->uniacid);
			$res = $m_deliveryer->RetrievePassword($data);
			if(empty($res)) jsonReturn(1,'修改失败');
			jsonReturn(0,'密码修改成功');
			
		}
		
	}