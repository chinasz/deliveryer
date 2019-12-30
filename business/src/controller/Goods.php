<?php
	/*Desc:商品
	**Author:sz
	**Date:2019/11/05
	**Time:14:26
	*/
	namespace controller;
	class Goods extends Auth{
		public function __empty(){
			jsonReturn(43,'url 错误');
			
		}
		//商品列表
		public function show(){
			$page = intval(getvar('p'));
			$page = $page >1?$page:1;
			$m_Goods = new \model\Goods($this->uniacid);
			$field = ['id','title','price','sailed','thumb','description','unitname','status','content'];
			$goods = $m_Goods->storeAllGoods($this->store_id,$field,$page);
			jsonReturn(0,'',['goods'=>$goods]);
		}
		//商品详情
		public function info(){
			$id = getvar('id');
			if(empty($id)) jsonReturn(1,'参数错误');
			$m_Goods = new \model\Goods($this->uniacid);
			$field = ['g.id','g.title','g.price','g.is_options','g.unitname','g.total','g.status','g.content','g.sailed','g.displayorder','g.description','g.commitment','g.old_price','c.title as cname','g.cid'];
			$good = $m_Goods->goodsInfo($this->store_id,$id,$field);
			jsonReturn(0,'',['good'=>$good]);
		}
		//修改商品信息
		public function edit(){
			$id = getvar('id');
			if(empty($id)) jsonReturn(1,'参数错误');
			$m_Goods = new \model\Goods($this->uniacid);
			$good = $m_Goods->goodsInfo($this->store_id,$id);
			if(empty($good)) jsonReturn(1,'商品不存在或已删除');
			
			
			$data = [
				'title'	=>	getvar('title'),
				'cid'	=>	getvar('cid'),
				'price'	=>	getvar('price'),
				'total'	=>	getvar('total'),
				'status'=>	getvar('status'),
			];
			$validate = new \validate\Goods($data);
			$error = $validate->scene('edit')->valid();
			if($error['errno'] > 0){
				
				$msg = explode(',',$error['message']);
				jsonReturn(43,$msg[0]);	
			}
			
			//商品图片
			if(!empty($_FILES['img'])){
				$image = supload($_FILES['img']);
				if(!$image['success']){
					jsonReturn(43,$image['message']);
				}
				$data['thumb'] = $image['path'];
			}
			
			$data['content'] = getvar('content');
			$data['description'] = getvar('description');
			
			
			$res = $m_Goods->editGoods($id,$data);
			if(empty($res)) jsonReturn(1,'服务繁忙请重试');
			jsonReturn(0,'编辑成功');
		}
		
		//新增商品
		public function create(){
			$data = [
				'title'	=>	getvar('title'),
				'cid'	=>	getvar('cid'),
				'price'	=>	getvar('price'),
				'thumb'	=>	$_FILES['img'],
				'total'	=>	getvar('total'),
				'status'=>	getvar('status'),
			];
			$validate = new \validate\Goods($data);
			$error = $validate->scene('create')->valid();
			if($error['errno'] > 0){
				
				$msg = explode(',',$error['message']);
				jsonReturn(43,$msg[0]);	
			}
			//商品图片

			$image = supload($_FILES['img']);
			if(!$image['success']){
				jsonReturn(43,$image['message']);
			}
			$data['thumb'] = $image['path'];
			
			$data['content'] = getvar('content');
			$data['description'] = getvar('description');
			
			$m_Goods = new \model\Goods($this->uniacid);
			$res = $m_Goods->addGoods($this->store_id,$data);
			if(empty($res)) jsonReturn(1,'服务繁忙请重试');
			jsonReturn(0,'添加成功');
			
		}
		//删除商品
		public function del(){
			$id = getvar('id');
			$m_Goods = new \model\Goods($this->uniacid);
			$res = $m_Goods->delGoods($this->store_id,$id);
			if(empty($res)) jsonReturn(1,'服务繁忙请重试');
			jsonReturn(0,'删除成功');
		}
	}