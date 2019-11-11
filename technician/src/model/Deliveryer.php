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
	}