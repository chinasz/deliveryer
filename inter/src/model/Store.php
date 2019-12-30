<?php
	/*Desc:商户模型
	**Author:sz
	**Date:2019/10/22
	**Time:15:37
	*/
	namespace model;
	class Store extends \We7Table{
		
		protected $tableName = 'rhinfo_service_store';
        protected $primaryKey = 'id';
		protected $uniacid;
		public function __construct($uniacid){
			parent::__construct();
			$this->uniacid = $uniacid;	
		}
		//商户所有分类
		public function getAllCategory($field=['*']){
			
			$Category = $this->query
							->from('rhinfo_service_store_category')
							->select($field)
							->where(['uniacid'=>$this->uniacid,'status'=>1])
							->orderby('displayorder','desc')
							->getall();
			if(!empty($Category)){
				$Category = array_map(function($v){
					if(!empty($v['thumb'])){
						$v['thumb'] = tomedia($v['thumb']);
					}
					return $v;
				},$Category);
				
			}
			return $Category;
		}
		//商户分类及分类下的商户
		public function getCategoryAndStore($page='',$storelimit='',$categoryfield = ['id','title'],$storefield=['id','title','logo']){
			$categorys = $this->getAllCategory($categoryfield);
			if(!empty($categorys)){
				foreach($categorys as $k=>$category){
					if(empty($page) || empty($storelimit)){
						$tem = $this->query->from($this->tableName)->select($storefield)->where(['cid like'=>'%|'.$category['id'].'|%','status'=>1,'uniacid'=>$this->uniacid]);
					}else{
						$tem = $this->query->from($this->tableName)->select($storefield)->where(['cid like'=>'%|'.$category['id'].'|%','status'=>1,'uniacid'=>$this->uniacid])->limit($page,$storelimit);
						
					}
					$category['stores'] = $tem->getall();
					
					if(!empty($category['stores'])){
						$category['stores'] = array_map(function($v){
							$v['logo'] = tomedia($v['logo']);
							
							return $v;
						},$category['stores']);
					}
					$categorys[$k] = $category;
					
					//分类店铺为空不显示
					if(empty($category['stores'])) unset($categorys[$k]);
					/**/
				}
				
			}
			
			return $categorys;
	
		}		
		
		/*
		**Desc:新增店员
		**Author:sz
		**Date:2019/10/24
		**Time:11:48
		**@param $data = ['phone'=>'手机号','title'=>'店员姓名','uid'=>'用户id'];
		*/
		public function addClerk($data){
			$insert = [
				'uniacid'	=>	$this->uniacid,
				'mobile'	=>	$data['phone'],
				'title'		=>	$data['title'],
				'openid'	=>	'',
				'nickname'	=>	'店员'.random(8,true),
				'avatar'	=>	'',
				'salt'		=>	random(6),
				'addtime' => TIMESTAMP,
				'uid'	  =>	$data['uid']
			];
			$pass = random(8);
			$insert['password'] = md5(md5($insert['salt'] . $pass) . $insert['salt']);
			$clerk = pdo_insert('ims_rhinfo_service_clerk',$insert);
			if(!empty($clerk)){
				
				$clerk_id = pdo_insertid();
				
				cache_write('entry_manage_pass'.$clerk_id, ['pass'=>$pass,'express_in'=>10*24*60*60,'create_time'=>time()]);
				
				return $clerk_id;
			}
			return false;
			
		}
		//商户入驻
		public function addStoreEntry($data){
			global $_W;
			
			/*创建店员*/
			$clerk = [
				'phone'	=>	$data['phone'],
				'title'	=>	$data['name'],
				'uid'	=>	$data['uid'],
			];
			$clerk_id = $this->addClerk($clerk);
			/*-end 创建店员-*/
			
			//创建店铺
			
			$config_store = $_W['inter_config']['store'];
			
			$insert = array(
				'uid'	=>	$data['uid'],
				'uniacid' => $this->uniacid,
				'telephone' => $data['phone'],
				'title' => trim($data['title']),
				'logo'	=>	'',
				'content'=>	'',
				'address'=> $data['address'],
				'delivery_mode'=> !empty($config_store['delivery']['delivery_mode']) ? $config_store['delivery']['delivery_mode'] : 1,
				'delivery_fee_mode' => 1,
				'delivery_price' => !empty($config_store['delivery']['delivery_fee'])?$config_store['delivery']['delivery_fee']:0,
				'business_hours' => iserializer(array()), 
				'addtime' => TIMESTAMP,
				'push_token' => random(32), 
				'self_audit_comment' => intval($config_store['settle']['self_audit_comment']),
				'addtype'	=>	2,
				'status'	=>	2,
				'qualification' => iserializer(['business' => [], 'service' => ['thumb' => trim($data['file'])] ])
			);
			
			if (empty($config_store['delivery'])) {
				$config_store['delivery'] = array('delivery_fee_mode' => 1, 'delivery_price' => 0);
			}


			if (!empty($config_store['delivery']['delivery_fee_mode']) && $config_store['delivery']['delivery_fee_mode'] == 2) {
				$insert['delivery_fee_mode'] = 2;
				$insert['delivery_price'] = iserializer($insert['delivery_price']);
			} else {
				$insert['delivery_fee_mode'] = 1;
				$insert['delivery_price'] = floatval($insert['delivery_price']);
			}
			$delivery_times = $this->query->from('rhinfo_service_text')->where(['uniacid'=>$this->uniacid,'name'=>'takeout_delivery_time','agentid'=>$_W['agentid']])->get();
			$delivery_times = iunserializer($delivery_times['takeout_delivery_time']);
			$insert['delivery_times'] = iserializer($delivery_times);
			$insert['cid'] = "|1|";
			pdo_insert('rhinfo_service_store', $insert);
			$sid = pdo_insertid();
			/*-end 创建店铺-*/
			
			/*-店铺参数-*/
			$config_settle = $config_store['settle'];
			$store_account = [
				'uniacid' => $this->uniacid,
				'sid' => $sid,
				'fee_takeout' => iserializer($config_settle['fee_takeout']),
				'fee_selfDelivery' => iserializer($config_settle['fee_selfDelivery']),
				'fee_instore' => iserializer($config_settle['fee_instore']), 
				'fee_paybill' => iserializer($config_settle['fee_paybill']),
				'fee_limit' => $config_settle['get_cash_fee_limit'],
				'fee_rate' => $config_settle['get_cash_fee_rate'],
				'fee_min' => $config_settle['get_cash_fee_min'],
				'fee_max' => $config_settle['get_cash_fee_max']
			];
			pdo_insert('rhinfo_service_store_account', $store_account);
			/*-end 店铺参数-*/
			
			/* 创建店铺管理员 */
			$store_clerk = [
				'uniacid'	=>	$this->uniacid,
				'sid'		=>	$sid,
				'clerk_id'	=>	$clerk_id,
				'role' 		=>  'manager',
				'addtime'	=>	TIMESTAMP	
			];
			$res = pdo_insert('rhinfo_service_store_clerk',$store_clerk);
			/*-end-*/
			
			return $res;
			
		}
		
		//查询用户有没有提交入驻申请
		public function sureStoreEntry($uid,$field=['*']){
			
			$entry=$this->query->from($this->tableName)->where(['uid'=>$uid,'uniacid'=>$this->uniacid])
						->select($field)
						->get();
			return $entry;	
		}
		//用户是否收藏店铺
		public function isFavorite($uid,$sid){
			$is_favorite = $this->query->from('rhinfo_service_store_favorite')
								->where(['sid'=>$sid,'uid'=>$uid])
								->get();
			if(!empty($is_favorite)){
				return true;
			}
			return false;
		}
		//指定商户信息
		public function storeInfo($sid,$field=['*']){

			$store = $this->query->from($this->tableName)->where(['id'=>$sid,'uniacid'=>$this->uniacid,'status'=>1])->select($field)->get();
			if(!empty($store['business_hours'])) $store['business_hours'] = iunserializer($store['business_hours']);
			// return $this->query->getLastQuery();
			return $store;
		}
		//收藏店铺||取消收藏
		public function favoriteStore($uid,$sid){
			$is_favorite = $this->isFavorite($uid,$sid);
			if($is_favorite) {
				return pdo_delete('rhinfo_service_store_favorite',['uniacid'=>$this->uniacid,'uid'=>$uid,'sid'=>$sid]);
			}
			$data = [
				'uniacid' =>	$this->uniacid,
				'uid'	  =>	$uid,
				'sid'	  =>	$sid,
				'addtime' =>	TIMESTAMP
			];
			return pdo_insert('rhinfo_service_store_favorite',$data);

		}
		//店铺服务时间
		public function storeDeliveryTimes($sid){
			$field = ['id', 'delivery_reserve_days', 'delivery_within_days', 'delivery_time', 'delivery_times', 'delivery_fee_mode', 'delivery_price','delivery_time_orders'];
			$store = $this->query->from($this->tableName)
								->where(['id'=>$sid,'uniacid'=>$this->uniacid,'status'=>1])
								->field($field)
								->get();
			$days = [];
			$totaytime = strtotime(date('Y-m-d'));
			if (0 < $store['delivery_reserve_days']) {
				$days[] = date('m-d', $totaytime + ($store['delivery_reserve_days'] * 86400));
			}else if (0 < $store['delivery_within_days']) {
				$i = 0;
				while ($i <= $store['delivery_within_days']) {
					$days[] = date('m-d', $totaytime + ($i * 86400));
					++$i;
				}
			}else {
				$days[] = date('m-d');
			}
			
			$times = iunserializer($store['delivery_times']);
			$timestamp = array();
			if (!empty($times)) {
				foreach ($times as $key => &$row ) 	{
					if (empty($row['status'])) 	{
						unset($times[$key]);
						continue;
					}
					if ($store['delivery_fee_mode'] == 1) {
						$row['delivery_price'] = $store['delivery_price'] + $row['fee'];
						$row['delivery_price_cn'] = $row['delivery_price'] . '元附加费';
					}else {
						$row['delivery_price'] = $store['delivery_price'] + $row['fee'];
						$row['delivery_price_cn'] = '附加费' . $row['delivery_price'] . '元起';
					}
					$end = explode(':', $row['end']);
					$row['timestamp'] = mktime($end[0], $end[1]);
					$timestamp[$key] = $row['timestamp'];
				}
			}else {
				$start = mktime(8, 0);
				$end = mktime(22, 0);
				$i = $start;
				while ($i < $end) {
					if ($store['delivery_fee_mode'] == 1) {
						$store['delivery_price_cn'] = $store['delivery_price'] . '元附加费';
					}else {
						$store['delivery_price_cn'] = '附加费' . $store['delivery_price'] . '元起';
					}
					$times[] = array('start' => date('H:i', $i), 'end' => date('H:i', $i + 1800), 'timestamp' => $i + 1800, 'fee' => 0, 'delivery_price' => $store['delivery_price'], 'delivery_price_cn' => $store['delivery_price_cn']);
					$timestamp[] = $i + 1800;
					$i += 1800;
				}
			}		
			$data = array('days' => $days, 'times' => $times, 'timestamp' => $timestamp, 'updatetime' => strtotime(date('Y-m-d')) + 86400, 'reserve' => (0 < $store['delivery_reserve_days'] ? 1 : 0));
			return $data;
		}
		//获取商户支付方式
		public function storePayment($sid){
			$pay_types =pay_types();
			$store = $this->storeInfo($sid);
			$payment = iunserializer($store['payment']);
			$payment_list = [];
			foreach($payment as $v){
				if(!empty($pay_types[$v])){
					$payment_list[$v] = $pay_types[$v]['text'];
				}else{
					
					continue;
				}
			}
			
			return $payment_list;
		}
		//某分类下的商户 
		public function storeByCategory($cid,$page=0,$field=['*']){
			$limit = 10;
			$start = $page>0?($page-1)*$limit:0;
			$stores = $this->query->from($this->tableName)
						->where(['cid like'=>'%|'.$cid.'|%','status'=>1,'uniacid'=>$this->uniacid])
						->limit($start,$limit)
						->orderby('displayorder','desc')
						->getall();	
			if(!empty($stores)){
				foreach($stores as &$store){
					$store['logo'] = tomedia($store['logo']);	
				}
			}
			return  $stores;
		}
	}