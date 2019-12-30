<?php
/*Desc:token
**Author:sz
**Date:2019/10/21
**Time:15:43
*/
	namespace service;
	class Token{
		protected $token;
		protected $salt = 'sz';//chr(mt_rand(97,122)).chr(mt_rand(97,122)).chr(mt_rand(97,122)).chr(mt_rand(97,122));
		public function maketoken(){
			
			$token = md5('token'.microtime(true).$this->salt);
			$this->token = $token;
			cache_write($token,['value'=>$token,'express_in'=>7200,'create_time'=>time()]);
			
		}
		public function gettoken(){
			$this->maketoken();
			//$token = cache_load($token);
			return $this->token;
		}
		
		public function veriftytoken($verifty){
			$token = cache_load($token);
			if($token['value'] == strval($verifty)){
				if(($token['express_in']+$token['create_time']) >= time()){
					cache_delete($verifty);
					$token['create_time'] = time();
					cache_write($token['value'],$token);
					return true;
				}else{
					
					throw new Exception('token 已失效');
					
				}
				
			}else{
				throw new Exception('token 错误');
				
			}
			return false;
		}
		
	}