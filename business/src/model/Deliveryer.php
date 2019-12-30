<?php
	/*Desc:商户端技师模型
	**Author:sz
	**Date:2019/11/20
	**Time:16:10
	*/
	namespace model;
	class Deliveryer extends Model{
		protected $tableName = 'rhinfo_service_deliveryer';

        protected $primaryKey = 'id';

		protected $uniacid;

		public function __construct($uniacid){

			parent::__construct();

			$this->uniacid = $uniacid;	
		}
		
		//查询一个技师
		public function getDeliveryerOne($deliveryer_id,$field=['d.nickname','d.sex','d.age','d.credit2','d.work_status','c.title as cname']){
			$deliveryer = $this->query->from($this->tableName,'d')
								->innerjoin('rhinfo_service_store_category','c')
								->on(['d.cid'=>'c.id'])
								->where(['d.id'=>$deliveryer_id])
								->select($field)
								->get();
			if(!empty($deliveryer)){
				if(!empty($deliveryer['avatar'])){
					$deliveryer['avatar'] = tomedia($deliveryer['avatar']);
				}
				
			}
			return $deliveryer;
		}
		
		//技师账户变动
		/*
		**@param $money 变动金额 $deliveryer_id 技师id $trade_type(1为入账 2为申请提现) $extra(未知) $type 变动类型(inc + dec -) $remark(备注)
		**@return 
		**deliveryer.mod.php line 121
		*/
		public function changeDeliveryerAccount($money,$deliveryer_id,$extra,$trade_type = 1,$type="inc",$remark=""){
			$deliveryer = $this->getDeliveryerOne($deliveryer_id);
			if(empty($deliveryer)) return false;
			$op = $type =='inc'?'+':'-';
			$sql = "update ".tablename($this->tableName)." set credit2 = credit2 ".$op.$money." where uniacid = ".$this->uniacid." and id = ".$deliveryer_id;
			
			pdo_query($sql);
			//
			$now_amount = $type =='inc'?($deliveryer['credit2']+$money):($deliveryer['credit2']-$money);
			$account_log = [
				'uniacid'	=>	$this->uniacid,
				'deliveryer_id'=>$deliveryer_id,
				'order_type'=>	'order',
				'trade_type'=>	$trade_type,
				'extra'		=>	$extra,
				'fee'		=>	$op=="+"?"":$op.$money,
				'amount'	=>	$now_amount,
				'addtime'	=>	TIMESTAMP,
				'remark'	=>	$remark
			];
			pdo_insert('rhinfo_service_deliveryer_current_log',$account_log);
			
			return true;
		}
		
		
	}