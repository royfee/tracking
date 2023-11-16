<?php
/**
 * 马来西亚渠道的采集轨迹
 */
namespace royfee\tracking\collection;

use royfee\tracking\interfaces\TrackInterface;
use royfee\tracking\common\BaseGateway;

class BCollection extends BaseGateway {
	var $requestUrl = 'http://iscgate.wtdex.com:6060/wtdex-service/ws/openapi/rest/route';
	var $appkey = 'dd0adf9c-468e-408b-814f-325bf89a74cc';
	var $token 	= '98d4838c-b87e-4d34-9d84-80356fd94710';
	var $secret = '5ed2f64f-91ca-4f57-a04c-abbfad3b2bbe';

	public function track(String $number,String $no){
		//必须要用订单号查询
		$result = $this->fetch($no);

		if($result === false){
			return false;
		}
		
		//排序
		$list = $this->sortNode($result);

		return [
			'code'	=>	0,
			'number'=>	$number,
			'list'	=>	$list,
			'latest'=>  $this->latest($list[0])
		];
	}

	private function latest($curNode){
		$status = $this->nodeStatus($curNode['desc']);
		$return = [
			'desc'	=>	$curNode['desc'],
			'time'	=>	$curNode['time'],
			'status'=>	$status,
			'sub_status'	=>	0,
			'status_desc'	=>	\royfee\tracking\support\Status::getDesc($status,0)
		];

		return $return;
	}

	private function request($method,$data){
		$orderJson = json_encode($data);
		$baseParam = [
			'appkey'	=>	$this->appkey,
			'format'	=>	'json',
			'method'	=>	$method,
			'params'	=>	$orderJson,
			'timestamp'	=>	date('Y-m-d H:i:s'),
			'token'		=>	$this->token,
			'v'			=>	'1.0'
		];
		$baseParam['sign'] = $this->sign($baseParam);
		return $this->_post('',$baseParam);
	}

	private function sign($params){
		$string = $this->secret;
		foreach($params as $k => $v){
			$string.= $v;
		}
		$string.= $this->secret;
		$md5 = strtolower(md5($string));
		return base64_encode($md5);
	}

	private function _post($action,$postArray){
        $curl = curl_init($this->requestUrl);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postArray);
        $response = curl_exec($curl);
		$err = '';
		if(curl_errno($curl) !== 0){
			$err = curl_error($curl);
		}
        curl_close($curl);
        return $err?$err:json_decode($response,true);
    }

	private function fetch($number){
        $res = $this->request('wtdex.trade.express.get',[
			'orderId'	=>	$number
		]);

		if(empty($res) || empty($res['response']['expressRoute'])){
			return false;
		}

		$trackList = [];
		foreach($res['response']['expressRoute']['expressRouteItemList']['expressRouteItems'] as $row){
			$trackList[] = array(
				'time'	=>	$row['optime'],
				'loca'	=>	$row['state'],
				'desc'	=>	$row['notes'],
			);
		}
		return $trackList;
	}
}