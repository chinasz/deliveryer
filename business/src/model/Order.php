<?php
	/*Desc:商户订单模型
	**Author:sz
	**Date:2019/11/04
	**Time:15:20
	*/
	namespace model;
	class Order extends Model{
		
		protected $tableName = 'rhinfo_service_order'; 
		
		protected $uniacid;

		public function __construct($uniacid){

			parent::__construct();

			$this->uniacid = $uniacid;	

		}
		//获取店铺订单
		public function getOrder($sid,$page=1,$type='',$field=['o.*','m.nickname','m.avatar']){
			$order_status = order_status();
			$pay_types = pay_types();
 			$size = 10;
			$start = ($page - 1) * $size;
			$where = ['uniacid'=>$this->uniacid,'sid'=>$sid];
			if(!empty($type)) $where['status'] = $type;
			$orders = $this->query->from($this->tableName,'o')
						->where($where)
						->innerjoin('rhinfo_service_members','m')
						->on(['o.uid'=>'m.id'])
						->select($field)
						->orderby('id','desc')
						->limit($start,$size)
						->getall();
			if(!empty($orders)){
				foreach($orders as $k=>$order){
					if(!empty($order['avatar'])){
						$order['avatar'] = tomedia($order['avatar']);
					}
					$pay_index = $order['pay_type'];
					$order['pay_type'] = $pay_types[$pay_index]['text'];
					if(!empty($order['data']) && is_serialized($order['data'])){
						
						$order['data'] = iunserializer($order['data']);
						$order['data'] = array_values($order['data']['cart']);
					}
					if(!empty($order['status'])){
						$index = $order['status'];
						$order['status'] = $order_status[$index]['text'];
					}
					$orders[$k] = $order;
				}	
				
			}
			return $orders;
		}
		//修改订单状态
		public function updateOrderStatus($oid,$update){
			
			
			
			
		}
		//获取订单详情
		public function getOneOrder($oid,$field=['*']){
			$order = $this->query->from($this->tableName)
							->where(['uniacid'=>$this->uniacid,'id'=>$oid])
							->select($field)
							->get();
			if(!empty($order)){
				$order_types = order_types();
				$pay_types = order_pay_types();
				$order_status = order_status();
				$order['order_type_cn'] = $order_types[$order['order_type']];
				$order['status_cn'] = $order_status[$order['status']]['text'];
				$order['addtime'] = date('Y-m-d H:i',$order['addtime']);
				$order['order_pay_type_cn'] = $pay_types[$order['pay_type']]['text'];
			}
			return $order;
		}
		
		//接单
		public function receiveOrder($oid){
			global $_W;
			
			$update = array(
				'status' => 2,
				'handletime' => TIMESTAMP,
			);
			
			pdo_update($this->tableName,$update,['uniacid'=>$this->uniacid,'id'=>$oid]);
			pdo_update('rhinfo_service_order_stat',['status' => 2],['uniacid' => $this->uniacid, 'oid' => $oid]);
			
			$status_log = [
				'uniacid'	=>	$this->uniacid,
				'oid'		=>	$oid,
				'type'		=>	'handle',
				'role'		=>	$_W['role'],
				'role_cn'	=>	$_W['role_cn'],
				'title'		=>	'商家已确认订单',
				'note'		=>	'正在为您安排服务人员',
				'addtime'	=>	TIMESTAMP,
			];
			pdo_insert('rhinfo_service_order_status_log', $status_log);
			return true;
		}
		/*取消订单
		**@param $oid 订单id $reason 取消原因
		**@return 
		*/
		public function cancelOrder($oid,$reason){
			global $_W;
			$order = $this->getOneOrder($oid);
			//修改订单状态
			$update = [
				'status'	=>	6,
				'delivery_status'=>6,
				'refund_status'=>	1,
				'refund_fee'	=>	$order['final_fee'],
				'spreadbalance'	=>	1,
				'is_remind'		=>	0
			];
			pdo_update('rhinfo_service_order_stat', array('status' => 6), array('uniacid' => $this->uniacid, 'oid' => $order['id']));
			pdo_update($this->tableName,$update,['uniacid'=>$this->uniacid,'id'=>$order['id']]);
			//订单状态日志
			$status_log = [
				'uniacid'	=>	$this->uniacid,
				'oid'		=>	$order['id'],
				'type'		=>	'cancel',
				'role'		=>	$_W['role'],
				'role_cn'	=>	$_W['role_cn'],
				'title'		=>	'订单已取消',
				'note'		=>	$reason,
				'addtime'	=>	TIMESTAMP,
			];
			pdo_insert('rhinfo_service_order_status_log', $status_log);
			//退款申请	
			$refund = array(
				'uniacid' => $order['uniacid'],
				'acid' => $order['acid'],
				'sid' => $order['sid'],
				'uid' => $order['uid'],
				'order_id' => $order['id'],
				'order_sn' => $order['ordersn'],
				'order_channel' => $order['order_channel'],
				'pay_type' => $order['pay_type'],
				'fee' => $order['final_fee'],
				'status' => 1,
				'out_trade_no' => $order['out_trade_no'],
				'out_refund_no' => date('YmdHis') . random(10, true),
				'apply_time' => TIMESTAMP,
				'reason' => $reason
			);
			pdo_insert('rhinfo_service_order_refund', $refund);
			//退款申请日志
			$refund_log = [
				'uniacid'	=>	$this->uniacid,
				'oid'		=>	$order['id'],
				'order_type'=>	'order',
				'status'	=>	1,
				'type'		=>	'apply',
				'title'		=>	'提交退款申请',
				'note'		=>	"",
				'addtime' => TIMESTAMP,
			];
			pdo_insert('rhinfo_service_order_refund_log', $refund_log);
			
			return true;
		}
	}