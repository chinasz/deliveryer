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
			$output['type']= deliveryer_order_types();
			$type = empty($type)?3:$type;
			$m_Order = new \model\Order($this->uniacid);
			$orders = $m_Order->deliveryerOrder($this->uid,$type);
			$output['orders'] = $orders['orders'];
			$output['stores'] = $orders['stores'];
			jsonReturn(0,'',$output);
			
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
			if($order['status'] == 6) jsonReturn(1,'系统已取消,不能抢单');
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
			global $_W;
			$oid = getvar('id');
			$m_Order = new \model\Order($this->uniacid);
			$order = $m_Order->getDeliveryerOrder($this->uid,$oid);
			//
			$m_deliveryer = new \model\Deliveryer($this->uniacid);
			$deliveryer = $m_deliveryer->getDeliveryerOne($this->uid);
			
			if(empty($order)) jsonReturn(1,'订单不存在');
			if($order['status'] == 5) jsonReturn(1,'系统已完成,不能确认服务完成');
			if($order['status'] == 6) jsonReturn(1,'系统已取消,不能确认服务完成');
			
			// 修改订单
			$istimeout = 0;
			if(($_W['sys']['takeout']['order']['timeout_limit']>0) && (TIMESTAMP - $order['paytime'] > $_W['sys']['takeout']['order']['timeout_limit'] * 60)){
				$is_timeout = 1;
			}
			$update = [
				'is_timeout' => $is_timeout,
				'status'	 =>	5,
				'delivery_status'=>5,
				'endtime' => TIMESTAMP,
				'delivery_success_time' => TIMESTAMP,
				'delivery_success_location_x' => '',
				'delivery_success_location_y' => '',
				'is_remind' => 0,
				'deliveryer_id'=>	$this->uid,
			];
			pdo_update('rhinfo_service_order',$update,['uniacid'=>$this->uniacid,'id'=>$order['id']]);
			pdo_update('rhinfo_service_order_stat',['status'=>5],['uniacid'=>$this->uniacid,'oid'=>$order['id']]);
			//
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
			//赠送积分 order.mode.php line 2422(暂时不做)
			
			//
			//赠送余额
			
			//
			//赠送优惠券
			
			//
			//赠送红包
			
			//
			
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
		//订单详情
		public function detail(){
			$oid = getvar('oid');
			if(empty($oid)) jsonReturn(43,'参数错误');
			
			$m_Order = new \model\Order($this->uniacid);
			$field = ['o.id','o.status','o.ordersn','o.username','o.mobile','o.location_x','o.location_y','o.address','o.note','o.pay_type','s.title as store_name','s.address as store_add','s.location_x as store_x','s.location_y as store_y','s.telephone as store_phone','o.total_fee','o.final_fee','o.discount_fee','o.deliveryer_id','o.delivery_day','o.delivery_time','o.order_type','o.delivery_fee']; 
			$order = $m_Order->getOrderDetail($oid,$field);
			$pay_types = pay_types();
			$order_types = order_types();
			if(!empty($order)){
				//支付方式
				$order['pay_type'] = $pay_types[$order['pay_type']]['text'];
				//服务人员
				if($order['deliveryer_id']>0){
					$m_deliveryer = new \model\Deliveryer($this->uniacid);
					$delivery = $m_deliveryer->getDeliveryerOne($order['deliveryer_id'],['d.nickname','d.avatar']);
					if(!empty($delivery))  $order['deliveryer'] = $delivery;
				}
				//服务方式
				$order['order_type'] = $order_types[$order['order_type']]['text'];
			}
			
			if(empty($order)) jsonReturn(1,'订单不存在或已删除');
			jsonReturn(0,'',$order);
		}
	}