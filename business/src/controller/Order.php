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
			$field = ['o.id','o.order_type','o.serial_sn','o.is_pay','o.ordersn','o.username','o.mobile','o.note','o.price','o.num','o.delivery_day','o.delivery_time','o.pay_type','o.addtime','o.status','o.delivery_fee','o.pack_fee','o.discount_fee','o.total_fee','o.final_fee','o.box_price','o.address','o.distance','o.data','m.nickname','m.avatar'];
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
			global $_W;
			$id = getvar('id');
			$m_Order = new \model\Order($this->uniacid);
			$order = $m_Order->getOneOrder($id);
			$store = [
				'title'	=>	$_W['business']['title'],
				'logo'	=>	tomedia($_W['business']['logo']),
			];
			jsonReturn(0,'',['order'=>$order,'store'=>$store]);
		}
		//通知技师抢单
		public function notify(){
			global $_W;
			$id = getvar('id');
			$m_Order = new \model\Order($this->uniacid);
			$order = $m_Order->getOneOrder($id);
			if(empty($order)) josnReturn(1,'订单不存在或已删除');
			if($order['order_type']>1) jsonReturn(1,'订单类型不是上门服务订单,不需要通知服务人员抢单');
			if($order['status'] > 3) jsonReturn(1,'订单状态有误');
			$update = [
				'status'	=>	3,
				'delivery_status'=>3,
				'delivery_type'=>$_W['business']['delivery_mode'],
				'clerk_notify_collect_time' => TIMESTAMP,
			];
			pdo_update('rhinfo_service_order',$update,['uniacid'=>$this->uniacid,'id'=>$id]);
			pdo_update('rhinfo_service_order_stat', array('status' => 3),['uniacid'=>$this->uniacid,'id'=>$id]);
			$status_log = [
				'uniacid' => $this->uniacid,
				'oid' => $id,
				'status' => 3,
				'type' => 'delivery_wait',
				'role' => $_W['role'],
				'role_cn' => $_W['role_cn'],
				'title' => '服务人员已准备就绪',
				'note' => '服务人员已准备就绪,将按时为您提供服务',
				'addtime' => TIMESTAMP,
			];
			
			pdo_insert('rhinfo_service_order_status_log', $status_log);
			jsonReturn(0,'通知服务人员抢单成功,请耐心等待服务人员接单');
		}
		//订单完成
		public function finish(){
			global $_W;
			$id = getvar('id');
			$m_Order = new \model\Order($this->uniacid);
			$order = $m_Order->getOneOrder($id);
			if(empty($order)) josnReturn(1,'订单不存在或已删除');
			if($order['status'] == 5) jsonReturn(1,'系统已完成，请勿重复操作');
			if($order['status'] == 6) jsonReturn(1,'订单已取消,不能进行其他操作');
			$is_timeout = 0;
			// if(($config_takeout['timeout_limit'] > 0) && (TIMESTAMP - $order['paytime'] > $config_takeout['timeout_limit'] * 60)) {
				// $is_timeout = 1;
			// }
			$update = array(
				'is_timeout' => $is_timeout,
				'status' => 5,
				'delivery_status' => 5, //已服务
				'endtime' => TIMESTAMP,
				'delivery_success_time' => TIMESTAMP,
				'is_remind' => 0
			);
			pdo_update('rhinfo_service_order', $update, array('uniacid' => $this->uniacid, 'id' => $order['id']));
			pdo_update('rhinfo_service_order_stat', array('status' => 5), array('uniacid' => $_W['uniacid'], 'oid' => $order['id']));
			if($order['delivery_type'] == 2){
				//平台配送费
				if (0 < $order['plateform_deliveryer_fee']) {
					// 					
					$m_deliveryer->changeDeliveryerAccount($order['plateform_deliveryer_fee'],$order['deliveryer_id'],$order['id']);
				}
				
				//用户支付方式为货到付款时
				if($order['pay_type'] == 'delivery'){
					// 
					$remark = "{$order['id']} 属于货到支付单,您线下收取客户{$order['final_fee']}元,平台从您账户扣除该费用";
					//从技师余额扣款
					$m_deliveryer->changeDeliveryerAccount($order['final_fee'],$order['deliveryer_id'],$order['id'],3,'dec',$remark);
				}
				
			}
			//
			if($order['is_pay'] == 1){
				$m_Store = new \model\Store($this->uniacid);
				if(in_array($order['pay_type'], array('wechat', 'alipay', 'credit', 'peerpay', 'qianfan', 'majia', 'eleme', 'meituan')) || ($order['delivery_type'] == 2 && $order['pay_type'] == 'delivery')) {
					//
					$m_Store->changeStoreAccount($order['store_final_fee'],$order['sid'],$order['id']);
					
				} else {
					$remark = "编号为{$order['id']}的订单属于线下支付,平台需要扣除{$order['plateform_serve_fee']}元服务费";
					// 
					$m_Store->changeStoreAccount($order['store_final_fee'],$order['sid'],$order['id'],1,"dec",$remark);
				}
			}
			//更新用户信息
			$member_mall = pdo_get('rhinfo_service_members',['uniacid'=>$this->uniacid,'uid'=>$order['uid']]);
			if(!empty($member_mall)){
				$member_update = [
					'success_num'	=>	$member_mall['success_num'] + 1,
					'success_price'	=>	round($member_mall['success_price']+ $order['final_fee'],2),
					'success_last_time' => TIMESTAMP,
				];
				if(!$member_mall['success_first_time']) {
					$member_update['success_first_time'] = TIMESTAMP;
				}
				pdo_update('rhinfo_service_members',$member_update,['id'=>$member_mall['id']]);
				//商户下用户信息
				$member_store =  pdo_get('rhinfo_service_store_members', array('uniacid' => $this->uniacid, 'sid' => $order['sid'], 'uid' => $order['uid']));
				
				if(empty($member_store)){
					//新增商户用户
					$insert = [
						'uniacid'	=>	$this->uniacid,
						'sid'		=>	$order['sid'],
						'uid'		=>	$order['uid'],
						'openid'	=>	$order['openid'],
						'success_first_time' => TIMESTAMP,
						'success_last_time' => TIMESTAMP,
						'success_num' => 1,
						'success_price' => $order['final_fee'],
					];
					
					pdo_insert('rhinfo_service_store_members', $insert);
				}else{
					//更新商户用户信息
					$sore_update = [
						'success_num' => $member_store['success_num'] + 1,
						'success_price' => round($member_store['success_price'] + $order['final_fee'], 2),
						'success_last_time' => TIMESTAMP,
					];
					pdo_update('rhinfo_service_store_members',$member_update,['id'=>$member_store['id']]);
				}
				
			}
			
			// 写入订单状态日志
			
			$status_log = [
				'uniacid'	=>	$this->uniacid,
				'oid'		=>	$order['id'],
				'status'	=>	5,
				'type'		=>	'end',
				'role'		=>	'deliveryer',
				'role_cn'	=>	'服务人员:'.$deliveryer['title'],
				'title'		=>	'订单已完成',
				'note'		=>	'任何意见和吐槽,都欢迎联系我们',
				'addtime' 	=> 	TIMESTAMP,
			];
			pdo_insert('rhinfo_service_order_status_log',$status_log);
			jsonReturn(0,'服务成功');
		}
	}