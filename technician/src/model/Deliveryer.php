<?php
	/*Desc:技师模型
	**Author:sz
	**Date:2019/11/07
	**Time:16:09
	*/
	namespace model;
	class Deliveryer extends Model{
		protected $tableName = 'rhinfo_service_deliveryer';

        protected $primaryKey = 'id';

		protected $uniacid;
		
		protected $sex = [1=>'男',2=>'女'];
		
		public function __construct($uniacid){
			parent::__construct();
			$this->uniacid = $uniacid;
			
		}
		//技师登录
		public function deliveryer_login($phone,$pass){
			$salt = $this->query->from($this->tableName)
								->where(['uniacid'=>$this->uniacid,'mobile'=>$phone])
								->getcolumn('salt');
			if(empty($salt)) jsonReturn(1,'用户不存在');
			$password = md5(md5($salt . trim($pass)) . $salt);
			
			$deliveryer=$this->query->from($this->tableName)
								->where(['mobile'=>$phone,'password'=>$password])
								->get();
			return $deliveryer;
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
		
		//新增技师
		public function newDeliveryer($data){
			$new = [
				'uniacid'	=>	$this->uniacid,
				'nickname'	=>	'技师'.random(8,true),
				'avatar'	=>	'',
				'mobile'	=>	$data['phone'],
				'title'		=>	$data['real'],
				'sex'		=>	$this->sex[$data['sex']],
				'age'		=>	intval($data['year']),
				'cid'		=>	$data['cid'],
				'salt' => random(6),
				'token' => random(32),
				'addtime' => TIMESTAMP,
			];
			$new['password'] = md5(md5($new['salt'] . $data['pass']) . $new['salt']);
			$res = pdo_insert($this->tableName,$new);
			
			return $res;
		}
		//技师类型
		public function deliveryerType($deliveryer){
			$sids = pdo_fetchall('select sid from ' . tablename('rhinfo_service_store_deliveryer') . ' where uniacid = :uniacid and deliveryer_id = :deliveryer_id and (sid = 0 or (delivery_type = 1 and sid > 0))', array(':uniacid' => $this->uniacid, ':deliveryer_id' => $deliveryer), 'sid');
			$sids = array_unique(array_keys($sids));
			
			if(empty($sids)) return false;
			
			$deliveryer_type = 1; //平台技师.
			
			if(!in_array(0, $sids)) {
				$deliveryer_type = 2; //店内技师
			} else {
				if(count($sids) > 1) {
					$deliveryer_type = 3;
				}
			}
			
			return [$deliveryer_type,$sids];		
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