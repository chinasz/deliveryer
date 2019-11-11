<?php

	/*Desc:资源控制器

	**Author:sz

	**Date:2019/10/24

	**Time:15:17

	*/

	namespace controller;

	class Source extends Auth{

		
		//图片上传
		public function upload(){
			global $_W,$_GPC;
			$file = $_FILES['file'];
			$res = supload($file);
			jsonReturn(0,'',$res);

		}



	}