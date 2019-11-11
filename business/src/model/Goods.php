<?php
	/*Desc:商品模型
	**Author:sz
	**Date:2019/11/05
	**Time:14:33
	*/
	namespace model;
	class Goods extends Model{
		protected $tableName = 'rhinfo_service_goods';

        protected $primaryKey = 'id';

		protected $uniacid;

		public function __construct($uniacid){

			parent::__construct();

			$this->uniacid = $uniacid;	
		}
		//店铺商品
		public function storeAllGoods($sid,$field=['*'],$page = 1){
			$size = 10;
			$start= ($page-1) * $size;
			$goods =$this->query->from($this->tableName)
						->where(['uniacid'=>$this->uniacid,'sid'=>$sid])
						->select($field)
						->orderby('displayorder','desc')
						->limit($start,$size)
						->getall();
					
			if(!empty($goods)){
				foreach($goods as $k=>$good){
					if(!empty($good['thumb'])){
						$good['thumb'] = tomedia($good['thumb']);
					}
					if(!empty($good['slides'])){
						$good['slides'] = iunserializer($good['slides']);
						$good['slides'] = array_map(function($v){
							return tomedia($v);
						},$good['slides']);
					}
					$goods[$k]	=	$good;
				}
			}
			return $goods;
		}
		//商品详情
		public function goodsInfo($sid,$gid,$field=['*']){
			
			$good = $this->query->from($this->tableName,'g')
								->innerjoin('rhinfo_service_goods_category','c')
								->on(['g.cid'=>'c.id'])
								->select($field)
								->where(['g.uniacid'=>$this->uniacid,'g.sid'=>$sid,'g.id'=>$gid])
								->get();
			if(!empty($good)){
				//缩略图
				if(!empty($good['thumb'])) $good['thumb'] = tomedia($good['thumb']);
				//轮播图
				if(!empty($good['slides'])){
					$good['slides'] = iunserializer($good['slides']);
					$good['slides'] = array_map(function($v){
						return tomedia($v);
					},$good['slides']);
				}
				if(!empty($good['is_options'])){
					//商品规格
					$goods['options'] = $this->query->from('rhinfo_service_goods_options')->where(['uniacid'=>$this->uniacid,'goods_id'=>$gid,'sid'=>$sid])->orderby('displayorder','desc')->getall();
				}
			}
			return $good;
		}
		
		//修改商品信息
		public function editGoods($gid,$data){
			
			$update = [
				'cid'	=>	intval($data['cid']),
				'title'	=>	$data['title'],
				'price'	=>	floatval($data['price']),
				'total'	=>	$data['total'],
				'status'=>	intval($data['status']),
			];
			if(!empty($data['thumb'])) $update['thumb'] = $data['thumb'];
			if(!empty($data['content'])) $update['content'] = trim($data['content']);
			if(!empty($data['description'])) $update['description'] = trim($data['description']);
			
			return pdo_update($this->tableName,$update,['id'=>$gid,'uniacid'=>$this->uniacid]);
			
			
		}
		//商品分类
		public function goodsCategory($sid,$field=['*']){
			
			$cate = $this->query->from('rhinfo_service_goods_category')
						->where(['uniacid'=>$this->uniacid,'sid'=>$sid])
						->orderby('displayorder','desc')
						->getall();
			
			return $cate;
		}
		//新增商品
		public function addGoods($sid,$data){
			
			$insert = [
				'uniacid'	=>	$this->uniacid,
				'sid'		=>	$sid,
				'cid'		=>	intval($data['cid']),
				'title'		=>	trim($data['title']),
				'price'		=>	floatval($data['price']),
				'total'		=>	$data['total'],
				'status'	=>	intval($data['status']),
				'thumb'		=>	trim($data['thumb']),
			];
			
			if(!empty($data['content'])) $update['content'] = trim($data['content']);
			if(!empty($data['description'])) $update['description'] = trim($data['description']);
			
			return pdo_insert($this->tableName,$insert);
			
		}
		//删除商品
		public function delGoods($sid,$gid){
			$goods = $this->goodsInfo($sid,$gid);
			if(empty($goods)) return false;
			pdo_delete($this->tableName,['uniacid'=>$this->uniacid,'sid'=>$sid,'id'=>$gid]);
			//删除商品规格
			if(!empty($goods['is_options'])){
				pdo_delete('rhinfo_service_goods_options',['uniacid'=>$this->uniacid,'sid'=>$sid,'goods_id'=>$gid]);
			}
			return true;
		}
		
	}