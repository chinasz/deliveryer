<?php
	/*Desc:订单
	**Author:sz
	**Date:2019/11/08
	**Time:11:48
	*/
	namespace controller;
	class Order extends Auth{
		
		public function __empty(){
		
		}
		
		//技师订单
		public function show(){
			$type = getvar('type');
			$ouput['type']= deliveryer_order_types();
			$type = empty($type)?3:$type;
			$m_Order = new \model\Order($this->uniacid);
			$orders = $m_Order->deliveryerOrder($this->uid,$type);
			jsonReturn(0,'',$orders);
			
		}
		//抢单
		public function rob(){
			//订单id
			$oid = getvar('id');
			$m_Order = new \model\Order($this->uniacid);
			//
			$order = $m_Order->getOrderByKey($oid);
			//
			$m_deliveryer = new \model\Deliveryer($this->uniacid);
			$deliveryer = $m_deliveryer->getDeliveryerOne($this->uid);
			
			if(empty($order)) jsonReturn(1,'订单不存在或已删除');
			if($order['status'] == 5) jsonReturn(1,'系统已完成,不能抢单');
			if($order['status'] == 3) jsonReturn(1,'系统已取消,不能抢单');
			if($order['deliveryer_id'] > 0) jsonReturn(1,'来迟了,该订单已被别人接单');
			//修改订单状态
			$update = [
				'status'	=>	4,
				'delivery_status'=>7,
				'deliveryer_id'=>$this->uid,
				'delivery_assign_time' => TIMESTAMP,
			];
			pdo_update('rhinfo_service_order',$update,['uniacid'=>$this->uniacid,'id'=>$order['id']]);
			pdo_update('rhinfo_service_order_stat',['status'=>4],['uniacid'=>$this->uniacid,'oid'=>$order['id']]);
			//技师接单数量+1
			$sql = "update ".tablename('rhinfo_service_deliveryer')." set order_takeout_num = order_takeout_num+1 where uniacid = :uniacid and id = :id";
			pdo_query($sql,[':uniacid'=>$this->uniacid,':id'=>$this->uid]);
			//订单状态日志
			$note = "技师:{$deliveryer['title']},手机号{$deliveryer['mobile']}";
			$status_log = [
				'uniacid'	=>	$this->uniacid,
				'oid'		=>	$order['id'],
				'status'	=>	4,
				'type'		=>	'delivery_assign',
				'role'		=>	'deliveryer',
				'role_cn'	=>	'服务人员:'.$deliveryer['title'],
				'title'		=>	'已分配服务人员',
				'note'		=>	$note,
				'addtime'	=>	TIMESTAMP
			];
			pdo_insert('rhinfo_service_order_status_log',$status_log);
			jsonReturn(0,'抢单成功');
		}
		//确认订单
		public function takegoods(){
			$oid = getvar('id');
			$m_Order = new \model\Order($this->uniacid);
			$order = $m_Order->getDeliveryerOrder($this->uid,$oid);
			//
			$m_deliveryer = new \model\Deliveryer($this->uniacid);
			$deliveryer = $m_deliveryer->getDeliveryerOne($this->uid);
			
			if(empty($order)) jsonReturn(1,'订单不存在');
			if($order['status'] == 5) jsonReturn(1,'系统已完成,不能确认订单');
			if($order['status'] == 6) jsonReturn(1,'系统已取消,不能确认订单');
			
			$update = [
				'delivery_status' => 4,
				'delivery_instore_time'=>TIMESTAMP,
				'delivery_handle_type'=> 'app',
			];
			pdo_update('rhinfo_service_order',$update,['uniacid'=>$this->uniacid,'id'=>$order['id'],]);
			// order_insert_status_log($order['id'], 'delivery_instore');
			$status_log = [
				'uniacid'	=>	$this->uniacid,
				'oid'		=>	$order['id'],
				'status'	=>	12,
				'type'		=>	'delivery_instore',
				'role'		=>	'deliveryer',
				'role_cn'	=>	'服务人员:'.$deliveryer['title'],
				'title'		=>	'服务人员已安排就绪',
				'note'		=>	'服务人员已安排就绪, 将按时为您提供服务',
				'addtime'	=>	TIMESTAMP
			];
			pdo_insert('rhinfo_service_order_status_log',$status_log);
			
			jsonReturn(0,'确认已接单成功');
		}
		//服务完成
		public function finish(){
			$oid = getvar('id');
			$m_Order = new \model\Order($this->uniacid);
			$order = $m_Order->getDeliveryerOrder($this->uid,$oid);
			//
			$m_deliveryer = new \model\Deliveryer($this->uniacid);
			$deliveryer = $m_deliveryer->getDeliveryerOne($this->uid);
			
			if(empty($order)) jsonReturn(1,'订单不存在');
			if($order['status'] == 5) jsonReturn(1,'系统已完成,不能确认服务完成');
			if($order['status'] == 6) jsonReturn(1,'系统已取消,不能确认服务完成');
			// order_deliveryer_update_status($id, 'delivery_success', array('deliveryer_id' => $_deliveryer['id']));
			
			// order_status_update($order['id'], 'end', $extra);
			
			
		}
	}