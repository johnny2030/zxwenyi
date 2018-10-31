<?php

/**
 * 微信支付接口
 */

require_once 'class.today.php';
require_once 'Wechat.php';

class Wxpay {

	private $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
    private $pay_url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';//提现地址
    private $appId = 'wx69217b5af9a538fb';
	private $appSecret = '98e91168e2ac5e907068e62ea340ec23';
	private $mchId = '1489351662';
	private $key = 'blaumdqfginslrv1vqcxh9bfygtzs073';
	private $version = '1.0';

	private $returnUrl = '';
	private $notifyUrl = '';

	/**
	 * 设置URL
	 */
	public function setUrl( $returnUrl, $notifyUrl ) {
		$this->returnUrl = $returnUrl;
		$this->notifyUrl = $notifyUrl;
	}

	/**
	 * 创建支付
	 */
	public function pay( $openId, $ip, $orderNo, $total, $productBody ) {
        $mrand = mt_rand( time(), time() + rand() );
		$params = array(
            'appid' => $this->appId,
            'body' => $productBody,
            'mch_id' => $this->mchId,
            'nonce_str' => $mrand,
            'notify_url' => $this->notifyUrl,
            'openid' => $openId,
            'out_trade_no' => $orderNo,
            'spbill_create_ip' => $ip,
            'total_fee' => $total * 100,
			'trade_type' => 'JSAPI'
		);
		$params['sign'] = $this->sign( $params );
		$xml = \Today\Today::Array2Xml( $params, null, null, 'xml' );
		$xml = $xml->saveXML();
		$result = \Today\Today::httpRequest( $this->url, $xml, false );
		$xml = \Today\Today::Xml2Array( simplexml_load_string( $result ) );

		if ( $xml['result_code'] == 'SUCCESS' && $this->isSign( $xml ) ) {
			if ( $xml['result_code'] == 'SUCCESS' && $xml['return_code'] == 'SUCCESS' ) return array( 'status' => 200, 'prepay_id' => $xml['prepay_id'] );
			else return array( 'status' => 500, 'msg' => $xml['return_msg'] );
		}
		else return array( 'status' => 500, 'msg' => $xml['status'] == 0 ? '验证签名失败' : $xml['return_msg'] );
	}


	public function verify( $data = array() ) {
		if ( $data ) $xml = $data;
		else {
			$result = file_get_contents( 'php://input' );
			$xml = simplexml_load_string( $result );
			$xml = \Today\Today::Xml2Array( $xml );
		}

		if ( $this->isSign( $xml ) ) {
			if ( $xml['result_code'] == 'SUCCESS' && $xml['return_code'] == 'SUCCESS' ) return array( 'status' => true, 'data' => $xml );
		}
		return array( 'status' => false, 'data' => $xml );
	}

	public function getJsApiPayParams( $prepay_id ) {
		$timeStamp = time();
        $mrand = mt_rand( time(), time() + rand() );
		$params = array(
			'appId' => $this->appId,
			'timeStamp' => "$timeStamp",  //必须是字符串形式，所以外面的双引号不能省去
			'nonceStr' => "$mrand", //同样必须是字符串
			'package' => 'prepay_id='.$prepay_id,
			'signType' => 'MD5'
		);
		$params['paySign'] = $this->sign( $params );
		return $params;
	}
	/**
	 * 签名
	 */
	private function sign( $data ) {
		$sign = '';
		ksort( $data );
		foreach ( $data as $key => $val ) {
			if ( $val != '' && $key != 'sign' ) {
				$sign .= "{$key}={$val}&";
			}
		}
		$sign .= 'key='.$this->key;
		$sign = strtoupper( md5( $sign ) );
		return $sign;
	}

	/**
	 * 验证签名是否合法
	 */
	private function isSign( $data ) {
		$sign = $this->sign( $data );
		return $sign == $data['sign'];
	}

	//微信提现
    function transfer($data){
        //支付信息
        $mrand = mt_rand( time(), time() + rand() );
        $webdata = array(
            'mch_appid' => $this->appId,//商户账号appid
            'mchid' => $this->mchId,//商户号
            'nonce_str' => $mrand,//随机字符串
            'partner_trade_no' => $data['orderNo'], //商户订单号，需要唯一
            'openid' => $data['openid'],//转账用户的openid
            'check_name' => 'NO_CHECK', // NO_CHECK：不校验真实姓名 FORCE_CHECK：强校验真实姓名
            'amount' => $data['money'] * 100, //付款金额单位为分,单笔最小金额默认为1元,每个用户每天最多可付款10次,给同一个用户付款时间间隔不得低于15秒
            'desc' => 'transfer',//企业付款描述信息
            'spbill_create_ip' => $data['ip'],//获取IP
        );
        $webdata['sign'] = $this->sign( $webdata );
        $xml = \Today\Today::Array2Xml( $webdata, null, null, 'xml' ); //$this->ArrToXml();数组转XML
        $xml = $xml->saveXML();
        $res = \Today\Today::httpRequest( $this->pay_url, $xml, true );
        if(!$res){
            return array('status'=>1, 'msg'=>"Can't connect the server" );
        }
        $content = \Today\Today::Xml2Array( simplexml_load_string($res, 'SimpleXMLElement', LIBXML_NOCDATA) );
        return $content;
        /*if(strval($content->return_code) == 'FAIL'){
            return array('status'=>1, 'msg'=>strval($content->return_msg));
        }
        if(strval($content->result_code) == 'FAIL'){
            return array('status'=>1, 'msg'=>strval($content->err_code),':'.strval($content->err_code_des));
        }
        else return array( 'status' => 500, 'msg' => $xml['status'] == 0 ? '提现失败' : $xml['return_msg'] );*/
    }

}

?>