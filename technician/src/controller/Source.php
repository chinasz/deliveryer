<?php
	/*Desc:
	**Author:sz
	**Date:2019/11/08
	**Time:9:11
	*/
	namespace controller;
	class Source extends Controller{
		
		public function __empty(){
		
		}
		//服务分类
		public function service(){
			$field = ['title','id'];
			$m_category = new \model\StoreCategory($this->uniacid);
			$category = $m_category->storeAllCategory($field);
			
			jsonReturn(0,'',['categorys'=>$category]);
		}
		
	}