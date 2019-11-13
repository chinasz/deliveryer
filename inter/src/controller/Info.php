<?php
/*Desc：
**Author:sz
**Date:2019/10/22
**Time:9:07
*/
	namespace controller;
	class Info extends Auth{
		//用户信息
		public function userinfo(){
			global $_W,$_GPC;
			/*-COPY-*/
			//收藏
			$favorite = intval(pdo_fetchcolumn('select count(*) from ' . tablename('rhinfo_service_store_favorite') . ' where uniacid = :uniacid and uid = :uid', array(':uniacid' => $_W['uniacid'], ':uid' => $_W['member']['uid'])));
			//卡券
			$coupon_nums = intval(pdo_fetchcolumn('select count(*) from ' . tablename('rhinfo_service_activity_coupon_record') . ' where uniacid = :uniacid and uid = :uid and status = 1', array(':uniacid' => $_W['uniacid'], ':uid' => $_W['member']['uid'])));
			//红包
			$redpacket_nums = intval(pdo_fetchcolumn('select count(*) from ' . tablename('rhinfo_service_activity_redpacket_record') . ' where uniacid = :uniacid and uid = :uid and status = 1', array(':uniacid' => $_W['uniacid'], ':uid' => $_W['member']['uid'])));
			/*-COPY END-*/
			$member = $_W['member'];
			$member['favorite'] = $favorite;
			$member['coupon_nums'] = $coupon_nums;
			$member['redpacket_nums'] = $redpacket_nums;
			jsonReturn(0,'',$member);
			
		}
		//用户订单
		public function order(){
			global $_W,$_GPC;
			$order_type = order_status();
			$type = getvar('type');
			$type = empty($type)?0:intval($type);
			$m_Order = new \model\Order($this->uniacid);
			
			$order = $m_Order->getorder($this->uid,$type);
			
			jsonReturn(0,'',['orders'=>$order,'type'=>$order_type]);
		}
		
		//用户收货地址列表
		public function address(){
			
			global $_W,$_GPC;
			$m_Address = new \model\Address($this->uniacid);
			
			$addresss = $m_Address->getAllAddress($this->uid,['id','realname','name','sex','mobile','is_default','address']); 
			
			jsonReturn(0,'',$addresss);
			
		}
		//领券中心
		public function coupon(){
			global $_W,$_GPC;
			$m_Coupon = new \model\Coupon($this->uniacid);
			$field = ['c.*','c.id as eid','s.title as store_title','s.logo'];
			$coupons = $m_Coupon->getMemberUnclaimed($this->uid,$field);
			
			jsonReturn(0,'',$coupons);
			
		}
		//评价列表
		public function evaluate(){
			global $_W,$_GPC;
			$m_comment = new \model\Evaluate($this->uniacid);
			
			$comments = $m_comment->getMemberEvaluate($this->uid);
			
			jsonReturn(0,'',$comments);
		}
		
		///投诉
		public function complaint(){
			
			//pass
			
		}	
		
		
	}