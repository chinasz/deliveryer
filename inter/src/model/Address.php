<?php

	/*Desc:用户收货地址模型

	**Author:sz

	**Date:2019/10/22

	**Time:18:30

	*/

	namespace model;

	class Address extends \We7Table{

		protected $tableName = 'rhinfo_service_address';

        protected $primaryKey = 'id';

		protected $uniacid;

		public function __construct($uniacid){

			parent::__construct();

			$this->uniacid = $uniacid;	

		}

		//用户所有收货地址

		public function getAllAddress($uid,$field=['*']){

			$addresss = $this->query->from($this->tableName)->where(['uniacid'=>$this->uniacid,'uid'=>$uid,'type'=>1])->orderby('is_default','desc')->getall();

			

			return $addresss;

		}
		//新增
		public function newAddress($uid,$data){
			$add = [
				'uniacid' 	=>	$this->uniacid,
				'uid'		=>	$uid,
				'name'		=>	$data['name'],
				'mobile'	=>	$data['phone'],
				'address'	=>	$data['address'],
				'location_x'=>	$data['location_x'],
				'location_y'=>	$data['location_y'],
			];
			return pdo_insert($this->tableName,$add);

		}
		//编辑
		public function editAddress($aid,$data){
			
			if(!empty($data) && !empty($aid)){
				$data = [
					'name'		=>	$data['name'],
					'mobile'	=>	$data['phone'],
					'address'	=>	$data['address'],
					'location_x'=>	$data['location_x'],
					'location_y'=>	$data['location_y'],	
				];
				return pdo_update($this->tableName,$data,['id'=>$aid]);
				
			}
			
			return false;
			
		}
		//删除
		public function delAddress($uid,$aid){
			$address = $this->query->where(['uid'=>$uid,'id'=>$aid])->get();

			if(empty($address)) jsonReturn(1,'无效的地址');

			return pdo_delete($this->tableName,['id'=>$aid]);

		}
		
		/*用户可用的收货地址
		**@param $sid 商户id,$uid 用户id
		**@return array$address 地址信息
		*/
		public function memberAvailableAddress($sid,$uid){
			$address = [];
			$store = $this->query->from('rhinfo_service_store')
								 ->select(['location_y', 'location_x', 'delivery_fee_mode', 'delivery_areas', 'serve_radius', 'not_in_serve_radius'])
								 ->where(['id'=>$sid,'uniacid'=>$this->uniacid])
								 ->get();
			$adds = $this->getAllAddress($uid);
			if(!empty($adds)){
				foreach($adds as $add){
					
					$c = $this->checkMemberAddress($sid,[$add['location_y'],$add['location_x']]);
					if($c){
						$address = $add;
						break;
					}
					
				}
			}
			return $address;
				
		}
		
		/*收货地址是否可用
		**@param int $sid 商户id,array $lnglat ['location_y','location_x']
		**@return boolean
		**
		*/
		public function checkMemberAddress($sid,$lnglat){
			$flag = false;
			$store = $this->query->from('rhinfo_service_store')
						 ->select(['location_y', 'location_x', 'delivery_fee_mode', 'delivery_areas', 'serve_radius', 'not_in_serve_radius'])
						 ->where(['id'=>$sid,'uniacid'=>$this->uniacid])
						 ->get();
			if (($store['delivery_fee_mode'] == 1) || ($store['delivery_fee_mode'] == 2)){
				if (!($store['not_in_serve_radius']) && (0 < $store['serve_radius'])) {
					$dist = distanceBetween($lnglat[0], $lnglat[1], $store['location_y'], $store['location_x']);
					if ($dist <= $store['serve_radius'] * 1000){
						$flag = true;
					}
				}else{
					$flag = true;
				}
			}
			
			return $flag;
		}
		
	}