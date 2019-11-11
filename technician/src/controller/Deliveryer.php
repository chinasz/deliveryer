<?php
	/*Desc:技师
	**Author:sz
	**Date:2019/11/8
	**Time:17:33
	*/
	namespace controller;
	class Deliveryer extends Auth{
		
		public function __empty(){
			
			
		}
		//技师主页信息
		public function show(){
			global $_W;
			$deliveryer_type = $_W['deliveryer']['deliveryer_type'];
			//接单数量
			$order_count = [];
			if($deliveryer_type != 2) {
				//开始时间
				$today_starttime = strtotime(date('Y-m-d'));
				$yesterday_starttime = $today_starttime - 86400;
				$month_starttime = strtotime(date('Y-m'));
				$sql = "select count(1) from ".tablename('rhinfo_service_order');
				$where = " where uniacid = :uniacid and deliveryer_id = :deliveryer_id and delivery_type =2 and status = 5 and addtime >= :starttime ";
				//
				$yesterday_param = [
					':uniacid'	=>	$this->uniacid,
					':deliveryer_id'=>	$this->uid,
					':starttime'=>	$yesterday_starttime,
					':endtime'	=>	$today_starttime,
				];
				$order_count['yesterday_num'] = pdo_fetchcolumn($sql.$where." and addtime <= :endtime",$yesterday_param);
				//
				$today_param = [
					':uniacid'	=>	$this->uniacid,
					':deliveryer_id'=>	$this->uid,
					':starttime'=>	$today_starttime,
				];
				$order_count['today_num'] = pdo_fetchcolumn($sql.$where,$today_param);
				//
				$month_param = [
					':uniacid'	=>	$this->uniacid,
					':deliveryer_id'=>	$this->uid,
					':starttime'=>	$month_starttime,
				];
				$order_count['month_num'] = pdo_fetchcolumn($sql.$where,$month_param);
				
				//
				$total_param = [
					':uniacid'	=>	$this->uniacid,
					':deliveryer_id'=>	$this->uid,
				];
				$order_count['total_num'] = pdo_fetchcolumn($sql." where uniacid = :uniacid and deliveryer_id = :deliveryer_id and delivery_type = 2 and status =5",$total_param);	
			}
			$m_deliveryer = new \model\Deliveryer($this->uniacid);
			$deliveryer = $m_deliveryer->getDeliveryerOne($this->uid);
			
			$output = [
				'deliveryer' =>	$deliveryer,
				'order'		=>	$order_count
			];
			jsonReturn(0,'',$output);
		}
		//技师账户
		public function account(){
			$deliveryer_account_status = [
				['id'=>0,'text'=>'全部'],
				['id'=>1,'text'=>'附加费入账'],
				['id'=>2,'text'=>'申请提现'],
				['id'=>3,'text'=>'其他变动']
			];
			$type = getvar('type');
			$type = empty($type)?0:intval($type);
			$where = " where uniacid = :uniacid and deliveryer_id = :deliveryer_id ";
			$param = [
				':uniacid'	=>	$this->uniacid,
				':deliveryer_id'=>$this->uid
			];
			if($type > 0){
				$where .= " and type = :type";
				$param[':type'] = $type;
			}
			$records = pdo_fetchall("select * from ".tablename('rhinfo_service_deliveryer_current_log').$where." order by id desc",$param);
			if(!empty($records)){
				foreach($records as &$record){
					
					if ($record['trade_type'] == 1) {
						$record['trade_type_cn'] = '附加费入账';
					}
					else if ($record['trade_type'] == 2) {
						$record['trade_type_cn'] = '申请提现';
					}
					else {
						$record['trade_type_cn'] = '其他变动';
					}
					
					$record['addtime'] = date('Y-m-d H:i',$record['addtime']);
				}
			}
			jsonReturn(0,'',['records'=>$records]);
		}
		//技师工作状态
		public function work(){
			$m_Deliveryer = new \model\Deliveryer($this->uniacid);
			$deliveryer = $m_Deliveryer->getDeliveryerOne($this->uid);
			$update =[
				'work_status'	=>	empty($deliveryer['work_status'])?1:0,
			];
			pdo_update('rhinfo_service_deliveryer',$update,['uniacid'=>$this->uniacid,'id'=>$this->uid]);
			pdo_update('rhinfo_service_store_deliveryer',$update,['uniacid'=>$this->uniacid,'deliveryer_id'=>$this->uid]);
			jsonReturn(0,'设置工作状态成功');
		}
		//余额提现
		public function cash(){
			global $_W;
			//技师类型
			$deliveryer_type = $_W['deliveryer']['deliveryer_type'];
			if($deliveryer_type == 2) jsonReturn(1,'非法访问');
			//技师信息
			$m_Deliveryer = new \model\Deliveryer($this->uniacid);
			$deliveryer = $m_Deliveryer->getDeliveryerOne($this->uid,['d.*']);
			if(empty($deliveryer['openid']) || empty($deliveryer['title'])) jsonReturn(1,'技师账户不完整,无法提现');
			//提现设置
			$sys = $_W['delivery']['sys']['cash'];
			//提现金额
			$cash_fee = getvar('cash');
			$cash_fee = floatval($cash_fee);
			if($cash_fee <= 0) jsonReturn(1,'提现金额错误');
			if($cash_fee < $sys['get_cash_fee_limit']) jsonReturn(1,'至少'.$sys['get_cash_fee_limit'].'元才能提现');
			if($cash_fee > $deliveryer['credit2']) jsonReturn(1,'提现金额大于账户余额');
			//手续费
			$take_fee = round($cash_fee*$sys['get_cash_fee_rate']/100,2);
			$take = max($take_fee,$sys['get_cash_fee_min']);
			if(0<$sys['get_cash_fee_max']){
				$take_fee = min($take_fee,$sys['get_cash_fee_max']);
			}
			//最终提现金额
			$final_fee = $cash_fee - $take_fee;
			$final_fee = $final_fee < 0?0:$final_fee;
				
			$insert_log = [
				'uniacid'	=>	$this->uniacid,
				'deliveryer_id'=>	$this->uid,
				'trade_no'	=>	date('YmdHis').random(10,true),
				'get_fee'	=>	$cash_fee,
				'take_fee'	=>	$take_fee,
				'final_fee'	=>	$final_fee,
				'account'	=>	iserializer([
					'nickname' => $deliveryer['nickname'], 
					'openid' => $deliveryer['openid'],
					'avatar' => $deliveryer['avatar'],
					'realname' => $deliveryer['title']
				]),
				'status'	=>	2,
				'addtime'	=>	TIMESTAMP
			];
			pdo_insert('rhinfo_service_deliveryer_getcash_log',$insert_log);
			$cash_log_id = pdo_insertid();
		
			//修改技师余额
			$delivery_update = [
				'credit2'	=>	$deliveryer['credit2'] - $cash_fee,
			];
			pdo_update('rhinfo_service_deliveryer',$delivery_update,['uniacid'=>$this->uniacid,'id'=>$this->uid]);
			//技师余额日志
			$remark = date('Y-m-d H:i:s') . '申请提现,提现金额' . $cash_fee . '元, 手续费' . $take_fee . '元, 实际到账' . $final_fee . '元';
			$account_log = [
				'uniacid'	=>	$this->uniacid,
				'delivery_id'=>	$this->uid,
				'order_type'=>	'order',
				'trade_type'=>	2,
				'extra'		=>	$cash_log_id,
				'fee'		=>	-$cash_fee,
				'amount'	=>	$delivery_update['credit2'],
				'addtime'	=>	TIMESTAMP,
				'remark'	=>	$remark
			];
			pdo_insert('rhinfo_service_deliveryer_current_log',$account_log);
			//
			jsonReturn(0,'申请提现成功');
		}
		//账户明细详情
		public function accountinfo(){
			$id = getvar('id');
			$info_field = ['id','trade_type','fee','amount','addtime','remark'];
			$info = pdo_get('rhinfo_service_deliveryer_current_log',['uniacid'=>$this->uniacid,'id'=>$id,'deliveryer_id'=>$this->uid],$info_field);
			$output['info'] = $info;
			if(!empty($info)){
				$info['addtime'] = date('Y-m-d H:i',$info['addtime']);
				if($info['trade_type'] == 2){
					$cash_field = ['trade_no','get_fee','take_fee','final_fee','status','addtime','endtime','account'];
					$cash_log = pdo_get('rhinfo_service_deliveryer_getcash_log',['uniacid'=>$this->uniacid,'id'=>$info['extra']]);
					$cash_log['account'] = iunserializer($cash_log['account']);
					$cash_log['addtime'] = date('Y-m-d H:i',$cash_log['addtime']);
					$cash_log['endtime'] = empty($cash_log['endtime'])?0:date('Y-m-d H:i',$cash_log['endtime']);
					$output['cash'] = $cash_log;
				}
			}
			jsonReturn(0,'',$output);
		}
	}