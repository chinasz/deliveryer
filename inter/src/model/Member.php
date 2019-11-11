<?php
/*Desc:用户模型
**Author:sz
**Date:2019/10/21
**Time:15:07
*/
	namespace model;
	class Member extends \We7Table{
		
		protected $tableName = 'rhinfo_service_members';
        protected $primaryKey = 'id';
		protected $uniacid;
		public function __construct($uniacid){
			parent::__construct();
			$this->uniacid = $uniacid;
			
		}
		/*Desc:手机号密码登录
		**Author:sz
		**Date:2019/10/21
		**Time:15:11
		*/
		public function loginByPass($data,$field=['id']){
			$salt = $this->query->where(['uniacid'=>$this->uniacid,'mobile'=>$data['phone']])->getcolumn('salt');
			if(!empty($salt)){
				// $pass = md5(md5($salt.trim($data['pass']).$salt);
				$pass = md5($salt.trim($data['pass']));
				$pass = md5($pass.$salt);
				return $this->query->from($this->tableName)->select($field)->where(['mobile'=>$data['phone'],'password'=>$pass])->get();
			}
			return false;
		}
		
		/*Desc:用户注册
		**Author:sz
		**Date:2019/10/21
		**Time:17:19
		*/
		public function newMember($data){
			$member = array('uniacid' => $this->uniacid, 'uid' => date('His') . random(3, true), 'mobile' => $data['phone'], 'mobile_audit' => 1, 'nickname' => 'user'.intval(microtime(true)), 'salt' => random(6, true), 'addtime' => TIMESTAMP, 'register_type' => 'app', 'is_sys' => 2);
			$member['password'] = md5(md5($member['salt'] . trim($data['pass'])) . $member['salt']);

			return pdo_insert('rhinfo_service_members', $member);
			
		}
		
		/*Desc:获取用户信息
		**Author:sz
		**Date:2019/10/21
		**Time:18:15
		*/
		public function getMemeberInfo($uid,$field=['nickname','uid','avatar','credit1','credit2']){
			
			$member = $this->query->select($field)->where(['id'=>intval($uid),'uniacid'=>$this->uniacid])->get();
			if(!empty($member)){
				
				if(!empty($member['avatar'])){
					$member['avatar'] = tomedia($member['avatar']);
					
				}
				
			}
			
			return $member;
		}
		/*Desc:修改密码
		**Author:sz
		**Date:2019/10/30
		**Time:18:36
		*/
		public function RetrievePassword($data){
			$member = $this->query->from($this->tableName)
						->where(['mobile'=>$data['phone'],'uniacid'=>$this->uniacid])
						->get();
			if(empty($member)) jsonReturn(1,'手机号错误');
			$pass = md5($salt.trim($data['pass']));
			$pass = md5($pass.$salt);
			return pdo_update($this->tableName,['password'=>$pass],['id'=>$member['id']]);
		}
		
		/*Desc:修改资料
		**Author:sz
		**Date:2019/11/01
		**Time:10:03
		*/
		public function editMember($uid,$data){
			
			$update = [
				'nickname'	=>	$data['name'],
				'mobile'	=>	$data['phone'],
			];
			if(!empty($data['img'])) $update['avatar']=$data['img'];
			
			return pdo_update('rhinfo_service_members',$update,['id'=>$uid]);
		}
	}