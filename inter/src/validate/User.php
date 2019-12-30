<?php

/*Desc:用户信息验证

**Author:sz

**Date:2019/10/21

**Time:14:55

*/

	namespace validate;

	class User extends Validate{

		

		public $rules = [

			'phone'	=>	'required|mobile',

			'pass'	=>	'required',

			'code'	=>	'required',

			'name'	=>	'required|min:2|max:15',

			'location_x'=>'required',

			'location_y'=>'required',
			
			'address'	=>	'required|max:100',
		];

		public $message= [
			
			'phone.required' => '手机号为必填项',

			'phone.mobile'	 =>	'手机号格式错误',

			'pass.required'	 =>	'请输入密码',
			'code.required'	 =>	'请输入短信验证码',	
			'name.required'	 =>	'联系人为必填',
			'name.min'		 =>	'联系人2~15个字',
			'name.max'		 =>	'联系人2~15个字',
			'location_x.required'=>'获取不到位置信息',
			'location_y.required'=>'获取不到位置信息',
			'address'		 =>	'请填写具体地址',
			'img.required'	 =>	'请选择头像',
		];

		public $scene = ['login'=>['phone','pass'],'code'=>['phone'],'register'=>['phone','pass'],'address'=>['phone','name','location_x','location_y','address'],'password'=>['phone','pass'],'edit'=>['phone','name']];

		

	}