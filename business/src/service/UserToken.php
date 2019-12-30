<?php
	namespace service;
	class UserToken{
		protected $token;
		// protected $salt = random(6);
		
		public function maketoken($uid){
			$salt = random(6);
			$t = microtime(true);
			$token = md5('bussiness'.$uid.$t.$salt);
			$this->token = $token;
			cache_write($token,['express_in'=>30*24*60*60,'create_time'=>time(),'uid'=> $uid,'t'=>$t,'salt'=>$salt]);

			return $this->token;
			
		}
		
		public function veriftytoken($verifty){
			$token = cache_load($verifty);
			$verifty_token = md5('bussiness'.$token['uid'].$token['t'].$token['salt']);
			if(!empty($token) && $verifty_token == strval($verifty)){
				if(($token['express_in']+$token['create_time']) >= time()){
					cache_delete($verifty);
					$token['create_time'] = time();
					cache_write($verifty,$token);
					return true;
				}else{
					
					// throw new \Exception('token 已失效');
					return false;
					
				}
				
			}else{
				// throw new \Exception('token 错误');
				return false;
			}
			return false;
		}
	}