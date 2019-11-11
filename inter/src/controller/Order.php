<?php
    /*Desc:订单
    **Author:sz
    **Date:2019/10/26
    **Time:10:17
    */
	namespace controller;
    class Order extends Auth{

        //下单
        public function order(){
			global $_W,$_GPC;
			$sid = getvar('sid');
			$coupon_id = intval(getvar('coupon'));
			$address_id = intval(getvar('aid'));
			$day = intval(getvar('day'));
			$time = intval(getvar('time'));
			$m_Store = new \model\Store($this->uniacid);
			$m_Cart = new \model\Cart($this->uniacid);
			$m_Address = new \model\Address($this->uniacid);
			$store = $m_Store->storeInfo($sid);
			if(empty($store)) jsonReturn(1,'店铺不存在或已被停用');	
			
			//订单类型
			if($store['delivery_type']== 1 || $store['delivery_type'] ==3){
				$order_type = 1;
			}
			
			if($store['delivery_type'] == 2){
				$order_type = 2;
			}
			//用户购物车
			$cart = $m_Cart->showMemberCart($this->uid,$sid);
			if(empty($cart)) jsonReturn(1,'商品为空');
			
			//服务时间
			$delivery_time = $m_Store->storeDeliveryTimes($sid);
			
			if(empty($delivery_time['days'][$day]) || empty($delivery_time['times'][$time]) || $delivery_time['times'][$time]['status'] != 1) jsonReturn(1,'服务时间错误');
			//收货地址
			if($order_type == 1){
				$address = pdo_get('rhinfo_service_address',['uid'=>$this->uid,'id'=>$address_id,'uniacid'=>$this->uniacid]);
				if(empty($address_id) || empty($address)) jsonReturn(1,'服务地址错误');
				//上门服务
				$delivery_price = 0;
				$distance = 0;
				if($store['delivery_type'] != 2){
					if ($store['delivery_fee_mode'] == 1) {
						$delivery_price = $store['delivery_price'] + $delivery_time['times'][$time]['fee'];
					}elseif ($store['delivery_fee_mode'] == 2) {
						
						$distance = distanceBetween($address['location_y'], $address['location_x'], $store['location_y'], $store['location_x']);
						$distance = $distance / 1000;
						$delivery_price = $store['delivery_price_extra']['start_fee'];
						if (0 < $distance) {
							if ($store['delivery_price_extra']['start_km'] < $distance) {
								$delivery_price += ($distance - $store['delivery_price_extra']['start_km']) * $store['delivery_price_extra']['pre_km_fee'];
							}
							$delivery_price = round($delivery_price, 2);
							$delivery_price += $delivery_time['times'][$time]['fee'];
						}
						
					}else if ($store['delivery_fee_mode'] == 3){
						//服务地址是否在商家服务范围内 
						$is_check = $m_Address->checkMemberAddress($sid,[$address['location_y'],$address['location_x']]);
						if(!$is_check) jsonReturn(1,'服务址不在商家服务范围');
						/*I don't know what's these(copy)*/
						$price = store_order_condition($store,[$address['location_y'], $address['location_x']]);
						
						$price = $price['send_price'];
						if ($cart['price'] < $send_price) {
							jsonReturn(1,'当前服务不满上门服务价');
						}
						$delivery_price = round($price['delivery_price'], 2);
						$delivery_free_price = $price['delivery_free_price'];
						$delivery_price += $delivery_time['times'][$time]['fee'];
						$distance = distanceBetween($address['location_y'], $address['location_x'], $store['location_y'], $store['location_x']);
						$distance = $distance / 1000;
						/*--*/
					
					}
				}
				$distance = round($distance, 2);
				
			}elseif($order_type == 2){
				$name = getvar('name');
				$phone = getvar('phone');
				if(empty($name)) jsonReturn(1,'下单人为必填项');
				if(empty($phone)) jsonReturn(1,'手机号为为必填项');
				$address = ['realname'=>trim($name),'mobile'=>$phone];
				
			}
			//支付方式
			$pay_type = getvar('pay_type');
			$payment = iunserializer($store['payment']);
			if(!in_array($pay_type,$payment)) jsonReturn(1,'支付方式错误');
			
			$total_fee = $cart['price'] + $cart['box_price'] + $store['pack_price'] + $delivery_price;
			/*-copy-*/
			$serial_sn = pdo_fetchcolumn('select serial_sn from' . tablename('rhinfo_service_order') . ' where uniacid = :uniacid and sid = :sid and order_plateform = :order_plateform and addtime > :addtime order by serial_sn desc', array(':uniacid' => $_W['uniacid'], ':sid' => $sid, ':order_plateform' => 'rhinfo_service', ':addtime' => strtotime(date('Y-m-d'))));
			$serial_sn = intval($serial_sn) + 1;
			/*--*/
			//优惠
			$activityed = ['list' => '', 'total' => 0, 'activity' => 0, 'token' => 0, 'store_discount_fee' => 0, 'agent_discount_fee' => 0, 'plateform_discount_fee' => 0];
			if(!empty($coupon_id)){
				$coupon = pdo_get('rhinfo_service_activity_coupon_record', array('uniacid' => $this->uniacid, 'sid' => $sid, 'uid' => $this->uid,'status' => 1, 'id' => $coupon_id));
				if(!empty($coupon) && $coupon['starttime'] <= TIMESTAMP && $coupon['endtime'] >= TIMESTAMP && $cart['price'] >= $coupon['condition']){
					
					$activityed['list']['token'] = array('text' => "-￥{$coupon['discount']}", 'value' => $coupon['discount'], 'type' => 'couponCollect', 'name' => '代金券优惠', 'icon' => 'couponCollect_b.png', 'recordid' => $coupon_id, 'plateform_discount_fee' => 0, 'agent_discount_fee' => 0, 'store_discount_fee' => $coupon['discount']);
					$activityed['total'] += $coupon['discount'];
					$activityed['activity'] += $coupon['discount'];
					$activityed['store_discount_fee'] += $coupon['discount'];
					$activityed['agent_discount_fee'] += 0;
					$activityed['plateform_discount_fee'] += 0;
				}
				
			}
			//订单数据	
			$order = [
				'uniacid'	=>	$this->uniacid,
				'agentid'	=>	$store['agentid'],
				'acid'		=>	1,
				'sid'		=>	$sid,
				'uid'		=>	$this->uid,
				'mall_first_order'=>0,
				'ordersn'	=>	date('YmdHis') . random(6, true),
				'serial_sn'	=>	$serial_sn,
				'code'		=>	random(4, true),
				'order_type'=>	$order_type,
				'openid'	=>	$_W['member']['openid'],
				'mobile'	=>	$address['mobile'],
				'username'	=>	$address['realname'],
				'sex'		=>	$address['sex'],
				'address'	=>	$address['address'],
				'location_x'=>	$address['location_x'],
				'location_y'=>	$address['location_y'],
				'delivery_day'=>date('Y-').$delivery_time['days'][$day],
				'delivery_time'=>$delivery_time['times'][$time]['start'].'~'.$delivery_time['times'][$time]['end'],
				'delivery_fee'=> $delivery_price,
				'pack_fee'	=>	$store['pack_price'],
				'pay_type'	=>	$pay_type,
				'num'		=>	$cart['num'],
				'distance'	=>	$distance,
				'box_price'	=>	$cart['box_price'],
				'price'		=>	$cart['price'],
				'total_fee'	=>	$total_fee,
				'discount_fee'=>$activityed['total'],
				'store_discount_fee'=>$activityed['store_discount_fee'],
				'plateform_discount_fee'=>$activityed['plateform_discount_fee'],
				'agent_discount_fee'=>$activityed['agent_discount_fee'],
				'final_fee'	=>	$total_fee - $activityed['total'],
				'vip_free_delivery_fee'=>0,
				'delivery_type' => $store['delivery_mode'],
				'status'	=> 	1, 
				'is_comment'=> 	0,
				'invoice'	=>	'',
				'addtime' 	=> 	TIMESTAMP,
				'data'		=>	iserializer(['cart' => iunserializer($cart['data']),'commission' => ['spread1_rate' => '0%', 'spread1' => 0, 'spread2_rate' => '0%', 'spread2' => 0]]),
				'note_images'=>	'',
			];
			
			$order['final_fee'] = $order['final_fee'] >0?$order['final_fee']:0;
			
			pdo_insert('rhinfo_service_order',$order);
			$order_id = pdo_insertid();
			/*copy */
			//未知
			//order_update_bill($order_id, array('activity' => $activityed));
			//优惠券 order_insert_discount
			if(!empty($activityed['list']['token'])){
				pdo_update('rhinfo_service_activity_coupon_record', array('status' => 2, 'usetime' => TIMESTAMP, 'order_id' => $order_id), array('uniacid' => $this->uniacid, 'id' => $activityed['list']['token']['recordid']));
				foreach ($discount_data as $data ) {
					$insert = array('uniacid' => $this->uniacid, 'sid' => $sid, 'oid' => $order_id, 'type' => $data['type'], 'name' => $data['name'], 'icon' => $data['icon'], 'note' => $data['text'], 'fee' => $data['value'], 'store_discount_fee' => floatval($data['store_discount_fee']), 'agent_discount_fee' => floatval($data['agent_discount_fee']), 'plateform_discount_fee' => floatval($data['plateform_discount_fee']));
					pdo_insert('rhinfo_service_order_discount', $insert);
				}

			}
			//未知 	order_insert_status_log($order_id, 'place_order');
			$status_log1 = [
				'uniacid'	=>	$this->uniacid,
				'oid'		=>	$order_id,
				'status'	=>	1,
				'type'		=>	'place_order',
				'role'		=>	'consumer',//!empty($role) ? $role : $_W['role']; 未知
				'role'		=>	'下单顾主',//!empty($role_cn) ? $role_cn : $_W['role_cn']; 未知
				'title'		=>	'订单提交成功',
				'note'		=>	"单号:{$order['ordersn']},请耐心等待商家确认",
				'addtime'	=>	TIMESTAMP,
			];
			pdo_insert('rhinfo_service_order_status_log', $status_log1);
			$status_log2 = [
				'uniacid'	=>	$this->uniacid,
				'oid'		=>	$order_id,
				'title'		=>	'订单待支付',
				'note'		=>	'',
				'addtime'	=>	TIMESTAMP
			];
			pdo_insert('rhinfo_service_order_status_log', $status_log2);
			//order_update_goods_info($order_id, $sid);
			//pass
			
			/*--*/
			//删除购物车
			pdo_delete('rhinfo_service_order_cart',['id'=>$cart['id'],'uid'=>$this->uid]);
			
			
			jsonReturn(0,'',['oid'=>$order_id]);
        }
		//取消订单
		public function cancel(){
			global $_W,$_GPC;
			$oid = getvar('id');
			$m_Order = new \model\Order($this->uniacid);
			$order = $m_Order->getOrderByKey($oid,$this->uid);
			if(empty($order)) jsonReturn(1,'订单不存在或已删除');
			if($order['status'] != 1) jsonReturn(1,'商家已接单,如需取消订单请联系商家处理');
			if($order['is_pay']) jsonReturn(1,'订单已支付,如需取消订单请联系商家处理');
			if($order['delivery_type']  == 2 && $order['delivery_status'] >=4) jsonReturn(1,'该订单由平台服务，服务人员已接单，服务人员已准备就绪， 如需取消订单，请联系平台管理员');
			pdo_update('rhinfo_service_order_stat', array('status' => 6), array('uniacid' => $this->uniacid, 'oid' => $order['id']));
			if(!$order['is_pay'] || $order['final_fee'] <= 0 || ($order['is_pay'] == 1 && $order['pay_type'] == 'delivery' || $order['pay_type'] == 'cash')){
				
				pdo_update('rhinfo_service_order', array('status' => 6, 'delivery_status' => 6, 'spreadbalance' => 1, 'is_remind' => 0), array('uniacid' => $this->uniacid, 'id' => $order['id']));
				/*order_insert_status_log($order['id'], 'cancel', $extra['note']);*/
				$status_log = [
					'uniacid'	=>	$this->uniacid,
					'oid'		=>	$order['id'],
					'status'	=>	6,
					'role'		=>	'consumer',
					'role_cn'	=>	'下单顾主',
					'title'		=>	'订单已取消',
					'note'		=>	'',
					'addtime'	=>	TIMESTAMP
				];
				pdo_insert('rhinfo_service_order_status_log',$status_log);
				/**/
			}else{
				if($order['refund_status'] > 0){
					
					jsonReturn(1,'退款申请处理中, 请勿重复发起');
				}
				$update = [
					'status' => 6,
					'delivery_status' => 6,
					'refund_status' => 1,
					'refund_fee' => $order['final_fee'],
					'spreadbalance' => 1,
					'is_remind' => 0,
				];
				pdo_update('rhinfo_service_order', $update, array('uniacid' => $this->uniacid, 'id' => $order['id']));
				/*order_insert_status_log($order['id'], 'cancel', $extra['note']);*/
				$status_log = [
					'uniacid'	=>	$this->uniacid,
					'oid'		=>	$order['id'],
					'status'	=>	6,
					'role'		=>	'consumer',
					'role_cn'	=>	'下单顾主',
					'title'		=>	'订单已取消',
					'note'		=>	'',
					'addtime'	=>	TIMESTAMP
				];
				pdo_insert('rhinfo_service_order_status_log',$status_log);
				/**/
				
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
					'reason' => '订单取消，发起退款'
				);
				pdo_insert('rhinfo_service_order_refund', $refund);
				$is_refund = 1;
				//order_insert_refund_log($order['id'], 'apply');
				$refund_log = [
					'uniacid'	=>	$this->uniacid,
					'oid'		=>	$order['id'],
					'order_type'=>	'order',
					'status'	=>	1,
					'type'		=>	'apply',
					'title'		=>	'提交退款申请',
					'note'		=>	'',
					'addtime'	=>	TIMESTAMP
				];
				pdo_insert('rhinfo_service_order_refund_log', $refund_log);
				/**/
				/*order_status_notice($order['id'], 'cancel', $note);*/
				/**/
				/*order_refund_notice($order['id'], 'apply');*/
				if($order['deliveryer_id'] > 0){
					/*order_deliveryer_notice($order['id'], 'cancel', $order['deliveryer_id']);*/	
				}
				//对顾主的订单数据进行更新处理
				$member_update = [
					'cancel_num'	=>	$_W['member']['cancel'] + 1,
					'cancel_price'	=>	round($member['cancel']+$order['final_fee'], 2),
					'cancel_last_time' => TIMESTAMP
				];
				if(empty($_W['member']['cancel_first_time'])){
					$member_update['cancel_first_time'] = TIMESTAMP;
				}
				pdo_update('rhinfo_service_members',$member_update,['id'=>$this->uid]);
				$member_store = pdo_get('rhinfo_service_store_members',['uniacid'=>$this->uid,'sid'=>$order['sid'],'uid'=>$order['uid']]);
				if(empty($member_store)){
					$insert = [
						'uniacid'	=>	$this->uniacid,
						'sid'		=>	$order['sid'],
						'uid'		=>	$order['uid'],
						'openid'	=>	$order['openid'],
						'cancel_first_time'=>	TIMESTAMP,
						'cancel_last_time'=>TIMESTAMP,
						'cancel_num'	=>	1,
						'cancel_price'	=>	$order['final_fee']
					];
					pdo_insert('rhinfo_service_store_members', $insert);
				}else{
					$member_update = array(
						'cancel_num' => $member_store['cancel_num'] + 1,
						'cancel_price' => round($member_store['cancel_price'] + $order['final_fee'], 2),
						'cancel_last_time' => TIMESTAMP,
					);
					
					pdo_update('rhinfo_service_store_members', $member_update, array('id' => $member_store['id']));
					
				}
			}
			jsonReturn(0,'取消订单成功');
		}
		//订单评价
		public function evaluate(){
			$oid = getvar('id');
			$goods_score = getvar('goods_score');
			$delivery_score = getvar('delivery_score');
			$text = getvar('text');
			$thumb = getvar('thumb');
			$m_Order = new \model\Order($this->uniacid);
			$order = $m_Order->getOrderByKey($oid,$this->uid);
			if(empty($order)) jsonReturn(1,'订单不存在或已删除');
			if($order['is_comment'] == 1) jsonReturn(1,'订单已评价');
			$store = pdo_get('rhinfo_service_store',['id'=>$order['sid']]);
			$insert = [
				'uniacid'	=>	$this->uniacid,
				'uid'		=>	$this->uid,
				'username'	=>	$order['username'],
				'avatar'	=>	$_W['member']['avatar'],
				'mobile'	=>	$order['mobile'],
				'oid'		=>	$order['id'],
				'sid'		=>	$order['sid'],
				'delivery_id'=>	$order['delivery_id'],
				'goods_quality'=>intval($goods_score),
				'delivery_service'=>intval($delivery_score),
				'note'		=>	trim($text),
				'status'	=>	$store['comment_status'],
				'data'		=>	'',
				'addtime'	=>	TIMESTAMP
			];
			if(empty($thumb)){
				$insert['thumb'] = [];
			}else{
				// 预留
			}
			//商品评价
			//预留
			
			$insert['score'] = $insert['delivery_service'] + $insert['goods_quality'];
			$insert['data']	= iserializer($insert['data']);
			pdo_insert('rhinfo_service_order_comment',$insert);
			pdo_update('rhinfo_service_order',['is_comment'=>1],['id'=>$oid]);
			
			jsonReturn(0,'评价成功');
		}
    }