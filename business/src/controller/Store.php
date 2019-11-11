<?php
	/*Desc:商户
	**Author:sz
	**Date:2019/11/04
	**Time:18:16
	*/
	namespace controller;
	class Store extends Auth{
		
		public function __empty(){
		
			jsonReturn(43,'url错误');
		}
		//商户余额
		public function income(){
			
			$output['balance'] = 0;
			$output['balance'] = pdo_getcolumn('rhinfo_service_store_account',['uniacid'=>$this->uniacid,'sid'=>$this->store_id],'amount');
			
			$output['cashed'] = pdo_fetchcolumn("select sum(fee) from ".tablename('rhinfo_service_store_current_log')." where trade_type = 2 and sid = ".$this->store_id." and uniacid = ".$this->uniacid." order by id desc");
			$output['cashed'] = empty($output['cashed'])?'0.00':$output['cashed'];
			jsonReturn(0,'',$output);
			
		}
		//账户明细
		public function feedetail(){
			
			$field = ['id','trade_type','fee','amount','addtime','remark'];
			$detail = pdo_getall('rhinfo_service_store_current_log',['uniacid'=>$this->uniacid,'sid'=>$this->store_id],$field);
			if(!empty($detail)){
				foreach($detail as $k=>$v){
					if ($v['trade_type'] == 1) {
						$v['trade_type_cn'] = '订单入账';
					}
					else if ($v['trade_type'] == 2) {
						$v['trade_type_cn'] = '申请提现';
					}
					else {
						$v['trade_type_cn'] = '其他变动';
					}
					$v['addtime'] = date('Y-m-d H:i',$v['addtime']);
					$detail[$k] = $v; 
				}
				
			}
			jsonReturn(0,'',$detail);
		}
		//店铺商品分类
		public function category(){
			
			$m_Goods = new \model\Goods($this->uniacid);
			$categorys = $m_Goods->goodsCategory($this->store_id,['id','title']);
			
			jsonReturn(0,'',['category'=>$categorys]);
		}
		//店铺信息
		public function info(){
			global $_W;
			$tem = $_W['business'];
			$store = [
				'title'	=>	trim($tem['title']),
				'logo'	=>	empty($tem['logo'])?'':tomedia($tem['logo']),
				'business_hours'=>empty($tem['business_hours'])?[]:iunserializer($tem['business_hours']),
				'content'=>	trim($tem['content']),
				'telephone'=>$tem['telephone'],
				'delivery_within_days'=>$tem['delivery_within_days'],
			];
			$store['order_total'] = pdo_fetchcolumn('select count(1) from '.tablename('rhinfo_service_order').' where sid = :sid and uniacid = :uniacid',[':sid'=>$this->store_id,':uniacid'=>$this->uniacid]);
			
			$store['receive_order_total'] = pdo_fetchcolumn('select count(1) from '.tablename('rhinfo_service_order').' where sid = :sid and uniacid = :uniacid and status = 2',[':sid'=>$this->store_id,':uniacid'=>$this->uniacid]); 
			
			jsonReturn(0,'',$store);
			
		}
		//修改店铺信息
		public function edit(){
			global $_GPC;
			$data = [
				'title'	=>	getvar('title'),
				'mobile'=>	getvar('mobile'),
				'time'	=>	getvar('time'),
			];
			$validate = new \validate\Business($data);
			$error = $validate->scene('edit')->valid();
			if($error['errno'] > 0){
				$msg = explode(',',$error['message']);
				jsonReturn(43,$msg[0]);	
			}
			if(strpos($data['time'],'|')){
				$tem = explode('|',$data['time']);
				$data['time']['s'] = $tem[0];
				$data['time']['e'] = $tem[1];
			}else{
				jsonReturn(43,'营业时间错误');
			}
			
			if(!empty($_FILES['image'])){
				
				$image = supload($_FILES['image']);
				if(!$image['success']){
					jsonReturn(43,$image['message']);
				}
				$data['logo'] = $image['path'];
			}
			if(!empty($_GPC['content'])) $data['content'] = getvar('content');
			$m_Store = new \model\Store($this->uniacid);
			$res = $m_Store->storeUpdate($this->store_id,$data);
			if(empty($res)){
				jsonReturn(1,'服务繁忙,请重试');
			}
			jsonReturn(0,'修改成功');
		}
		//店铺用户
		public function member(){
			$Store = new \model\Store($this->uniacid);
			$member = $Store->storeMember($this->store_id);
			
			jsonReturn(0,'',['members'=>$member]);
		}
		
		//店铺评价
		public function comment(){
			
			$where = " where uniacid = :uniacid and sid = :sid";
			$param = [
				'uniacid'	=>	$this->uniacid,
				'sid'		=>	$this->store_id,
			];
			$comment = pdo_fetchall('select * from '.tablename('rhinfo_service_order_comment').$where." order by id desc",$param);
			if(!empty($comment)){
				$comment_status = order_comment_status();
				foreach($comment as $k=>$v){
					$v['data'] = iunserializer($v['data']);
					$v['score'] = ($v['delivery_service'] + $v['goods_quality']);
					$v['addtime'] = date('Y-m-d H:i', $v['addtime']);
					$v['replytime'] = date('Y-m-d H:i', $v['replytime']);
					$v['mobile'] = str_replace(substr($v['mobile'], 3, 6), '******', $v['mobile']);
					$v['avatar'] = tomedia($v['avatar']) ? tomedia($v['avatar']) : MODULE_URL. 'template/mobile/microe/default/static/img/head.png';
					$v['thumbs'] = iunserializer($v['thumbs']);
					if(!empty($v['thumbs'])) {
						foreach($v['thumbs'] as &$item) {
							$item = tomedia($item);
						}
					}
					$v['status_cn'] = $comment_status[$v['status']]['text'];
					$comment[$k] =$v;
				}
			}
			jsonReturn(0,'',['comments'=>$comment]);
		}
		//回复评论
		public function replycomment(){
			$id = getvar('id');
			$comment = pdo_get('rhinfo_service_order_comment',['id'=>$id,'uniacid'=>$this->uniacid,'sid'=>$this->store_id]);
			if(empty($comment)) jsonReturn(1,'评论不存在或已删除');
			
			$reply = getvar('content');
			$update = [
				'reply' => trim($reply),
				'replytime' => TIMESTAMP,
			];
			pdo_update('rhinfo_service_order_comment',$update,['id'=>$id]);
			
			jsonReturn(0,'回复成功');
			
		}
	}