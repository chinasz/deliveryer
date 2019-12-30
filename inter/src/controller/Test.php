<?php
	/*Desc:测试
	**
	**
	*/
	namespace controller;
	class Test{
		
		public function test(){
			global $_W,$_GPC;
			// $token = new \service\Token;
			// $a = $token->gettoken();
			// var_dump($a);
		
			//jsonReturn(200,'',$_GPC);
			// var_dump($_FILES['file']);die;
			//测试接口
			// $t = '';
			// $form ="
			// 	<form method='post'>
			// 	<input type='hidden' name='t' value='test'/>
			// 	url:<input name='url'/>控制器/方法名 如:test/test<br>
			// 	提交方式:<select name='ac'><option value='1'>GET</option><option value='2'>POST</option></select><br>
			// 	参数:<input name='param'/>param=value&param=value....<br>
			// 	<input type='submit' value='提交'/>
			// 	</form>
			// ";
			// echo $form;
			// $t = getvar('t');
			// if($t == 'test'){
			// 	$ac_arr = [1=>'GET',2=>'POST'];
			// 	$url = getvar('url');
			// 	$ac = getvar('ac');
			// 	$param = getvar('param');
			// 	$post = [];
			// 	if(!empty($param)){
			// 		if(strrpos($param,'&')>0){
			// 			$param = explode('&',$param);
			// 			for($i=0;$i<count($param);$i++){
			// 				if(strrpos($param[$i],'=')>0){
			// 					$tem = explode('=',$param[$i]);
			// 					$post[$tem[0]] = $tem[1] ; 
			// 				}
			// 			}
			// 		}else{
			// 			if(strrpos($param,'=')>0){
			// 				$tem = explode('=',$param);
			// 				$post[$tem[0]] = $tem[1];
			// 			}

			// 		}
					
			// 	}
			// 	load()->func('communication'); 
			// 	if($url) {
			// 		$u="http://jiazheng.it-xd.cn/app/index.php?i=1&c=entry&do=api&m=rhinfo_service";
			// 		$url = explode('/',$url);
			// 		$url = $u."&act={$url[0]}&ctrl={$url[1]}";
			// 	}
			// 	$response = ihttp_request($url, $post);
			// 	var_dump($response);
			// }
		}
	}