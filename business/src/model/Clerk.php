<?php
	/*Desc:
	**Author:sz
	**Date:2019/11/04
	**Time:14:32
	*/
	namespace model;
	class Clerk extends Model{
		protected $tableName = 'rhinfo_service_clerk';

        protected $primaryKey = 'id';

		protected $uniacid;

		public function __construct($uniacid){

			parent::__construct();

			$this->uniacid = $uniacid;	

		}
		//商户登录
		public function manager_login($phone,$pass){
			
			$salt = $this->query->from($this->tableName)->where(['uniacid'=>$this->uniacid,'mobile'=>$phone])->getcolumn('salt');
			if(empty($salt)) return false;
			$password = md5(md5($salt . $pass).$salt);
			$manager = $this->query->from($this->tableName,'c')
									->innerjoin('rhinfo_service_store_clerk','sc')
									->on(['c.id'=>'sc.clerk_id'])
									->select(['c.id'])
									->where(['c.uniacid'=>$this->uniacid,'c.mobile'=>$phone,'c.password'=>$password,'sc.role'=>'manager'])
									->get();
			return $manager;
		}
		//获取商户id
		public function clerk2sid($clerk_id){
			
			$clerk =$this->query->from($this->tableName,'c')
						->innerjoin('rhinfo_service_store_clerk','sc')
						->on(['c.id'=>'sc.clerk_id'])
						->where(['c.id'=>$clerk_id,'uniacid'=>$this->uniacid])
						->get();
			return $clerk;
		}
		
		//修改管理密码
		/*
		**@param clerk 管理员id new 新密码
		**@return 
		*/
		public function clerkEditPass($clerk,$new){
			$salt = random(6);
			$pass = md5(md5($salt . $new).$salt);
			return pdo_update($this->tableName,['salt'=>$salt,'password'=>$pass],['id'=>$clerk,'uniacid'=>$this->uniacid]);
		}
	}