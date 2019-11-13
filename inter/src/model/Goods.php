<?php

    /*Desc:商品模型

    **Author:sz

    **Date:2019/10/15

    **Time:10:09

    */

    namespace model;

    class Goods extends \We7Table{

		

		protected $tableName = 'rhinfo_service_goods';

        protected $primaryKey = 'id';

		protected $uniacid;

		public function __construct($uniacid){

			parent::__construct();

			$this->uniacid = $uniacid;

        }
		//商户商品分类
		public function goodsCategory($sid){
			
			$category = $this->query->from($this->tableName,'g')
						->innerjoin('rhinfo_service_goods_category','c')
						->on(['g.cid'=>'c.id'])
						->select(['distinct g.cid','c.title'])
						->where(['c.status'=>1,'c.uniacid'=>$this->uniacid,'c.sid'=>$sid])
						->getall();
			return 	$category;
		}

        //商户下的商品

        public function storeGoods($sid,$cid,$field=["*"]){

            $goods = $this->query->from($this->tableName,'g')->select($field)

                        ->where(['status'=>1,'sid'=>$sid,'uniacid'=>$this->uniacid,'cid'=>$cid])

                        ->orderby('displayorder','desc')

                        ->getall();
			if(!empty($goods)){
				$goods = array_map(function($v){
					if(!empty($v['thumb'])) $v['thumb'] = tomedia($v['thumb']);
					if(!empty($v['is_options'])) {
						$v['options'] = $this->query->from('rhinfo_service_goods_options')->select(['id','name','price','total'])->where(['uniacid'=>$this->uniacid,'goods_id'=>$v['id']])->orderby('displayorder','desc')->getall('id');
					}
					return $v;
				},$goods);
				
			}
			
            return empty($goods)?[]:$goods;

        } 
        //商品详细信息
        public function goodDetail($goods_id,$field=['*'],$where=[]){
            $w = ['g.uniacid'=>$this->uniacid,'g.id'=>$goods_id,'g.status'=>1];
            $where = array_merge($w,$where);
            $goods =$this->query->from($this->tableName,'g')
                        ->innerjoin('rhinfo_service_goods_category','c')
                        ->on(['g.cid'=>'c.id'])
                        ->innerjoin('rhinfo_service_store','s')
                        ->on(['g.sid'=>'s.id'])
                        ->select($field)
                        ->where($where)
                        ->get();
            if(!empty($goods)){
                //是否启用规格
                if($goods['is_options']){
                    //商品规格
                    $goods['options'] = $this->query->from('rhinfo_service_goods_options')->where(['uniacid'=>$this->uniacid,'goods_id'=>$goods['id']])->orderby('displayorder','desc')->getall('id');
                }
                //缩略图
                if(!empty($goods['thumb'])) $goods['thumb'] = tomedia($goods['thumb']);
                //轮播图
                if(!empty($goods['slides'])) {
                    $goods['slides'] = array_map(function($v){
                        return tomedia($v);
                    },iunserializer($goods['slides']));
                }
            }
            return $goods;         
        }

    }