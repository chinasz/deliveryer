<?php

	/*Desc:

	**Author:sz

	**Date:2019/10/22

	**Time:15:41

	*/

	namespace controller;

	class Show{

		public $uniacid;

		public function __construct(){

			global $_W,$_GPC;

			$this->uniacid = $_W['uniaccount']['uniacid'];
		}

		//商户分类

		public function storecategory(){
			//$store_label = store_fetchall_category();
			
			$m_Store = new \model\Store($this->uniacid);

			$categorys = $m_Store->getAllCategory(['id','title','thumb']);



			jsonReturn(0,'',$categorys);

		}
		//商户分类和商户

		public function categoryandstore(){

			global $_W,$_GPC; 

			$m_Store = new \model\Store($this->uniacid);

			

			$data = $m_Store->getCategoryAndStore(0,3);

			jsonReturn(0,'',$data);

		}
		//店铺首页
		public function store(){
			$sid = intval(getvar('id'));
			$token = new \service\UserToken;
			$t = getvar('token');
			$is_favorite = false;
			$m_Store = new \model\Store($this->uniacid);
			$uid = 0;
			if(!empty($t)){
				$v = $token->veriftytoken($t);
				if($v){
					$uid = cache_load($t);
					$uid = $uid['uid'];
					//用户是否 收藏店铺
					$is_favorite = $m_Store->isFavorite($uid,$sid);
					
				}
			}
			$store_field = ['id','title','business_hours'];
			$store = $m_Store->storeInfo($sid,$store_field);
			if(empty($store)) jsonReturn(1,'店铺不存在或已被停用');
			
			$m_Goods = new \model\Goods($this->uniacid);
			
			$category = $m_Goods->goodsCategory($sid);
			$category = array_map(function($v) use($sid,$m_Goods){
				$goods_field = ['id','title','price','unitname','content','sailed','thumb','is_options'];
				$v['goods'] = $m_Goods->storeGoods($sid,$v['cid'],$goods_field);
				return $v;
			},$category);
			$ouput = ['store'=>$store,'is_favorite'=>$is_favorite,'category'=>$category];
			jsonReturn(0,'',$ouput);
		}
		//店铺分类下的商品
		public function goods(){
			$sid = intval(getvar('id'));
			$cid = intval(getvar('cid'));
			$m_Store = new \model\Store($this->uniacid);
			$store = $m_Store->storeInfo($sid);
			if(empty($store)) jsonReturn(1,'店铺不存在或已被停用');
			$m_Goods = new \model\Goods($this->uniacid);
			$goods_field = ['id','title','price','unitname','content','sailed'];
			$goods = $m_Goods->storeGoods($sid,$cid,$goods_field);
			jsonReturn(0,'',$goods);
		}
		

		//帮助中心

		public function helps(){

			$helps = pdo_fetchall('select * from ' . tablename('rhinfo_service_help') . ' where uniacid = :uniacid order by displayorder desc, id asc', array(':uniacid' => $this->uniacid));

			

			jsonReturn(0,'',$helps);

		}

		

		//公众号二维码

		public function qrcode(){

			$barcode = array(

				'expire_seconds' => 24*60*60,

				'action_name' => 'QR_SCENE',

				'action_info' => array(

					'scene' => array(

						'scene_id' => 123

					),

				),

			);

			$account_api = \WeAccount::create();

			$result = $account_api->barCodeCreateDisposable($barcode);			

			if(!is_error($result)){		

				jsonReturn(1,'二维码生成失败',$result);

			}

			jsonReturn(0,'',$result);	

		}

		//热搜
		public function hotsearch(){
			global $_W;
			$stores = pdo_fetchall('select id, title from ' . tablename('rhinfo_service_store') . ' where uniacid = :uniacid and agentid = :agentid and status = 1 order by click desc, displayorder desc limit 4', array(':uniacid' => $this->uniacid, ':agentid' => $_W['agentid']));
			jsonReturn(0,'',$stores);
		}
		//搜索
		public function search(){
			global $_W,$_GPC;
			mload()->model('store');
			$keyword = trim(getvar('keyword'));
			$sids = [0];
			$sids_str = 0;
			$stores = [];
			$store_goods = [];
			if(empty($keyword)) jsonReturn(1,'搜索关键字不能为空');
			$goods = pdo_fetchall('select * from ' . tablename('rhinfo_service_goods') . ' where uniacid = :uniacid and status = 1 and title like :key', array(':uniacid' => $this->uniacid, ':key' => '%' . $keyword . '%'));
			if (!empty($goods)) {
				foreach ($goods as $good) {
					$sids[] = $good['sid'];
					$good['discount_price'] = $good['price'];
					$store_goods[$good['sid']][] = $good;
				}
				$sids_str = implode(',', $sids);
				$stores = pdo_fetchall('select id,title,logo,content,business_hours,delivery_fee_mode,sailed,score,delivery_price,delivery_areas,send_price,delivery_time,delivery_mode,forward_mode,forward_url from ' . tablename('rhinfo_service_store') . ' where uniacid = :uniacid and agentid = :agentid and status = 1 and id in (' . $sids_str . ')', array(':uniacid' => $_W['uniacid'], ':agentid' => $_W['agentid']), 'id');
			}
			// var_dump($goods);die;

			$search_stores = pdo_fetchall('select id,title,logo,content,business_hours,delivery_fee_mode,sailed,score,delivery_price,delivery_areas,send_price,delivery_time,delivery_mode,forward_mode,forward_url from ' . tablename('rhinfo_service_store') . ' where uniacid = :uniacid and agentid = :agentid and status = 1 and id not in (' . $sids_str . ') and title like :key', array(':uniacid' => $_W['uniacid'], ':agentid' => $_W['agentid'], ':key' => '%' . $keyword . '%'));
			$stores = array_merge($search_stores, $stores);
			foreach ($stores as &$row) {
				$row['goods'] = $store_goods[$row['id']];
				// $row['activity'] = store_fetch_activity($row['id'], array('discount'));
				// $row['url'] = store_forward_url($row['id'], $row['forward_mode'], $row['forward_url']);
				$row['score_cn'] = round($row['score'] / 5, 2) * 100;		
		
				if ($row['delivery_fee_mode'] == 2) {
					$row['delivery_price'] = iunserializer($row['delivery_price']);
					$row['delivery_price'] = $row['delivery_price']['start_fee'];
				}
				else {
					if ($row['delivery_fee_mode'] == 3) {
						$row['delivery_areas'] = iunserializer($row['delivery_areas']);
		
						if (!is_array($row['delivery_areas'])) {
							$row['delivery_areas'] = array();
						}
		
						$price = store_order_condition($row);
						$row['delivery_price'] = $price['delivery_price'];
						$row['send_price'] = $price['send_price'];
					}
				}
			}
			jsonReturn(0,'',$store_goods);

		}
		//商品详情
		public function goodsInfo(){
			$goods_id = getvar('id');
			$field = ['g.id','g.title','g.price','g.is_options','g.unitname','g.label','g.thumb','g.slides','g.description','g.commitment','g.content','g.old_price','g.sailed','g.min_buy_limit','g.total','g.comment_good','g.comment_total','c.title as ctitle'];
			$m_Goods = new \model\Goods($this->uniacid);
			$goods = $m_Goods->goodDetail($goods_id);
			
			if(empty($goods)){
				jsonReturn(1,'商品已下架或被删除');
			}
			$goods['comment_good_percent'] = empty($goods['comment_total'])?'0%':round($goods['comment_good'] / $goods['comment_total'] * 100, 2) . '%';
			jsonReturn(0,'',$goods);
		}
		//商品规格
		public function goodsOptions(){
			$goods_id = getvar('id');
			$field = ['g.is_options','g.id'];
			$m_Goods = new \model\Goods($this->uniacid);
			$goods = $m_Goods->goodDetail($goods_id,$field);
			if(empty($goods)){
				jsonReturn(1,'商品已下架或被删除');
			}
			jsonReturn(0,'',$goods);
		}
		//首页幻灯片
		public function slide(){
			
			$sql = "select thumb from ".tablename('rhinfo_service_slide')." where uniacid = :uniacid and type = 2 and status = 1 order by displayorder desc"; 
			
			$slides = pdo_fetchall($sql,[':uniacid'=>$this->uniacid]);
			if(!empty($slides)){
				
				$slides = array_map(function($v){
					$v['thumb'] = tomedia($v['thumb']);
					return $v;
				},$slides);
			}
			jsonReturn(0,'',['slides'=>$slides]);
		}
		//分类下的商户
		public function storebycate(){
			$cid = intval(getvar('cid'));
			$page = intval(getvar('page'));
			if(empty($cid)) jsonReturn(1,'参数错误');
			$m_Store = new \model\Store($this->uniacid);
			$page = empty($page)?0:$page;
			$stores = $m_Store->storeByCategory($cid,$page);
			jsonReturn(0,'',$stores);
		}
		//分享
		public function share(){
			global $_W;
			$t = getvar('token');
			$uid = 0;
			if(!empty($t)){
				$token = new \service\UserToken;
				$v = $token->veriftytoken($t);
				if($v){
					$uid = cache_load($t);
					$uid = $uid['uid'];
				}
			}
			if(!is_dir(MODULE_ROOT.'/share')){
				load()->func('file');
				mkdirs(MODULE_ROOT.'/share');
			}
			load()->library('qrcode/phpqrcode');
			if(empty($uid)){
				$share = MODULE_ROOT.'/share/share.png';
				if(!file_is_image($share) || !file_exists($share)){
					$url = murl('entry//',['do'=>'api','m'=>'rhinfo_service','act'=>'index','ctrl'=>'share'],true,true);
					\QRcode::png($url,$share,'L',5,1);
				}
				$img = 'share.png';
			}else{
				$share = MODULE_ROOT.'/share/share'.$uid.'.png';
				$url = murl('entry//',['do'=>'api','m'=>'rhinfo_service','d'=>$uid,'act'=>'index','ctrl'=>'share'],true,true);
				\QRcode::png($url,$share,'L',5,1);
				$img = 'share'.$uid.'.png';
			}
			
			jsonReturn(0,'',['share'=>$_W['siteroot'].'/addons/rhinfo_service/share/'.$img]);
		}
	}