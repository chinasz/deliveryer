<?php
	/*Desc:订单模型
	**Author:sz
	**Date:2019/10/22
	**Time:11:42
	*/
	namespace model;
	class Order extends \We7Table{
	
		protected $tableName = 'rhinfo_service_order';
        protected $primaryKey = 'id';
		protected $uniacid;
		public function __construct($uniacid){
			parent::__construct();
			$this->uniacid = $uniacid;
			
		}
		/*Desc:获取用户订单
		**Auhor:sz
		**Date:2019/10/22
		**Time:11:44
		*/
		public function getorder($uid,$type = 0,$page=1){
			$page = $page <1?1:$page;
			$order_status = order_status();
			$where = ['o.uid'=>$uid,'o.uniacid'=>$this->uniacid];
			if(!empty($type)) $where = array_merge($where,['o.status'=>$type]);
			$orders = $this->query->from($this->tableName,'o')->leftjoin('rhinfo_service_store','s')
						->on(['o.sid'=>'s.id'])
						->select(['o.*','s.title','s.logo','s.delivery_mode'])
						->where($where)
						->orderby(['status'=>'desc','addtime'=>'desc'])
						->page($page,10)
						->getall();
			if(!empty($orders)){
				foreach($orders as $k=>$order){
					$order['data'] = iunserializer($order['data']);
					$status_index = $order['status'];
					$order['status_cn'] = $order_status[$status_index]['text'];
					$order['goods'] = $this->query->from('ims_rhinfo_service_order_stat')->where(['uniacid'=>$this->uniacid,'oid'=>$order['id']])->getall();
					$orders[$k] = $order;
				}
			}
			return $orders;
		
		}
		//用户一条订单
		public function getOrderByKey($oid,$uid,$field=['*']){
			
			$order = $this->query->from($this->tableName)
						->select($field)
						->where(['id'=>$oid,'uid'=>$uid,'uniacid'=>$this->uniacid])
						->get();
			// var_dump($this->query->getLastQuery());die;
			return $order;
		}
	}