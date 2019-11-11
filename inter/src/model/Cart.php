<?php

    /*Desc:购物车模型

    **Author:sz

    **Date:2019/10/25

    **Time:18:22

    */
    namespace model;
    class Cart extends \We7Table{



        protected $tableName = 'rhinfo_service_order_cart';

        protected $primaryKey = 'id';

		protected $uniacid;

		public function __construct($uniacid){

			parent::__construct();

            $this->uniacid = $uniacid;	

            

        }
        //加入购物车
        public function memberAddCart($uid,$sid){


        }
        //用户购物车

        public function showMemberCart($uid,$sid){

            $cart = $this->query->from($this->tableName,'c')
                        ->select(['c.data','c.price','c.num','c.box_price','c.id','c.*'])
                        ->where(['c.uid'=>$uid,'c.uniacid'=>$this->uniacid,'c.sid'=>$sid])
                        ->get();
			if(!empty($cart)){
				$cart['data'] = is_serialized($cart['data'])?iunserializer($cart['data']):[];
			}
            return $cart;

        }

        /*删除购物车商品
        **@param $uid 用户id,$sid 商户id
        **@return
        */
        public function delMemberCart($uid,$sid){
            $cart = $this->memberCart($uid,$sid);
            if(!empty($cart)){
                pdo_delete($this->tableName,['id'=>$cart['id']]);
                return true;
            }
            return true;
        }

        /*获取商户用户购物车
        **@param $uid 用户，$sid 商户id
        **@return 
        */
        public function memberCart($uid,$sid,$field=['*']){

            $cart =$this->query->from($this->tableName)
                        ->select($field)
                        ->where(['uniacid'=>$this->uniacid,'uid'=>$uid,'sid'=>$sid])
                        ->get();
            return $cart;
        }


    }