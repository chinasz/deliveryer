<?php

	/*Desc:商户信息验证

	**Author:sz

	**Date:2019/10/21

	**11:04

	*/

	namespace validate;

	class Store extends Validate{

		

		public $rules = [

			'file'	=>	'required|image|max:1',

			'title'	=>	'required|min:2|max:15',

			'name'		=>	'required|min:2|max:15',

			'phone'	=>	'required|mobile',

			'address'	=>	'required',

		];

		public $message= [

			'file.required'	 =>	'请上传身份证照或营业照',

			'file.image'	 => '请上传jpg,png,gif的图片',

			'file.max'		 => '图片太大',

			'title.required' =>	'店铺名为必填项',

			'title.min'	 =>	'店铺名2~15个字',

			'title.max'	 =>	'店铺名2~15个字',

			'name.required'	 =>	'姓名为必填项',

			'name.min'	 => '姓名2~15个字',

			'name.max'	 => '姓名2~15个字',

			'phone.required' => '手机号为必填项',

			'phone.mobile'	 =>	'手机号格式错误',

			'address.required'=>	'地址为必填项',



		];

		public $scene = ['entry'=>['title','name','phone','address']];

		

		

	}