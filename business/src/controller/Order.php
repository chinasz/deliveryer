<?php
	/*Desc:订单
	**Author:sz
	**Date:2019/11/04
	**Time:15:29
	*/
	namespace controller;
	class Order extends Auth{
		
		
		public function __empty(){
			
			jsonReturn(43,'url错误');
		}
		//获取订单
		public function show(){
			$page = getvar('p');
			$page = $page > 0?$page:1;
			$type = getvar('type');
			$type = $type > 0?intval($type):0;
			$sid = $this->store_id;
			$m_Order = new \model\Order($this->uniacid);
			$field = ['o.id','o.is_pay','o.ordersn','o.username','o.mobile','o.note','o.price','o.num','o.delivery_day','o.delivery_time','o.pay_type','o.addtime','o.status','o.delivery_fee','o.pack_fee','o.discount_fee','o.total_fee','o.final_fee','o.box_price','o.data','m.nickname','m.avatar'];
			$order = $m_Order->getOrder($sid,$page,$type,$field);
			$order_type = order_status();
			jsonReturn(0,'',['order'=>$order,'type'=>$order_type]);
		}
		//接单
		public function receive(){
			$id = getvar('id');
			$m_Order = new \model\Order($this->uniacid);
			$order = $m_Order->getOneOrder($id);
			if(empty($order)) josnReturn(1,'订单不存在或已删除');
			if($order['status'] != 1) jsonReturn(1,'订单状态不是待处理状态,不能接单');
			if(!$order['is_pay'] && $order['order_type'] <= 2) jsonReturn(1,'该订单属于上门服务单,并且未支付,不能接单');
			
			$res = $m_Order->receiveOrder($oid);
			if($res) jsonReturn(0,'接单成功');
			josnReturn(1,'服务繁忙,请重试');
		}
		//取消订单
		public function cancel(){
			$id = getvar('id');
			$m_Order = new \model\Order($this->uniacid);
			$order = $m_Order->getOneOrder($id);
			if(empty($order)) josnReturn(1,'订单不存在或已删除');
			//取消原因
			$reasons = order_cancel_types();
			$reason = getvar('reason');
			if(empty($reasons[$reason])) jsonReturn(1,'请选择取消原因');
			if($order['status'] == 5) jsonReturn(1,'系统已完成,不能取消订单');
			if($order['status'] == 6) jsonReturn(1,'系统已取消,不能取消订单');
			if($order['delivery_type'] == 2 && $order['delivery_status'] >= 4) josnReturn(1,'该订单由平台服务，服务人员已接单，服务人员已准备就绪， 如需取消订单，请联系平台管理员');
			
			if($order['refund_status'] > 0) josnReturn(1,'退款申请处理中,请勿重复发起');
			$m_Order->cancelOrder($id,$reasons[$reason]);
			
			jsonReturn(1,'取消订单成功, 退款会在1-15个工作日打到客户账户');
		}
		//订单详情
		public function info(){
			$id = getvar('id');
			$m_Order = new \model\Order($this->uniacid);
			$order = $m_Order->getOneOrder($id);
			$store = [
				'title'	=>	$_W['business']['title'],
				'logo'	=>	tomedia($_W['business']['logo']),
			];
			jsonReturn(0,'',['order'=>$order,'store'=>$store]);
		}
		
	}