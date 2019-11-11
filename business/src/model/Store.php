<?php
	/*Desc:店铺模型
	**Author:sz
	**Date:2019/11/04
	**Time:16:17
	*/
	namespace model;
	class Store extends Model{
		
		protected $tableName = 'rhinfo_service_store'; 
		
		protected $uniacid;

		public function __construct($uniacid){

			parent::__construct();

			$this->uniacid = $uniacid;	

		}
		
		//店铺信息
		public function storeInfo($sid){
			
			return $this->query->from($this->tableName)
						->where(['uniacid'=>$this->uniacid,'id'=>$sid,'status'=>1])
						->get();
		}
		
		//修改店铺信息
		public function storeUpdate($sid,$data){
			
			$update = [
				'title'	=>	$data['title'],
				'telephone'=>$data['mobile'],
				'business_hours'=>iserializer($data['time']),
			];
			
			if(!empty($data['content'])) $update['content'] = trim($data['content']);
			if(!empty($data['logo'])) $update['logo'] = $data['logo'];
			
			return pdo_update($this->tableName,$data,['uniacid'=>$this->uniacid,'id'=>$sid]);
		}
		//店铺用户
		public function storeMember($sid){
			$field = ['sm.uid','sm.first_order_time','sm.last_order_time','sm.success_num','sm.success_price','sm.cancel_num','sm.cancel_price','sm.success_first_time','sm.success_last_time','sm.cancel_first_time','sm.cancel_last_time','m.nickname'];
			$member=$this->query->from('rhinfo_service_store_members','sm')
						->innerjoin('rhinfo_service_members','m')
						->on(['sm.uid'=>'m.id'])
						->select($field)
						->where(['sid'=>$sid,'uniacid'=>$this->uniacid])
						->getall();
			return $member;
		}
	}