<?php
	/*Desc:优惠券
	**Author:sz
	**Date:2019/10/23
	**Time:9:31
	*/
	namespace model;
	class Coupon extends \We7Table{
		
		protected $tableName = 'rhinfo_service_activity_coupon';
        protected $primaryKey = 'id';
		protected $uniacid;
		public function __construct($uniacid){
			parent::__construct();
			$this->uniacid = $uniacid;	
		}
		//用户可领的优惠券
		public function getMemberUnclaimed($uid,$field=['c.*','s.*']){
			$Coupons = $this->query->from($this->tableName,'c')
									->leftjoin('rhinfo_service_store','s')
									->on(['c.id'=>'s.id'])
									->select($field)
									->where(['c.type'=>'couponCollect','c.uniacid'=>$this->uniacid,'c.status'=>1])
									->orderby('c.id','desc')
									->getall();

			if(!empty($Coupons)){
				foreach ($Coupons as $k=>$coupon){
					
					if(!empty($coupon['logo'])) $coupon['logo'] = tomedia($coupon['logo']);
					if(!empty($coupon['coupons'])){
						$coupon['coupons'] = array_filter(iunserializer($coupon['coupons']));
						$coupon['num'] = count($coupon['coupons']);
					}
					$coupon['discount'] = 0;
					if (!empty($coupon['num']) and 1 < $coupon['num']) {
						foreach ($coupon['coupons'] as $cou) {
							$coupon['discount'] += $cou['discount'];
						}

						$coupon['couponInfo'] = '内含' . $coupon['num'] . '张券';
					}
					else {
						$coupon['coupons'] = array_values($coupon['coupons']);
						$coupon['discount'] = $coupon['coupons'][0]['discount'];
						$coupon['couponInfo'] = '满' . $coupon['coupons'][0]['condition'] . '减' . $coupon['coupons'][0]['discount'];
					}
					$coupon['get_status'] = 1;
					
					$user_coupon = pdo_get('rhinfo_service_activity_coupon_record', array('uniacid' => $this->uniacid, 'couponid' => $coupon['eid'], 'uid' => $uid));

					if (!empty($user_coupon)) {
						$coupon['get_status'] = 0;
					}
					$coupon['percent'] = round(($coupon['dosage'] / $coupon['amount']) * 100, 2);	
					$Coupons[$k] = $coupon;
				}
			
			}
			return $Coupons;
		}
		
		
		
	}