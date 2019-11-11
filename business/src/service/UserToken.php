<?php
	namespace service;
	class UserToken{
		protected $token;
		// protected $salt = random(6);
		
		public function maketoken($uid){
			
			$token = md5('utoken'.$uid.microtime(true).random(6));
			$this->token = $token;
			cache_write($token,['value'=>$token,'express_in'=>30*24*60*60,'create_time'=>time(),'uid'=> $uid]);

			return $this->token;
			
		}
		
		public function veriftytoken($verifty){
			$token = cache_load($verifty);

			if(!empty($token) && $token['value'] == strval($verifty)){
				if(($token['express_in']+$token['create_time']) >= time()){
					cache_delete($verifty);
					$token['create_time'] = time();
					cache_write($token['value'],$token);
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