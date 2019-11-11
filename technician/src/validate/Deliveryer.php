<?php
	/*Desc:技师验证
	**Author:sz
	**Date:2019/11/07
	**Time:17:14
	*/
	namespace validate;
	include_once MODULE_ROOT.'/inc/inter/src/validate/Validate.php';
	class Deliveryer extends Validate{
		public $rules = [
			'phone'	=>	'required|mobile',
			'pass'	=>	'required|min:8|max:15',
			'check' =>	'required|same:pass',
			'real'	=>	'required|min:2',
			'sex'	=>	'required|in:1,2',
			'year'	=>	'required|integer',
			'cid'	=>	'required|int|min:1',
		];
		public $message= [
			'phone.required' => '手机号必填',
			'phone.mobile'	 =>	'手机号格式错误',
			'pass.required'	 =>	'密码必填',
			'pass.min'		 =>	'密码8~15位数字字母组合',
			'pass.max'		 =>	'密码8~15位数字字母组合',
			'check.required' =>	'两次密码不一致',
			'check.same'	 => '两次密码不一致',
			'real.required'	 =>	'真实姓名必填',
			'real.min'	 	 =>	'真实姓名不合法',
			'sex.required'	 =>	'性别必填',
			'sex.in'		 =>	'性别不合法',
			'year.required'	 =>	'年龄必填',
			'year.integer'	 =>	'年龄不合法',
			'cid.required'	 =>	'服务类别必填',
			'cid.int'		 =>	'服务类别不合法',
			'cid.min'		 =>	'服务类别不合法',
		];

		public $scene = ['login'=>['phone','pass'],'register'=>['phone','pass','check','real','sex','year','cid']];
		
		
		
	}