<?php
	/*Desc:验证基类
	**Author:sz
	**Date:2019/10/21
	**Time:14:06
	*/
	namespace validate;
	class Validate{
		public $data = [];
		public $rules = [];
		public $message = [];
		public $scene = [];
		
		public function __construct($data = []){
			$this->data = $data;
		}
		public function scene($scene){
			load()->classs('validator');
			if($this->hasScene($scene)){
				
				// $rules = array_map(function($v){return $v[$v] = $this->rules[$v];},$this->scene[$scene]);
				foreach($this->scene[$scene] as $v){
					
					$rules[$v] = $this->rules[$v];
					
				}
				
				return new \Validator($this->data,$rules,$this->message);
			}else{
				
				throw new \Exception('验证场景错误');
			}
			
		}
		public function hasScene($scene){
			
			return empty($this->scene[$scene])?false:(is_array($this->scene[$scene])?true:false);
			
		}
		
	}