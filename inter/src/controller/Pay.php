<?php
	/*Desc:订单支付
	**Author:sz
	**Date:2019/10/30
	**Time:10:04
	*/
	namespace controller;
	class Pay extends Auth{
		
		//支付
		public function pay(){
			global $_W;
			
			$oid = getvar('id');
			$m_Store = new \model\Store($this->uniacid);

			$m_Order = new \model\Order($this->uniacid);
			$order = $m_Order->getOrderByKey($oid,$this->uid);
			if(empty($order)) jsonReturn(1,'订单不存在或已删除');
			// $this->wechatPay(0.1,$order);
			if(!empty($order['is_pay'])) jsonReturn(1,'订单已付款');
			if($order['status'] == 6) jsonReturn(1,'订单已取消');
			
			//支付记录
			$type = 'takeout'; //不确定
			$record = pdo_get('rhinfo_service_paylog', array('uniacid' => $this->uniacid, 'order_id' => $oid, 'order_type' => $type, 'order_sn' => $order['ordersn']));
			if(empty($record)){
				$insert = [
					'uniacid'	=>	$this->uniacid,
					'agentid'	=>	$order['agentid'],
					'uid'		=>	$_W['member']['uid'],
					'order_sn'	=>	$order['ordersn'],
					'order_id'	=>	$order['id'],
					'order_type'=>	$type,
					'fee'		=>	$order['final_fee'],
					'status'	=>	0,
					'addtime'	=>	TIMESTAMP
				];
				pdo_insert('rhinfo_service_paylog', $insert);
				$record['id'] = pdo_insertid();
			}else if ($record['status'] == 1) {
				jsonReturn(1, '该订单已支付,请勿重复支付');
			}
			//微擎系统支付记录
			$log = pdo_get('core_paylog', array('uniacid' => $this->uniacid, 'module' => 'rhinfo_service', 'tid' => $order['ordersn']));
			if(empty($log)){
				$log = [
					'uniacid'	=>	$this->uniacid,
					'acid'		=>	$_W['acid'],
					'openid'	=>	$_W['member']['uid'],
					'module'	=>	'rhinfo_service',
					'uniontid' 	=> 	date('YmdHis') . random(14, 1),
					'tid'		=>	$order['ordersn'],
					'fee'		=>	$order['final_fee'],
					'card_fee'	=>	$order['final_fee'],
					'status'	=>	0,
					'is_usecard'=>	0
				];
				pdo_insert('core_paylog',$log);
				
			}else if($log['status'] == 1){
				jsonReturn(1, '该订单已支付,请勿重复支付');
			}
			
			if($log['status'] == 0){
				$method = trim($order['pay_type']).'Pay';
				if(method_exists($this,$method)){
					$res = $this->$method($order['final_fee'],$order);
					if(empty($res)) jsonReturn(1,'支付失败');
					
					$this->updatePayStatus($order,$order['pay_type']);
					jsonReturn(0,'支付成功');	
				}else{
					jsonReturn(1,'支付方式错误');
				}
			}
			
		}
		//余额支付
		private function creditPay($fee,$order){
			$m_Member = new \model\Member($this->uniacid);
			$member = $m_Member->getMemeberInfo($this->uid);
			if($member['credit2'] < $fee){
				jsonReturn(1,'余额不足,需要'.$fee.'元,当前'.$member['credit2'].'元');
			}
			$balance = floatval($member['credit2']) - floatval($fee);  
			$res = pdo_update('rhinfo_service_members',['credit2'=>$balance],array('id'=>$this->uid));
			
			return $res;
		}
		//微信支付
		private function wechatPay($fee,$order){
			global $_W;
			$wechat_config = $_W['inter_config']['payment']['wechat']['default'];
			if(empty($wechat_config) || empty($wechat_config['appid']) || empty($wechat_config['mchid']) || empty($wechat_config['apikey'])) jsonReturn(1,'支付参数错误');
			$url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
			$param = [
				'appid'	=>	$wechat_config['appid'],
				'mch_id'=>	$wechat_config['mchid'],
				'nonce_str'=>random(32),
				'body'	=>	'家政平台订单支付',
				'out_trade_no'=>$order['ordersn'],
				'total_fee'=>	floatval($fee)*100,
				'spbill_create_ip'=>CLIENT_IP,
				'notify_url'=>	'http://www.baidu.com',
				'trade_type'=>	'APP',
			];
			//生成签名
			krsort($param);
			$sign = '';
			foreach ($param as $k => $v)
			{
				if($k != "sign" && $v != "" && !is_array($v)){
					$sign .= $k . "=" . $v . "&";
				}
			}
		
			$sign = trim($sign, "&");

			$sign = strtoupper(md5($sign."&key=".$wechat_config['apikey']));
			$param['sign'] = $sign;
			$post = array2xml($param);
			$res = ihttp_post($url,$post);
			var_dump($res);die;
	
		}
		//货到付款
		// private function 
		//修改订单状态
		private function updatePayStatus($order,$pay_type){
			pdo_update('core_paylog', ['status' => '1', 'type' => $pay_type], ['tid' => $order['ordersn'] ]);
			$record = pdo_get('rhinfo_service_paylog', array('uniacid' => $this->uniacid, 'order_sn' => $order['ordersn']));
			
			$update = [
				'is_pay'	=>	1,
				'order_channel'=>	'app',
				'pay_type'	=>	$pay_type,
				'final_fee'	=>	$record['fee'],
				'paytime'	=>	TIMESTAMP,
				'transaction_id'=> '',
				'out_trade_no'=>	'',	//$record['uniontid']	
			];
			/*-copy-*/
			if($order['order_type'] <= 2){
				$store = pdo_get('rhinfo_service_store', array('uniacid' => $this->uniacid, 'id' => $order['sid']), array('delivery_mode', 'auto_handel_order', 'auto_notice_deliveryer'));
				if($store['auto_handel_order'] == 1){
					$update['status'] = 2;
					$update['handletime'] = TIMESTAMP;
					if($order['order_type'] == 2) {
						$update['status'] = 4;
					}
					if($store['auto_notice_deliveryer'] == 1 && $order['order_type'] == 1) {
					if($order['quotesta']==2){ //现场报价---rhao---
						$update['status'] = 4;
					}else{
						$update['delivery_type'] = $store['delivery_mode'];
						$update['status'] = 3; //待服务（待抢单）
						$update['delivery_status'] = 3;
						$update['deliveryer_id'] = 0;
					}
					$update['clerk_notify_collect_time'] = TIMESTAMP;
					}
					pdo_update('rhinfo_service_order', $update, array('id' => $order['id'], 'uniacid' => $this->uniacid));
					
					// order_insert_status_log($order['id'], 'pay');
					// order_insert_status_log($order['id'], 'handle');
					// if($store['auto_notice_deliveryer'] == 1) {
						// order_insert_status_log($order['id'], 'delivery_wait');
					// }
					// order_print($order['id']);
					// order_status_notice($order['id'], 'handle');
					// order_clerk_notice($order['id'], 'place_order');
					// if($store['auto_notice_deliveryer'] == 1) {
						// order_status_update($order['id'], 'notify_deliveryer_collect', array('notify_channel' => 'first'));
					// }
				}else{
					pdo_update('rhinfo_service_order', $update, array('id' => $order['id'], 'uniacid' => $this->uniacid));
					// order_insert_status_log($order['id'], 'pay');
					// order_print($order['id']);
					// order_status_notice($order['id'], 'pay');
					// 下单立即通知
					// if(empty($config_takeout['notify_rule_clerk']['notify_delay'])) {
						// order_clerk_notice($order['id'], 'place_order');
					// }
					
				}
				/*--*/
			}elseif($order['order_type'] == 3){
				
				// mload()->model('table');
				$update['status'] = 2;
				pdo_update('rhinfo_service_order', $update, array('id' => $order['id'], 'uniacid' => $this->uniacid));
				// table_order_update($order['table_id'], $order['id'], 4);
				// order_insert_status_log($order['id'], 'pay');
				// order_print($order['id']);
				// order_status_notice($order['id'], 'pay');
				// order_clerk_notice($order['id'], 'store_order_pay');
			}elseif($order['order_type'] == 4){
				$update['status'] = 2;
				pdo_update('rhinfo_service_order', $update, array('id' => $order['id'], 'uniacid' => $this->uniacid));
				// order_insert_status_log($order['id'], 'pay');
				// order_print($order['id']);
				// order_status_notice($order['id'], 'pay');
				// order_clerk_notice($order['id'], 'reserve_order_pay');
				
			}
			return ;
		}
		
		
		
	}