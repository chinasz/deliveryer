<?php
	/*Desc:店铺分类模型
	**Author:sz
	**Date:2019/11/08
	**Time:9:20
	*/
	namespace model;
	class StoreCategory extends Model{
		protected $tableName = 'rhinfo_service_store_category';
		
        protected $primaryKey = 'id';

		protected $uniacid;
		
		public function __construct($uniacid){
			parent::__construct();
			$this->uniacid = $uniacid;
			
		}
		//店铺所有分类
		public function storeAllCategory($field=['*']){
			$categorys = $this->query->from($this->tableName)
									->where(['uniacid'=>$this->uniacid,'status'=>1])
									->select($field)
									->orderby('displayorder','desc')
									->getall();
			if(!empty($categorys)){
				
				$categorys = array_map(function($v){
					if(!empty($v['thumb'])) $v['thumb'] = tomedia($v['thumb']);
					return $v;
				},$categorys);
				
			}
			return $categorys;
		
		}
		//
		public function storeOneCategory($id,$field=['*']){
			$category = $this->query->from($this->tableName)
									->where(['uniacid'=>$this->uniacid,'id'=>$id])
									->select($field)
									->get();
			if(!empty($category)){
				if(!empty($category['thumb'])) $category['thumb'] = tomedia($category);
			}
			return $category;
		}
	}