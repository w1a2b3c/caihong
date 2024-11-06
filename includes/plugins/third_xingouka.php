<?php
namespace plugins;

class third_xingouka{

	private $config = [];

	static public $info = [
		'name'        => 'third_xingouka',
		'type'        => 'third',
		'title'       => '新购卡',
		'author'      => '彩虹',
		'version'     => '1.0',
		'link'        => '',
		'sort'        => 25,
		'showedit'    => false,
		'showip'      => false,
		'pricejk'     => 0,
		'input' => [
			'url' => '网站域名',
			'username' => '商家编号',
			'password' => '接口密钥',
			'paypwd' => '支付密码',
			'paytype' => false,
		],
	];

	public function __construct($config)
	{
		$this->config = $config;
	}

	public function do_goods($goods_id, $goods_type, $goods_param, $num = 1, $input = array(), $money, $tradeno, $inputsname)
	{
		$result['code'] = -1;

		$param = array('customerid'=>$this->config['username'], 'goodsid'=>$goods_id, 'quantity'=>$num , 'tradepassword' => $this->config["paypwd"]);
		
		if($goods_type==1){
			$url = '/api.php/buyer/buyCardGoodOrder';
		}else{
			$url = '/api.php/buyer/buyGoodOrder';
			$i=0;
			foreach ($input as $val){
				$param['lblName'.$i] = $input[$i];
				$i++;
			}
		}
		$sign = md5($this->config['username'] . $goods_id . $this->config['password']);
		$param['sign'] = $sign;
		$post = http_build_query($param);
		$data = $this->get_curl($url, $post);
		$json = json_decode($data,true);
		if(isset($json['code']) && $json['code']==1000){
			$result = array(
				'code' => 0,
				'id' => $json['data']['orderno']
			);
		}elseif(isset($json['info'])){
			$result['message'] = $json['info'];
		}else{
			$result['message'] = $data;
		}
		return $result;
	}

	public function goods_list(){
		$url = '/api.php/buyer/getGoods';
		$param = array('customerid'=>$this->config['username']);
		$sign = md5($this->config['username'] . $this->config['password']);
		$param['sign'] = $sign;
		$post = http_build_query($param);
		$ret = $this->get_curl($url, $post);
		if (!$ret = json_decode($ret, true)) {
			return '打开对接网站失败';
		} else if ($ret['code'] != 1000) {
			return $ret['info'];
		} else {
			$list = array();
			foreach ($ret['data'] as $v) {
				$list[] = array(
					'id' => $v['id'],
					'name' => $v['name'],
					'type' => $v['type'],
					'price' => $v['money']
				);
			}
			return $list;
		}
	}

	public function goods_info($goods_id){
		$url = '/api.php/buyer/getGood';
		$param = array('customerid'=>$this->config['username'], 'goodsid'=>$goods_id);
		$sign = md5($this->config['username'] . $goods_id . $this->config['password']);
		$param['sign'] = $sign;
		$post = http_build_query($param);
		$data = $this->get_curl($url, $post);
		if (!$ret = json_decode($data, true)) {
			return '打开对接网站失败';
		} elseif ($ret['code'] == 1000) {
			if($ret['data']['id']==null){
				return '商品不存在';
			}
			$return = $ret['data'];
			$return['input'] = $ret['data']['tpl']['name'];
			$inputs = '';
			foreach($ret['data']['tpl'] as $row){
				if($row['name']==$return['input'])continue;
				if($row['type'] == 'select' || $row['type'] == 'radio'){
					$inputs .= $row['name'].'{'.$row['value'].'}|';
				}else{
					$inputs .= $row['name'].'|';
				}
			}
			$return['inputs'] = trim($inputs,'|');
			return $return;
		} else {
			return $ret['info'];
		}
	}
	
	public function query_order($orderid, $goodsid, $value = []){
		$order_status = ['待处理','待处理','正在处理','已完成','异常','已退单'];
		$url = '/api.php/buyer/orderInfo';
		$param = array('customerid'=>$this->config['username'], 'orderno'=>$orderid);
		$sign = md5($this->config['username'] . $orderid . $this->config['password']);
		$param['sign'] = $sign;
		$post = http_build_query($param);
		$data = $this->get_curl($url, $post);
		if (!$ret = json_decode($data, true)) {
			return false;
		} elseif ($ret['code'] == 1000) {
			$result = $ret['data'];
			$return = ['订单状态'=>$order_status[$result['ostatus']]];
			if(!empty($result['retinfo']))$return['返回信息'] = $result['retinfo'];
			if($result['gtype']==1 && isset($result['cards']) && count($result['cards'])>0){
				$kmdata = '';
				foreach($result['cards'] as $kmrow){
					if(!empty($kmrow['card_password']) && !empty($kmrow['card_no'])){
						$kmdata.='卡号：'.$kmrow['card_no'].' 密码：'.$kmrow['card_password'].'<br/>';
					}elseif(empty($kmrow['card_no'])){
						$kmdata.=$kmrow['card_password'].'<br/>';
					}else{
						$kmdata.=$kmrow['card_no'].'<br/>';
					}
				}
				$return['卡密信息'] = $kmdata;
			}
			return $return;
		} else{
			return $ret['info'];
		}
	}

	private function get_curl($path,$post=0,$referer=0,$cookie=0,$header=0,$addheader=0){
		$url = ($this->config['protocol']==1?'https://':'http://') . $this->config['url'] . $path;
		return shequ_get_curl($url,$post,$referer,$cookie,$header,$addheader);
	}
}