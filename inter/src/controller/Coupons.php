<?php
	/*Desc:优惠券
	**Author:sz
	**Date:2019/10/31
	**Time:15:49
	*/
	namespace controller;
	class Coupons extends Auth{
		//领取优惠券
		public function receive(){
			$id = getvar('coupon');
			$coupon = pdo_get('rhinfo_service_activity_coupon',['uniacid'=>$this->uniacid,'id'=>$id,'type'=>'couponCollect','status'=>1]);
			if(empty($coupon)) jsonReturn(1,'优惠券已撤销');
			//新用户
			$newmember = 0;
			if($coupon['type_limit'] == 2 && $newmember == 0){
				jsonReturn(1,'不符合领取条件');
			}
			if($coupon['starttime'] > time()) jsonReturn(1,'优惠活动未生效');
			
			if($coupon['endtime'] < time()){
				
				pdo_update('rhinfo_service_activity_coupon', array('status' => 0), array('id' => $coupon['id']));
				jsonReturn(1,'优惠活动失效');
				
			}
			if($coupon['dosage'] >= $coupon['amount']){
				pdo_update('rhinfo_service_activity_coupon', array('status' => 0), array('id' => $coupon['id']));
				jsonReturn(1,'优惠券已领完');
				
			}
			
			$is_grant = pdo_get('rhinfo_service_activity_coupon_record', array('couponid' => $coupon['id'], 'uid' => $this->uid));
			
			if(!empty($is_grant)) jsonReturn(1,'已经领取过此优惠券');
			$coupon['coupons'] = array_values(array_filter(iunserializer($coupon['coupons'])));

			foreach($coupon['coupons'] as $item){
				 $data =[
					'uniacid'	=>	$this->uniacid,
					'sid'		=>	$coupon['sid'],
					'couponid'	=>	$coupon['id'],
					'uid'		=>	$this->uid,
					'code'		=>	random(8,true),
					'type'		=>	'couponCollect',
					'condition'	=>	$item['condition'],
					'discount'	=>	$item['discount'],
					'granttime' => TIMESTAMP,
					'endtime' => TIMESTAMP + intval($item['use_days_limit']) * 86400,
					'status' => 1,
					'remark' => '',
				 ];
				pdo_insert('rhinfo_service_activity_coupon_record',$data);
			}
			pdo_update('rhinfo_service_activity_coupon', array('dosage' => $coupon['dosage'] + 1), array('uniacid' => $this->uniacid, 'sid' => $coupon['sid'], 'id' => $coupon['id']));
			jsonReturn(0,'领取成功');
		}
		
		
	}