<?php
	/*Desc:商户验证类
	**Author:sz
	**Date:2019/11/4
	**Time:13:58
	*/
	namespace validate;
	include_once MODULE_ROOT.'/inc/inter/src/validate/Validate.php';
	class Business extends Validate{
		public $rules = [

			'phone'	=>	'required|mobile',

			'pass'	=>	'required',
			
			'newpass'	=>	'required',
			
			'checkpass'=>'required|same:newpass',
			
			'title'	=>	'required|min:2|max:30',
			
			'content'=>	'max:200',
			
			'mobile'=> 	'required|mobile',
			
			'time'	=>	'required',
		];
		public $message= [
			
			'phone.required' => '手机号为必填项',

			'phone.mobile'	 =>	'手机号格式错误',

			'pass.required'	 =>	'请输入密码',
			
			'newpass.required'=>'请输入新密码',
			
			'checkpass.required'=>'请确认密码',
			
			'checkpass.same' =>	'两次密码不一致',	
			
			'title.required' =>	'店铺名必填',
			
			'title.min'		 =>	'店铺名2~30个字',
			
			'title.max'		 =>	'店铺名2~30个字',
			
			'content.max'	 =>	'店铺简介不能超过200个字',
			
			'mobile.required'=>	'店铺电话必填',
			
			'mobile.mobile'	 =>	'手机号格式错误',
			
			'time.required'	 =>	'店铺营业时间必填',
		];

		public $scene = ['login'=>['phone','pass'],'edit'=>['title','mobile','time'],'pass'=>['pass','newpass','checkpass']];
		
		
		
	}