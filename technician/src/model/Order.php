<?php
	/*Desc:订单模型
	**Author:sz
	**Date:219/1/08
	**Time:12:00
	*/
	namespace model;
	class Order extends Model{
		protected $tableName = 'rhinfo_service_order';
		protected $primaryKey = 'id';
		protected $uniacid;
		
		public function __construct($uniacid){
			parent::__construct();
			$this->uniacid = $uniacid;
			
		}
		
		//技师订单
		/*
		**@param $deliveryer 技师id $type 订单类型
		**@return 
		*/
		public function deliveryerOrder($deliveryer,$type){
			global $_W;
			$delivery_type = $_W['deliveryer']['deliveryer_type'];
			$where = "where uniacid = :uniacid";
			$param = [
				'uniacid'	=>	$this->uniacid,
			];
			$store_cn = implode(',', $_W['deliveryer']['deliveryer_store']);
			if($type == 3){
				$where .= ' and delivery_status = :status';
				$param[':status'] = 3;
				if($delivery_type == 1){
					
					$where .= ' and delivery_type = 2';
					
				}elseif($delivery_type == 2){
					
					$where .= ' and delivery_type = 1 and sid in ('.$store_cn.')';
					
				}else{
					
					$where .= ' and (delivery_type = 2 or (delivery_type = 1 and sid in ('.$store_cn.')))';
					
				}
			}else{
				if($type == 7){
					$where .= ' and (delivery_status = 7 or delivery_status = 8)';
				}else{
					
					$where = ' and delivery_status = :status';
					$param[':status'] = $type;
				}
			}
			$sql = "select id,order_plateform,serial_sn, addtime, is_pay, pay_type,order_type, status, username, mobile, address,distance, delivery_status,deliveryer_id,plateform_deliveryer_fee, delivery_type, delivery_fee, delivery_day, delivery_time,sid, num, final_fee,data,quotesta,note_images from ".tablename($this->tableName).$where;
			$orders = pdo_fetchall($sql,$param);
			$stores  = [];
			if(!empty($orders)){
				$store = [];
				foreach($orders as &$order){
					//当订单状态为待抢状态时,计算附加费
					if($order['status'] == 3){
						//$da['plateform_deliveryer_fee'] = order_calculate_deliveryer_fee($da,$_deliveryer['id']);
						//没看明白 先写个死的
						$order['plateform_deliveryer_fee'] = 0;
					}	
				
					$stores_id[] = $order['sid'];
					$goodsdata = iunserializer($order['data']);
					$goodslist = ''; 
				
					if($order['quotesta'] == 2){
						$goodslist = $goodsdata['item'];
						
					}else{
						foreach($goodsdata['cart'] as &$carts) {
							$goodslist .= ' '.$carts['title'];
						}
						
					}
					$order['goodslist'] = $goodslist;
				}
				$stores_str = implode(',', array_unique($stores_id));
				$stores = pdo_fetchall('select id, title, address, telephone from ' . tablename('rhinfo_service_store') . " where uniacid = :uniacid and id in ({$stores_str})", array(':uniacid' => $this->uniacid), 'id');
			}
			return ['stores'=>$stores,'orders'=>$orders];
		}
		//id查询订单
		public function getOrderByKey($id,$field=['*']){
			$order=$this->query->from($this->tableName)
						->where(['uniacid'=>$this->uniacid,'id'=>$id])
						->get();
			
			return $order;
		}
		//一条技师订单
		public function getDeliveryerOrder($deliveryer_id,$oid){
			$order=$this->query->from($this->tableName)
						->where(['uniacid'=>$this->uniacid,'deliveryer_id'=>$deliveryer_id,'id'=>$oid])
						->get();
			
			return $order;
		}
	}