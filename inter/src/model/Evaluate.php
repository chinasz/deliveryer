<?php
	/*Desc:评价模型
	**Author:sz
	**Date:2019/10/23
	**Time:9:08
	*/
	namespace model;
	class Evaluate extends \We7Table{
		
		protected $tableName = 'rhinfo_service_order_comment';
        protected $primaryKey = 'id';
		protected $uniacid;
		public function __construct($uniacid){
			parent::__construct();
			$this->uniacid = $uniacid;	
		}
		//用户评价列表
		public function getMemberEvaluate($uid){
			$field = ['e.id','s.logo','s.title','e.username','e.avatar','e.goods_quality','e.delivery_service','e.note','e.thumbs','e.addtime','e.data'];
			$comments = $this->query->from($this->tableName,'e')
									->leftjoin('rhinfo_service_store','s')
									->on(['e.sid'=>'s.id'])
									->where(['e.uid'=>$uid,'e.uniacid'=>$this->uniacid])
									->select($field)
									->orderby('e.id','desc')
									->getall();
			$min = 0;

			if (!empty($comments)) {
				foreach ($comments as &$row) {
					$row['data'] = iunserializer($row['data']);
					$row['score'] = (($row['delivery_service'] + $row['goods_quality']) / 10) * 100;
					$row['thumbs'] = iunserializer($row['thumbs']);
					$row['logo'] = tomedia($row['logo']);
					if (!empty($row['thumbs'])) {
						foreach ($row['thumbs'] as &$item) {
							$item = tomedia($item);
						}
					}
					$row['addtime'] = date('Y-m-d H:i',$row['addtime']);
				}

				$min = min(array_keys($comments));
			}
			
			return $comments;
		}
		
		
	}