<?php

class wxjssdk {

	/**
	* AppId
	*/
	private $WX_APPID = 'wxdd058ab032b569b1';

    /**
     * 开发者验证
     */
    public function getSignPackage() {
        require_once 'Wechat_tq.php';
        $wechat = new \Wechat_tq( $this );
        $jsapiTicket = $wechat->getJSApiTicket();
        $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $timestamp = time();
        $nonceStr = mt_rand( time(), time() + rand() );

        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

        $signature = sha1($string);

        $signPackage = array(
            "appId"     => $this->WX_APPID,
            "nonceStr"  => $nonceStr,
            "timestamp" => $timestamp,
            "url"       => $url,
            "signature" => $signature,
            "rawString" => $string
        );
        return $signPackage;
    }

}
?>