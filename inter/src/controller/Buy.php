<?php
    /*Desc:购物车控制器
    **Author:sz
    **Date:2019/10/25
    **Time:15:56
    */
    namespace controller;
    class Buy extends Auth{
        //加入购物车
        public function addcart(){
            global $_W,$_GPC;

            $goods = getvar('goods');
            $sid = getvar('sid');
			$m_Store = new \model\Store($this->uniacid);
            if(empty($goods) && strrpos('|',$goods)<0 ) jsonReturn(1,'参数错误');
            if(strrpos($goods,',')) $goods = explode(',',$goods);
			
            if(empty($sid)) jsonReturn(1,'参数错误');
			$store = $m_Store->storeInfo($sid);
			if(empty($store)) jsonReturn(1,'店铺不存在或已被停用');
            $m_Goods = new \model\Goods($this->uniacid);
            $m_Cart = new \model\Cart($this->uniacid);
            //验证商品
            $data = [];
            if(is_array($goods)){

                $total_box_price = 0;
                $total_price = 0;
                $total = 0;
                foreach($goods as $k=>$good){
                    //商品id,规格id,商品数量
                    list($id,$option,$num) = explode('|',$good);
                    $info = $m_Goods->goodDetail($id,['g.*'],['s.id'=>$sid]);
                    //验证商品
                    if(empty($info)){
                        continue;
                    }
                    //验证商品数量
                    if(empty($num) && $num < 0 && ($info['total'] < $num || $info['total'] != -1)){
                        continue;
                    }
                    //商品价格
                    $price = $info['price'] * $num;  

                    //                        
                    $box_price = $info['box_price'] * $num;
                    /* */
                    $data[$k]['discount_num'] = 0;
                    $data[$k]['price_num'] = NULL;
                    $data[$k]['bargain_id'] = 0;
                    /* */

                    $data[$k]['title'] = $info['title'];
                    $data[$k]['num'] = $num;
                    $data[$k]['option_title'] = '';
                    $data[$k]['price'] = $info['price'];
                    $data[$k]['discount_price'] = $info['price']; 
                    $data[$k]['total_price'] = $price;
                    $data[$k]['total_discount_price'] = $price;  

                    //商品是否启用规格
                    if($info['is_options']){
                        //验证规格
                        if(empty($info['options'][intval($option)])){
                           
                            continue;
                        } 
                        //当前规格
                        $current = $info['options'][intval($option)];
                        //规格数量
                        if($current['total'] < $num && $current['total'] != -1){
                            continue;
                        }
                        //商品价格=规格价格 
                        $price = $current['price'] * $num;
                        //
                        //生成存储数据
                        $data[$k]['title'] = $info['title']."(".$current['name'].")";
                        $data[$k]['option_title'] = $current['name'];
                        $data[$k]['price'] = $current['price'];
                        $data[$k]['discount_price'] = $current['price']; 
                        $data[$k]['total_price'] = $price;
                        $data[$k]['total_discount_price'] = $price;    
                    }
                    
                    //
                    $total += $num;
                    $total_price += $price;
                    $total_box_price += $box_price;
                }
            }else{

                list($id,$option,$num) = explode('|',$goods);           
                $info = $m_Goods->goodDetail($id,['g.*'],['s.id'=>$sid]); 
                //验证商品
                if(empty($info)){
                    jsonReturn(1,'商品参数错误');
                }
                //验证商品数量
                if(empty($num) && $num < 0 && ($info['total'] < $num || $info['total'] != -1)){
                    jsonReturn(1,'数量参数错误');
                }
                //商品价格
                $price = $info['price'] * $num;  

                //                        
                $box_price = $info['box_price'] * $num;
                /* */
                $data['discount_num'] = 0;
                $data['price_num'] = NULL;
                $data['bargain_id'] = 0;
                /* */

                $data['title'] = $info['title'];
                $data['num'] = $num;
                $data['option_title'] = '';
                $data['price'] = $info['price'];
                $data['discount_price'] = $info['price']; 
                $data['total_price'] = $price;
                $data['total_discount_price'] = $price;  

                //商品是否启用规格
                if($info['is_options']){
                    $current = $info['options'][intval($option)];
                    //验证规格
                    if(empty($info['options'][intval($option)])){
                        jsonReturn(1,'规格参数错误');
                    } 
                    //当前规格
                    $current = $info['options'][intval($option)];
                    //var_dump($current);die;
                    //规格数量
                    if($current['total'] != -1){
                        jsonReturn(1,'数量参数错误');
                    }
                    //商品价格=规格价格 
                    $price = $current['price'] * $num;
                    //
                    //生成存储数据
                    $data['title'] = $info['title']."(".$current['name'].")";
                    $data['option_title'] = $current['name'];
                    $data['price'] = $current['price'];
                    $data['total_price'] = $price;
                    $data['discount_price'] = $current['price']; 
                    $data['total_discount_price'] = $price;    
                }
                //
                $total = $num;
                $total_price = $price;
                $total_box_price = $box_price;
                $data = [$data];
            }
            if(empty($data)) jsonReturn(1,'参数错误');

            $insert = [
                'uniacid'   =>  $this->uniacid,
                'sid'       =>  $sid,
                'uid'       =>  $this->uid,
                'groupid'   =>  $_W['member']['groupid'],
                'num'       =>  $total,
                'price'     =>  $total_price,
                'data'      =>  iserializer([$data]),
                'addtime'   =>  TIMESTAMP,
                'box_price' =>  $total_box_price,
                'original_data'=>iserializer([$data])
            ];
            
            $cart = $m_Cart->showMemberCart($this->uid,$sid);
            if(empty($cart)){
                pdo_insert('rhinfo_service_order_cart',$insert);
				$cartid = pdo_insertid();
            }else{
                pdo_update('rhinfo_service_order_cart',$insert,['id'=>$cart['id']]);
				$cartid = $cart['id'];
            }
            jsonReturn(0,'加入成功',['cartid'=>$cartid]);
        }
		//提交订单页面
		public function getCart(){
			$sid = getvar('sid');
			$m_Store = new \model\Store($this->uniacid);
			$store = $m_Store->storeInfo($sid);
			if(empty($store)) jsonReturn(1,'店铺不存在或已被停用');	
			//店铺支付方式
			$payment_list = $m_Store->storePayment($sid);
			
			if(empty($payment_list)) jsonReturn(1,'店铺没有设置有效的支付方式');
			$output['payment_list'] = $payment_list;
			
			//用户商品
			$m_Cart = new \model\Cart($this->uniacid);
			$cart = $m_Cart->showMemberCart($this->uid,$sid);
			$cart['data'] = $cart['data'][0];
			$output['cart'] = $cart;
			//用户地址
			$m_Address = new \model\Address($this->uniacid);
			$address = $m_Address->memberAvailableAddress($sid,$this->uid);
			$output['address'] = $address;
			//服务时间
			$delivery_time = $m_Store->storeDeliveryTimes($sid);
			if(!empty($delivery_time) && is_array($delivery_time)){
				//pass
			}else{
				jsonReturn(1,'店铺未设置服务时间');
			}
			$output['delivery_time'] = $delivery_time;
			//可用优惠券
			$condition = ' as a left join ' . tablename('rhinfo_service_store') . ' as b on a.sid = b.id where a.uniacid = :uniacid and a.sid = :sid and a.uid = :uid and a.status = 1 and a.`condition` <= :price';
			$params = array(':uniacid' =>$this->uniacid, ':sid' => $sid, ':price' => floatval($cart['price']), ':uid' => $this->uid);
			$coupons = pdo_fetchall('select a.*,b.logo,b.title from ' . tablename('rhinfo_service_activity_coupon_record') . $condition, $params);
			if(empty($coupons)){
				$coupons = array_map(function($v){
					$v['logo'] = tomedia($v['logo']);
					$v['endtime_cn'] = date('Y-m-d', $v['endtime']);
				},$coupons);
			}
			$output['coupons'] = $coupons;
			$output['coupons_num'] = count($coupons);
			//output店铺信息
			$output['store'] = [
				'delivery_type'	=>	$store['delivery_type'],
				'delivery_mode'	=>	$store['delivery_mode'] == 1?'商家服务':'平台服务',
			];
			
			jsonReturn(0,'',$output);
		}
        
    }