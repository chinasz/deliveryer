<?php

	/*Desc:商品验证

	**Author:sz

	**Date:2019/11/05

	**Time:15:39

	*/

	namespace validate;

	include_once MODULE_ROOT.'/inc/inter/src/validate/Validate.php';

	class Goods extends Validate{

		public $rules = [
			'title'	=>	'required|min:1|max:30',

			'price'	=>	'required|numeric',

			'cid'	=>	'required|int',

			'thumb'	=>	'required',

			'total'	=>	'required',

			'status'=>	'required',

		];

		public $message= [

			'title.required' => '商品名称必须',

			'title.min'	 =>	'商品名称1~30个字',

			'title.max'	 =>	'商品名称1~30个字',

			'price.required' => '商品价格必须',

			'price.numeric'	 =>	'商品价格必须是数字',

			'cid.required'	 =>	'商品分类必须',

			'cid.int'		 =>	'商品分类错误',

			'thumb.required' =>	'商品图必须',

			'total.required' => '库存必须',

			'status.required'=>	'商品状态必须',

		];



		public $scene = ['edit'=>['title','price','cid','total','status'],'create'=>['title','price','cid','total','status','thumb']];

		

		

		

	}