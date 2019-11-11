<?php
/**
 */
defined('IN_IA') or exit('Access Denied');
include('version.php');
include('defines.php');
include('model.php');

class Rhinfo_serviceModuleSite extends WeModuleSite {
	private $cache = array();
	public function __construct() {}

	public function doWebWeb() {
		$this->router();
	}

	public function doMobileMobile() {
		$this->router();
	}
	/*Desc:(二开)接口路由重定向
	**Author:sz
	**Date:2019/10/18
	**Time:14:14
	*/
	public function doMobileApi(){
		//error_reporting(E_ERROR | E_WARNING | E_PARSE);
		global $_W,$_GPC;
		error_reporting(-1);
        ini_set('display_errors', 1);
		$op = empty($_GPC['op'])?'':$_GPC['op'];
		switch($op){
			case 'business':
				$bootstrap = RHINFO_SERVICE_PATH . 'inc/business/__init.php';
				break;
			case 'tech':
				$bootstrap = RHINFO_SERVICE_PATH . 'inc/technician/__init.php';
				break;
			default:
				$bootstrap = RHINFO_SERVICE_PATH . 'inc/inter/__init.php';
		}
		
		if(file_exists($bootstrap)){
			
			include_once $bootstrap;
			
		}else{
			header("status: 404 Not Found");	
		}
		exit();
	}
	/**/
	public function doWebCheck(){
		global $_W;	
		if (empty($_W['uid'])) {
			echo "1";
		}
		else {
			echo "2";
		}
		exit;
	}
		
	public function router() {
		$bootstrap = RHINFO_SERVICE_PATH . 'inc/__init.php';
		require $bootstrap;
		exit();
	}

	public function __call($name, $arguments) {
		global $_W;
		
		$isWeb = stripos($name, 'doWeb') === 0;
		$isMobile = stripos($name, 'doMobile') === 0;
		$isApi = stripos($name, 'doApi') === 0;
		
		if($isWeb || $isMobile) {
			$dir = IA_ROOT . '/addons/' . $this->modulename . '/inc/';
			if($isWeb) {
				require $dir . 'web/__init.php';
				$do = strtolower(substr($name, 5));
				$sys = substr($do, 0, 3);
				if($sys == 'ptf') {
					$do = substr($do, 3);
					$dir .= 'web/plateform/';
				} elseif($sys == 'cmn') {
					$do = substr($do, 3);
					$dir .= 'web/common/';
				} else {
					$dir .= 'web/store/';
				}
				$fun = $do;
			} 
			else {
				require $dir . 'mobile/__init.php';
				$do = strtolower(substr($name, 8));
				$sys = substr($do, 0, 3);
				if($sys == 'cmn') {
					$do = substr($do, 3);
					$dir .= 'mobile/common/';
				} else {
					$sys = substr($do, 0, 2);
					if($sys == 'mg') {
						$do = substr($do, 2);
						$dir .= 'mobile/manage/';
						require $dir . 'bootstrap.inc.php';
					} elseif($sys == 'dy') {
						$do = substr($do, 2);
						$dir .= 'mobile/delivery/';
						require $dir . 'bootstrap.inc.php';
					} else {
						$dir .= 'mobile/store/';
						$routers = array(
							'goods' => imurl('microe/store/goods', array('sid' => $_GET['sid'])),
							'store' => imurl('microe/store/index', array('sid' => $_GET['sid'])),
						);
						if(in_array($do, array_keys($routers))) {
							header("location: {$routers[$do]}");
							die;
						}
					}
				}
				$fun = $do;
			}

			$file = $dir . $fun . '.inc.php';
			if(file_exists($file)) {
				require $file;
				exit;
			}
		} 
		else {
			$dir = IA_ROOT . '/addons/' . $this->modulename . '/inc/';
			require $dir . 'api/__init.php';
			$do = strtolower(substr($name, 5));
			$sys = substr($do, 0, 2);
			if($sys == 'mg') {
				$do = substr($do, 2);
				$dir .= 'api/manage/';
				require $dir . 'bootstrap.inc.php';
			} elseif($sys == 'dy') {
				$do = substr($do, 2);
				$dir .= 'api/delivery/';
				require $dir . 'bootstrap.inc.php';
			} elseif($sys == 'cm') {
				$do = substr($do, 3);
				$dir .= 'api/common/';
				require $dir . 'bootstrap.inc.php';
			} else {
				$dir .= 'api/store/';
				require $dir . 'bootstrap.inc.php';
			}
			$fun = $do;
			$file = $dir . $fun . '.inc.php';
			var_dump($file);exit();
			if(file_exists($file)) {
				require $file;
				exit;
			}
		}
		trigger_error("访问的方法 {$name} 不存在.", E_USER_WARNING);
		return null;
	}

	public function payResult($params) {
		global $_W;
		$_W['siteroot'] = str_replace(array('/addons/rhinfo_service', '/payment/qianfan', '/payment/majia'), array('', '', ''), $_W['siteroot']);
		$_W['uniacid'] = $params['uniacid'];
		
		$record = pdo_get('rhinfo_service_paylog', array('uniacid' => $_W['uniacid'], 'order_sn' => $params['tid']));
		
		$_W['agentid'] = $record['agentid'];
		$config = get_system_config();
		$_W['rhinfo_service']['config'] = $config;
		if(($params['result'] == 'success' && $params['from'] == 'notify') || ($params['from'] == 'return' && in_array($params['type'], array('delivery')))) {			
			mload()->model('order');
			if(!empty($record)) {
				pdo_update('rhinfo_service_paylog', array('status' => 1, 'paytime' => TIMESTAMP), array('id' => $record['id']));
			}
			//找人代付功能
			if($record['order_type'] == 'peerpay'){
				$order = pdo_get('rhinfo_service_order_peerpay_payinfo', array('id' => $record['order_id'], 'uniacid' => $_W['uniacid']));
				if(!empty($order)) {
					if(!$order['is_pay']) {
						pdo_update('rhinfo_service_order_peerpay_payinfo', array('is_pay' => 1, 'paytime' => TIMESTAMP), array('id' => $record['order_id'], 'uniacid' => $_W['uniacid']));
						$peerpay = pdo_get('rhinfo_service_order_peerpay', array('id' => $order['pid']));
						if(!empty($peerpay)) {
							$update = array(
								'peerpay_realprice' => round($peerpay['peerpay_realprice'] - $order['final_fee'], 2),
							);
							if($update['peerpay_realprice'] <= 0) {
								$update['status'] = 1;
							}
							pdo_update('rhinfo_service_order_peerpay', $update, array('id' => $peerpay['id']));
							if($update['status'] == 1) {
								$record = pdo_get('rhinfo_service_paylog', array('uniacid' => $_W['uniacid'], 'id' => $peerpay['plid']));
								$params = array(
									'channel' => 'wap',
									'type' => 'peerpay',
									'card_fee' => $record['fee'],
									'is_pay' => 1,
									'paytime' => TIMESTAMP,
									'out_trade_no' => '',
									'transaction_id' => '',
								);
							}
						}
					}
				}
			}
			if($record['order_type'] == 'takeout') {
				order_system_status_update($record['order_id'], 'pay', $params);
				$this->my_send_sound('rhinfo_service_'.$_W['setting']['site']['key'].$_W['uniacid']);
			} elseif($record['order_type'] == 'deliveryCard') {
				include RHINFO_SERVICE_PLUGIN_PATH . 'deliveryCard/model.php';
				card_setmeal_buy($record['order_id']);
			} elseif($record['order_type'] == 'paybill') {
				mload()->model('paybill');
				paybill_order_status_update($record['order_id'], 'pay', $params);
			} elseif($record['order_type'] == 'errander') {
				$_W['_plugin']['config'] = get_plugin_config('errander');
				include RHINFO_SERVICE_PLUGIN_PATH . 'errander/model.php';
				$order = pdo_get('rhinfo_service_errander_order', array('id' => $record['order_id'], 'uniacid' => $_W['uniacid']));
				if(!empty($order) && !$order['is_pay']) {
					$data = array(
						'order_channel' => $params['channel'],
						'pay_type' => $params['type'],
						'final_fee' => $params['card_fee'],
						'is_pay' => 1,
						'paytime' => TIMESTAMP,
						'out_trade_no' => $params['uniontid'],
						'transaction_id' => $params['transaction_id'],
					);
					pdo_update('rhinfo_service_errander_order', $data, array('id' => $order['id'], 'uniacid' => $_W['uniacid']));
					errander_order_status_update($order['id'], 'pay');
					errander_order_status_update($order['id'], 'dispatch');
				}
			} elseif($record['order_type'] == 'recharge') {
				mload()->model('member');
				member_recharge_status_update($record['order_id'], 'pay', $params);				
			} elseif($record['order_type'] == 'storeenter') {
				mload()->model('member');
				store_recharge_status_update($record['order_id'], 'pay', $params);
				
			} elseif($record['order_type'] == 'freelunch'){
				include RHINFO_SERVICE_PLUGIN_PATH . 'freeLunch/model.php';
				freelunch_partaker_status_update($record['order_id'], 'pay');
			}
		}

		if($params['from'] == 'return') {
			if($record['order_type'] == 'takeout') {
				$url = imurl('microe/order/index/detail', array('id' => $record['order_id']), true);
			} elseif($record['order_type'] == 'deliveryCard') {
				$url = imurl('deliveryCard/index', array(), true);
			} elseif($record['order_type'] == 'recharge') {
				$url = imurl('microe/member/mine', array(), true);
			} elseif($record['order_type'] == 'storeenter') { //--rhao--
				$url = imurl('manage/shop/index', array('sid'=>$record['sid']), true);
			}elseif($record['order_type'] == 'freelunch'){
				$url = imurl('freeLunch/freeLunch/partake_success', array(), true);
			} elseif($record['order_type'] == 'errander'){
				$url = imurl('errander/order/detail', array('id' => $record['sid']), true);
			} elseif($record['order_type'] == 'peerpay'){
				$url = imurl('system/paycenter/peerpay/paylist', array('payinfo_id' => $record['order_id']), true);
			} elseif($record['order_type'] == 'paybill'){
				$url = imurl('microe/member/mine', array(), true);
			}
			if (in_array($params['type'], array('credit', 'delivery'))) {
				imessage('下单成功', $url, 'success');
				return NULL;				
			}

			header('location:' . $url);
			exit();
		}
	}
	public function successResult($data){
		$errno = 0;
		$message = 'sucess';
		exit(json_encode(array(
			'errno' => $errno,
			'message' => $message,
			'result' => $data,
		)));
	}
	public function errorResult($message){
		$errno = 1;
		$data = '';
		exit(json_encode(array(
			'errno' => $errno,
			'message' => $message,
			'result' => $data,
		)));
	}
   public function my_send_sound($uid,$msg='您有新的订单，请注意查收'){//消息提醒
		$push_api_url = base64_decode("aHR0cDovL3dlLnp5MTc4LmNu").":2121";
		$post_data = array(
		   "type" => "publish",
		   "content" => $msg,
		   "to" => $uid, 
		);
		$ch = curl_init ();
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt ( $ch, CURLOPT_URL,$push_api_url);
		curl_setopt ( $ch, CURLOPT_POST, 1 );
		curl_setopt ( $ch, CURLOPT_HEADER, 0 );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt ( $ch, CURLOPT_POSTFIELDS, $post_data );
		curl_setopt ( $ch, CURLOPT_HTTPHEADER, array("Expect:"));
		$res = curl_exec($ch);
		curl_close ( $ch );
		return $res;				
	}
}