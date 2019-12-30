<?php
	/*Desc:技师端商户模型
	**Author:sz
	**Date:2019/11/11
	**Time:14:25
	*/
	namespace model;
	class Store extends Model{
		
		protected $tableName = 'rhinfo_service_store';

        protected $primaryKey = 'id';

		protected $uniacid;
			
		public function __construct($uniacid){
			parent::__construct();
			$this->uniacid = $uniacid;
		}
		/*Desc:获取商户账户
		**@param $sid 商户id
		**@return Array
		*/
		public function getStoreAccountByKey($sid){
		
			$store=$this->query->from('rhinfo_service_store_account')
						->where(['uniacid'=>$this->uniacid,'id'=>$sid])
						->get();
			return empty($store)?[]:$store;
		}
		
		/*Desc:商户账户变动
		**@param $money 变动金额 $sid 商户id $extra(未知) $trade_type(1为入账 2为申请提现) $type 变动类型(inc + dec -) $remark(备注)
		**@return 
		**store.mod.php line 386
		*/
		public function changeStoreAccount($money,$sid,$extra,$trade_type = 1,$type="inc",$remark=""){
			$store = $this->getStoreAccountByKey($sid);
			if(empty($store)) return false;
			$op = $type =='inc'?'+':'-';
			$sql = "update ".tablename('rhinfo_service_store_account')." set amount = amount ".$op.$money." where uniacid = ".$this->uniacid." and sid = ".$sid;
			
			$now_amount = $type =='inc'?($store['amount']+$money):($store['amount']-$money);
			
			$log = [
				'uniacid'	=>	$this->uniacid,
				'sid'		=>	$sid,
				'trade_type'=>	$trade_type,
				'extra'		=>	$extra,
				'fee'		=>	$fee,
				'amount'	=>	$now_amount,
				'addtime'	=>	TIMESTAMP,
				'remark'	=>	$remark,
			];
			
			pdo_insert('rhinfo_service_store_current_log', $log);
			return true;
		}
		/*获取商户信息*/
		public function getStoreDetail($sid,$field=['*']){
			$store = $this->query->from($this->tableName)
				->select($field)	
				->where(['uniacid'=>$this->uniacid,'id'=>$sid])
				->get();
			return $store;	
		}
		
	}