<?php
	/*Desc:用户操作
	**Author:sz
	**Date:2019/10/23
	**Time:10:45
	*/
	namespace controller;
	class Operation extends Auth{
		
		public function sureEntry(){
			global $_W,$_GPC;
			//是否提交申请
			$m_Store = new \model\Store($this->uniacid); 
			$store = $m_Store->sureStoreEntry($this->uid);
			$clerk_id = pdo_getcolumn("rhinfo_service_store_clerk",['sid'=>$store['id'],'uniacid'=>$this->uniacid],'clerk_id');
			$data = [];
			if(!empty($store)){
				switch(intval($store['status'])){
					case 1:
						$data['msg'] = '您的入驻审核已通过！';
						$pass = cache_load('entry_manage_pass'.$clerk_id);
						if(($pass['express_in']+$pass['create_time']) > time()){
							$data['pass'] = $pass['pass']; 
						}
						break;
					case 2:
						$data['msg'] = '您的申请正在审核中...';
						break;
					case 3:
						$data['msg'] = '您的申请未通过！';
						break;
				}
				jsonReturn(1,$data['msg'],$data);
			}
			jsonReturn(0,'');
		}
		
		//商家入驻
		public function merchantentry(){
			global $_W,$_GPC;
			
			if(empty($_FILES['image'])) jsonReturn(43,'请上传身份证照获营业执照');
			$image = supload($_FILES['image']);
			if(!$image['success']){
				jsonReturn(43,$image['message']);
			}

			$data = [
				'title' =>	getvar('title'),
				'name'	=>	getvar('name'),
				'phone'	=>	getvar('phone'),
				'address'=> getvar('address')
			];
			$validate = new \validate\Store($data);
			
			$form_error = $validate->scene('entry')->valid();
			if($form_error['errno'] > 0){
				$msg = explode(',',$form_error['message']);
				jsonReturn(43,$msg[0]);
			}
			$data['file'] = $image['path'];
			$data['uid']  = $this->uid;
			
			$m_Store = new \model\Store($this->uniacid);
			$store_config = $_W['inter_config']['store'];
			
			
			if($store_config['settle']['status'] != 1) {

				jsonReturn(1,'暂时不支持商家入驻');
				
			}
			
			$perm = pdo_get('rhinfo_service_perm_account', array('uniacid' => $this->uniacid));
			$max_store = empty($perm)?false:intval($perm['max_store']);
			if(!empty($max_store)){
				
				$now_store = pdo_fetchcolumn('select count(*) from ' . tablename('rhinfo_service_store') . ' where uniacid = :uniacid', array(':uniacid' => $this->uniacid));
				if($max_store <= $now_store){
					jsonReturn(1,'商家入驻量已超过上限,请联系管理员');
				}	
			}
			//手机号是否绑定
			$is_exist = pdo_fetchcolumn('select id from ' . tablename('rhinfo_service_clerk') . ' where uniacid = :uniacid and mobile = :mobile', array(':uniacid' => $this->uniacid, ':mobile' => $data['phone']));
			
			if(!empty($is_exist)) {
				jsonReturn(1,'该手机号已绑定其他店员, 请更换手机号');
			}
			//用户是否店员
			$is_clerk = pdo_fetchcolumn('select id from ' . tablename('rhinfo_service_clerk') . ' where uniacid = :uniacid and uid = :uid', array(':uniacid' => $this->uniacid, ':uid' => $this->uid)); 
			if(!empty($is_clerk)){
				jsonReturn(1,'该账号已绑定其他店员');	
			}
			//是否入驻
			$store = $m_Store->sureStoreEntry($this->uid);
			if(!empty($store)){
				jsonReturn(1,'您已经提交入驻申请!');
			}

			$res = $m_Store->addStoreEntry($data);
			if(empty($res)) jsonReturn(1,'提交失败请重试');
			
			jsonReturn(0,'申请已提交，等待审核');
		}
		//收藏店铺
		public function favorite(){
			$sid = getvar('id');
			$m_Store = new \model\Store($this->uniacid);
			$store = $m_Store->storeInfo($sid,['id']);
			if(empty($store)) jsonReturn(1,'店铺不存在或已被停用');

			$res = $m_Store->favoriteStore($this->uid,$sid);
			if(empty($res)){
				jsonReturn(1,'操作失败');
			}
			jsonReturn(0,'操作成功');

		}
		//新增收货地址
		public function newaddress(){
			$data = [
				'phone'	=>	getvar('phone'),
				'name'	=>	getvar('name'),
				'address'=> getvar('address'),
				'location_x'=>getvar('location_x'),
				'location_y'=>getvar('location_y')
			];
			$validate = new \validate\User($data);
			$form_error = $validate->scene('address')->valid();
			if($form_error['errno'] > 0){
				$msg = explode(',',$form_error['message']);
				jsonReturn(43,$msg[0]);
			}

			$m_Address = new \model\Address($this->uniacid);

			$res = $m_Address->newAddress($this->uid,$data);
			jsonReturn(0,'新增成功');
		}
		//编辑收货地址
		public function editAddress(){
			$aid = getvar('id');
			$m_Address = new \model\Address($this->uniacid);
			$address = pdo_get('rhinfo_service_address',['id'=>$aid,'uid'=>$this->uid],['realname','mobile','location_x','location_y','address']);
			$op = getvar('edit');
			if(empty($op)){
				$data = [
					'phone'	=>	getvar('phone'),
					'name'	=>	getvar('name'),
					'address'=> getvar('address'),
					'location_x'=>getvar('location_x'),
					'location_y'=>getvar('location_y')
				];
				$validate = new \validate\User($data);
				$form_error = $validate->scene('address')->valid();
				if($form_error['errno'] > 0){
					$msg = explode(',',$form_error['message']);
					jsonReturn(43,$msg[0]);
				}
				if(empty($address)) jsonReturn(1,'编辑地址不存在');
				if($address == $data) jsonReturn(1,'内容没有改变');

				$res = $m_Address->editAddress($aid,$data);
				jsonReturn(0,'编辑成功');
				
			}else{
				
				jsonReturn(0,'',$address);
				
			}
			
			
		}
		//删除收货地址
		public function delAddress(){
			
			$aid = getvar('id');
			
			$m_Address = new \model\Address($this->uniacid);
			
			$m_Address->delAddress($this->uid,$aid);
			jsonReturn(0,'删除成功');
		}
		//修改资料
		public function edit(){
			
			$data = [
				'name'	=>	getvar('name'),
				'phone'	=>	getvar('phone')
			];
			if(!empty($_FILES['img'])){
				$image = supload($_FILES['img']);
				if(!$image['success']){
					jsonReturn(43,$image['message']);
				}
				$data['img'] = $image['path'];
			}
			$validate = new \validate\User($data);
			$form_error = $validate->scene('edit')->valid();
			if($form_error['errno'] > 0){
				$msg = explode(',',$form_error['message']);
				jsonReturn(43,$msg[0]);
			}
			$m_Member = new \model\Member($this->uniacid);
			$m_Member->editMember($this->uid,$data);
			jsonReturn(0,'修改成功');
		}
		//设置默认地址
		public function setdefaultadd(){
			$aid = getvar('aid');
			$m_Address = new \model\Address($this->uniacid);
			$res = $m_Address->setDefaultAddress($this->uid,$aid);
			if($res){
				jsonReturn(0,'操作成功');
			}
			jsonReturn(1,'操作失败');
		}
		
	}