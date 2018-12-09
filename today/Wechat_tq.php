<?php

/**
* Wechat
*/
define("TOKEN", "VWgZhx7U");
$wechatObj = new Wechat_tq();
if (isset($_GET['echostr'])) {
    $wechatObj->valid();
}else{
    $wechatObj->responseMsg();
}
require_once 'today/class.today.php';
class Wechat_tq {
	/**
	* 系统对象
	*/
	private $systemObj = null;
	/**
	* AppId
	*/
	private $WX_APPID = 'wxdd058ab032b569b1';
	/**
	* AppSecret
	*/
	private $WX_APPSECRET = '5a93f5d3754dcbd2b92c79ad90ee88f2';
	/**
	* Token
	*/
	private $WX_TOKEN = 'VWgZhx7U';
	/**
	* AESKey
	*/
	private $WX_AESKEY = '2eM1eltU8iamF4t3CrkiqiT9w7OcjH1viPVhreNWAlm';
	/**
	* TimeStamp
	*/
	private $WX_TIMESTAMP = '1420774989';
	/**
	* NonceStr
	*/
	private $WX_NONCESTR = '2nDgiWM7gCxhL8v0';
    /**
     * 群发/转发（管理）
     */
    private $WX_FORWARD = 'sPHapieDmhYisvvp3IXazroUpUsD7Gk5d_xXuZjQU0I';
    /**
     * 问题咨询处理通知
     */
    private $WX_CONSULTATION = 'v0lItpu5WaJivHHKnzeZnoC-xTVbusHcQrA9eXzP9dw';
	/**
	* 获取access_token的URL
	*/
	private $WX_ACCESS_TOKEN_URL = 'https://api.weixin.qq.com/cgi-bin/token';
	/**
	* 获取JSApi_ticket的URL
	*/
	private $WX_JSAPI_TICKET_URL = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket';
	/**
	* 获取openID的URL
	*/
	private $WX_OPENID_URL = 'https://api.weixin.qq.com/sns/oauth2/access_token';
	/**
	* 获取用户基本信息的URL
	*/
	private $WX_USER_INFOR_URL = 'https://api.weixin.qq.com/cgi-bin/user/info';
	/**
	* 获取用户列表的URL
	*/
	private $WX_USER_LIST_URL = 'https://api.weixin.qq.com/cgi-bin/user/get';
	/**
	* 获取AUTH2.0的URL
	*/
	private $WX_AUTH20_URL = 'https://open.weixin.qq.com/connect/oauth2/authorize';
	/**
	* 根据二维码扫描登录
	*/
	private $WX_QRCONNECT_URL = 'https://open.weixin.qq.com/connect/qrconnect';
	/**
	* 获取网页授权TOKEN
	*/
	private $WX_ACCESS_TOKEN_URL_FOR_AUTH20 = 'https://api.weixin.qq.com/sns/oauth2/access_token';
	/**
	* 通过网页授权直接获取用户基本信息的URL
	*/
	private $WX_USER_INFOR_URL_FOR_AUTH20 = 'https://api.weixin.qq.com/sns/userinfo';
	/**
	* 获取微信服务器Media的URL
	*/
	private $WX_GET_MEDIA_URL = 'https://api.weixin.qq.com/cgi-bin/media/get';/*http://file.api.weixin.qq.com/cgi-bin/media/get*/
	/**
	* 微信菜单修改
	*/
	private $WX_MENU_CREATE_URL = 'https://api.weixin.qq.com/cgi-bin/menu/create';
	/**
	* 微信菜单删除
	*/
	private $WX_MENU_DELETE_URL = 'https://api.weixin.qq.com/cgi-bin/menu/delete';
	/**
	* 微信菜单获取
	*/
	private $WX_MENU_GET_URL = 'https://api.weixin.qq.com/cgi-bin/menu/get';
    /**
     * 显示二维码
     */
    private $WX_SHOW_QRCODE_URL = 'https://mp.weixin.qq.com/cgi-bin/showqrcode';
    /**
     * 发送客服消息
     */
    private $WX_SEND_CUSTOMMES_URL = 'https://api.weixin.qq.com/cgi-bin/message/custom/send';
    /**
     * 发送模板消息
     */
    private $WX_SEND_TEMPLATE_URL = 'https://api.weixin.qq.com/cgi-bin/message/template/send';
    /**
     * 获取素材总数
     */
    private $WX_GET_MATERIAL_COUNT_URL = 'https://api.weixin.qq.com/cgi-bin/material/get_materialcount';
    /**
     * 获取素材列表
     */
    private $WX_BG_MATERIAL_URL = 'https://api.weixin.qq.com/cgi-bin/material/batchget_material';
    /**
     * 获取永久素材
     */
    private $WX_GET_MATERIAL_URL = 'https://api.weixin.qq.com/cgi-bin/material/get_material';
    /**
     * 根据OpenID列表群发
     */
    private $WX_SEND_MATERIAL_URL = 'https://api.weixin.qq.com/cgi-bin/message/mass/send';


	public function Wechat( $systemObj ) {
		$this->systemObj = $systemObj;
	}
    /**
     * 开发者验证
     */
    public function valid(){
        $echoStr = $_GET["echostr"];

        //valid signature , option
        if($this->checkSignature()){
            echo $echoStr;
            exit;
        }
    }
    private function checkSignature(){
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token = TOKEN;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );

        if( $tmpStr == $signature ){
            return true;
        }else{
            return false;
        }
    }

	/**
	* 从数据库获取access_token，如果没有或者过期，则生成access_token，存入数据库
	*/
	public function getAccessToken() {
		$mdl_wxtoken = D( 'Wxtoken_tq' );

		$token = $mdl_wxtoken->query( "select *, (unix_timestamp(now())-ttime) as seconds from ".C( 'DB_PREFIX' )."wxtoken_tq where tname='token'" );
		$token = $token[0];

		if ( $token['seconds'] < 3600 && !empty( $token['token'] ) ) {
			return $token['token'];
		}

		//重新生成
		$query = array(
			'grant_type' => 'client_credential',
			'appid' => $this->WX_APPID,
			'secret' => $this->WX_APPSECRET
		);
		$query = http_build_query( $query );
		$url = $this->WX_ACCESS_TOKEN_URL.'?'.$query;
		$data = \Today\Today::httpRequest( $url );
		$resultArr = json_decode( $data, true );
		$token = $resultArr["access_token"];

		if ( $mdl_wxtoken->where( array( 'tname' => 'token' ) )->count() > 0 ) {
			$mdl_wxtoken->where( array( 'tname' => 'token' ) )->save( array( 'token' => $token, 'ttime' => time() ) );
		}
		else {
			$mdl_wxtoken->add( array( 'tname' => 'token', 'token' => $token, 'ttime' => time() ) );
		}
		return $token;
	}

	/**
	* 从数据库获取jsapi_ticket，如果没有或者过期，则生成jsapi_ticket，存入数据库
	*/
	public function getJSApiTicket() {
		$mdl_wxtoken = D( 'Wxtoken_tq' );

		$ticket = $mdl_wxtoken->query( "select *, (unix_timestamp(now())-ttime) as seconds from ".C( 'DB_PREFIX' )."wxtoken_tq where tname='JSticket'" );
		$ticket = $ticket[0];

		if ( $ticket['seconds'] < 3600 && !empty( $ticket['token'] ) ) {
			return $ticket['token'];
		}

		//重新生成
		$token = $this->getAccessToken();
		$query = array(
			'type' => 'jsapi',
			'access_token' => $token
		);
		$query = http_build_query( $query );
		$url = $this->WX_JSAPI_TICKET_URL.'?'.$query;
		$data = \Today\Today::httpRequest( $url );
		$resultArr = json_decode( $data, true );
		$ticket = $resultArr["ticket"];

		if ( $mdl_wxtoken->where( array( 'tname' => 'JSticket' ) )->count() > 0 ) {
			$mdl_wxtoken->where( array( 'tname' => 'JSticket' ) )->save( array( 'token' => $ticket, 'ttime' => time() ) );
		}
		else {
			$mdl_wxtoken->add( array( 'tname' => 'JSticket', 'token' => $ticket, 'ttime' => time() ) );
		}

		return $ticket;
	}

	/**
	* 获取JSAPI签名，返回数组
	* @param url 一定要注意：如果带有querystring的url，最后不能以&结尾
	*/
	public function getSignature( $url ) {
		$ticket = $this->getJSApiTicket();
		$query = array(
			'jsapi_ticket' => $ticket,
			'noncestr' => $this->WX_NONCESTR,
			'timestamp' => $this->WX_TIMESTAMP
		);
		$str = http_build_query( $query ).'&url='.$url;
		$signature = sha1( $str );

		return array( 'appId' => $this->WX_APPID, 'timestamp' => $this->WX_TIMESTAMP, 'nonceStr' => $this->WX_NONCESTR, 'signature' => $signature, 'url' => $url );
	}
	
	/**
	* 获取openID
	*/
	public function getOpenID( $code ) {
		$query = array(
			'grant_type' => 'authorization_code',
			'appid' => $this->WX_APPID,
			'secret' => $this->WX_APPSECRET,
			'code' => $code
		);
		$query = http_build_query( $query );
		$url = $this->WX_OPENID_URL.'?'.$query;

		$wxuser = json_decode( \Today\Today::httpRequest( $url ), true );
		return isset( $wxuser['openid'] ) ? $wxuser['openid'] : '';
	}

	/**
	* 获取用户的基本信息
	* @param refresh 是否重新获取最新数据，默认为false，表示直接从数据库获取
	*/
	public function getUserInfor( $openID, $refresh = false ) {
		$mdl_wxinfor = D( 'Wxinfor_tq' );
		$wxuser = $mdl_wxinfor->where( array( 'open_id' => $openID ) )->find();
		if ( !$refresh && $wxuser ) return $wxuser;

		$query = array(
			'access_token' => $this->getAccessToken(),
			'openid' => $openID,
			'lang' => 'zh_CN'
		);
		$query = http_build_query( $query );
		$url = $this->WX_USER_INFOR_URL.'?'.$query;

		$result = json_decode( \Today\Today::httpRequest( $url ), true );

		if( isset( $result['errorcode'] ) ) return $result;  //出错了

		$subscribe = isset( $result['subscribe'] ) ? $result['subscribe'] : 0;

		$data = array(
			'nickname' => addslashes( $result['nickname'] ),
			'sex' => $result['sex'],
			'language' => $result['language'],
			'city' => $result['city'],
			'province' => $result['province'],
			'country' => $result['country'],
			'headimgurl' => $result['headimgurl'],
			'subscribe_time' => $result['subscribe_time'],
			'renew_time' => time(),
			'subscribe' => $subscribe
		);
		if ( $wxuser ) {
			$mdl_wxinfor->where( array( 'open_id' => $wxuser['open_id'] ) )->save( $data );
			$wxuser = array_merge( $wxuser, $data );
		}
		else {
			$data['open_id'] = $openID;
			$mdl_wxinfor->add( $data );
			$wxuser = $data;
		}

		return $wxuser;
	}

	/**
	* 获取用户列表
	*/
	public function getUserList() {
		$query = array(
			'access_token' => $this->getAccessToken()
		);
		$query = http_build_query( $query );
		$url = $this->WX_USER_LIST_URL.'?'.$query;
		return json_decode( \Today\Today::httpRequest( $url ), true );
	}

	/**
	* 获取Auth2.0的URL
	* @param scope 默认为0表示snsapi_base，为1表示snsapi_userinfo
	*/
	public function getAuth20Url( $url, $scope = 1 ) {
		$query = array(
			'appid' => $this->WX_APPID,
			'redirect_uri' => $url,
			'response_type' => 'code',
			'scope' => $scope ? 'snsapi_userinfo' : 'snsapi_base',
			'state' => 1
		);
		$query = http_build_query( $query );
		$url = $this->WX_AUTH20_URL.'?'.$query.'#wechat_redirect';
		return $url;
	}

	/**
	* 获取网页授权的TOKEN
	*/
	public function getAccessTokenForAuth20( $code ) {
		$query = array(
			'appid' => $this->WX_APPID,
			'secret' => $this->WX_APPSECRET,
			'code' => $code,
			'grant_type' => 'authorization_code'
		);
		$query = http_build_query( $query );
		$url = $this->WX_ACCESS_TOKEN_URL_FOR_AUTH20.'?'.$query;
		$data = \Today\Today::httpRequest( $url );
		$resultArr = json_decode( $data, true );

		//返回的结果
		$access_token = $resultArr["access_token"];
		$expires_in = $resultArr["expires_in"];
		$refresh_token = $resultArr["refresh_token"];
		$openid = $resultArr["openid"];
		$scope = $resultArr["scope"];
		$unionid = $resultArr["unionid"];

		return array( 'access_token' => $access_token, 'openID' => $openid );
	}

	/**
	* 通过网页授权直接获取用户信息
	*/
	public function getUserInforForAuth20( $openID = '', $refresh = false, $url = '' ) {
		$mdl_wxinfor = $this->systemObj->loadModel( 'wxinfor_tq' );
		if ( $openID ) {
			$wxuser = $mdl_wxinfor->getByWhere( array( 'OpenID' => $openID ) );
			if ( !$refresh && $wxuser ) return $wxuser;
		}

		$code = $_GET['code'];
		if ( empty( $code ) ) {
			$this->systemObj->sheader( $this->getAuth20Url( $url ? $url : HTTP_ROOT, 1 ) );
		}

		$token = $this->getAccessTokenForAuth20( $code );
		$openID = $token['openID'];

		if ( $openID ) {
			$wxuser = $mdl_wxinfor->getByWhere( array( 'OpenID' => $openID ) );
			if ( !$refresh && $wxuser ) return $wxuser;
		}

		$query = array(
			'access_token' => $token['access_token'],
			'openid' => $token['openID'],
			'lang' => 'zh_CN'
		);
		$query = http_build_query( $query );
		$url = $this->WX_USER_INFOR_URL_FOR_AUTH20.'?'.$query;
		$data = \Today\Today::httpRequest( $url );
		$result = json_decode( $data, true );

		if( isset( $result['errorcode'] ) ) return $result;  //出错了

		$subscribe = isset( $result['subscribe'] ) ? $result['subscribe'] : 0;

		$data = array(
			'nickname' => addslashes( $result['nickname'] ),
			'sex' => $result['sex'],
			'language' => $result['language'],
			'city' => $result['city'],
			'province' => $result['province'],
			'country' => $result['country'],
			'headimgurl' => $result['headimgurl'],
			'subscribe_time' => $result['subscribe_time'],
			'renew_time' => time(),
			'subscribe' => $subscribe
		);
		if ( $wxuser ) {
			$mdl_wxinfor->updateByWhere( $data, array( 'OpenID' => $wxuser['OpenID'] ) );
			$wxuser = array_merge( $wxuser, $data );
		}
		else {
			$data['OpenID'] = $openID;
			$mdl_wxinfor->insert( $data );
			$wxuser = $data;
		}

		return $wxuser;
	}

	/**
	 * 通过qr code完成登录
	 */
	public function getUserInforForQRConnect( $url = '' ) {
		$query = array(
			'appid' => $this->WX_APPID,
			'redirect_uri' => $url ? $url : HTTP_ROOT,
			'response_type' => 'code',
			'scope' => 'snsapi_login',
			'state' => 1
		);
		$query = http_build_query( $query );
		return $this->WX_QRCONNECT_URL.'?'.$query.'#wechat_redirect';
	}

	/**
	* 获取微信服务器上的文件
	*/
	public function downloadWeixinFile( $mediaId, $url ) {
	    if (empty($url)){
            $query = array(
                'access_token' => $this->getAccessToken(),
                'media_id' => $mediaId
            );
            $query = http_build_query( $query );
            $url = $this->WX_GET_MEDIA_URL.'?'.$query;
        }
		$ch = curl_init( $url );
		curl_setopt( $ch, CURLOPT_HEADER, 0 );
		curl_setopt( $ch, CURLOPT_NOBODY, 0 );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, FALSE );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		$body = curl_exec( $ch );
		$header = curl_getinfo( $ch );
		curl_close( $ch );
		$result = array_merge( array( 'header' => $header ), array( 'body' => $body ) );
		return $result;
	}

	/**
	* 修改微信菜单
	*/
	public function menuCreate( $menu ) {
		//$menu = json_encode( $menu );

		$query = array(
			'access_token' => $this->getAccessToken()
		);
		$query = http_build_query( $query );
		$url = $this->WX_MENU_CREATE_URL.'?'.$query;

		return \Today\Today::httpRequest( $url, $menu );
	}

	/**
	* 删除微信菜单
	*/
	public function menuDelete() {
		$query = array(
			'access_token' => $this->getAccessToken()
		);
		$query = http_build_query( $query );
		$url = $this->WX_MENU_DELETE_URL.'?'.$query;

		return \Today\Today::httpRequest( $url );
	}

	/**
	* 获取微信菜单
	*/
	public function menuGet() {
		$query = array(
			'access_token' => $this->getAccessToken()
		);
		$query = http_build_query( $query );
		$url = $this->WX_MENU_GET_URL.'?'.$query;

		return \Today\Today::httpRequest( $url );
	}

    //生成二维码
    public function getUserQRcode($userid, $validity = true){//validity = true 永久  validity = false 临时
        $qrInfo=$this->getTicket($userid,$validity);
        $query = array(
            'ticket' => urldecode($qrInfo['ticket'])
        );
        $query = http_build_query( $query );
        $qrurl = $this->WX_SHOW_QRCODE_URL.'?'.$query;
        return $qrurl;
    }

    //获取ticket
    public function getTicket($userid, $validity){
        $accessToken = $this->getAccessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=".$accessToken;
        if($validity){
            $data='{"action_name": "QR_LIMIT_SCENE", "action_info": {"scene": {"scene_id": "'.$userid.'"}}}';
        }else{
            $data='{"expire_seconds": 604800, "action_name": "QR_SCENE", "action_info": {"scene": {"scene_id": "'.$userid.'"}}}';
        }
        $res = \Today\Today::httpRequest($url,$data);
        $result = json_decode($res, true);
        return $result;
    }
    //发送客服消息（文字）
    public function customSend($open_id,$data){
        $accessToken = $this->getAccessToken();
        $txt = '{
         "touser":"'.$open_id.'",
         "msgtype":"text",
         "text":{"content":"'.$data.'"}
        }';
        $url = $this->WX_SEND_CUSTOMMES_URL.'?access_token='.$accessToken;
        return \Today\Today::httpRequest( $url,$txt );
    }
    //发送客服消息（图文）
    public function customSendImg($open_id,$url,$title,$description){
        $accessToken = $this->getAccessToken();
        $txt = '{
            "touser":"'.$open_id.'",
            "msgtype":"news",
            "news":{
                "articles": [
                {
                    "title":"'.$title.'",
                    "description":"'.$description.'",
                    "url":"'.$url.'",
                    "picurl":"http://tieqiao.zzzpsj.com/themes/dp/Public/images/off-hd.jpg"
                }
                ]
            }
        }';
        $url = $this->WX_SEND_CUSTOMMES_URL.'?access_token='.$accessToken;
        return \Today\Today::httpRequest( $url,$txt );
    }
    //发送客服消息（图文media_id）
    public function customSendMedia($open_id,$media_id){
        $accessToken = $this->getAccessToken();
        $template = '{
            "touser":"'.$open_id.'",
            "msgtype":"mpnews",
            "mpnews":{
                "media_id":"'.$media_id.'"
            }
        }';
        $url = $this->WX_SEND_CUSTOMMES_URL.'?access_token='.$accessToken;
        return \Today\Today::httpRequest( $url, $template );
    }
    //发送模板消息
    public function templateSend($open_id,$url,$data,$topcolor='#7B68EE'){
        $accessToken = $this->getAccessToken();
        $template = array(
            'touser' => $open_id,
            'template_id' => $this->WX_CONSULTATION,
            'url' => $url,
            'topcolor' => $topcolor,
            'data' => $data
        );
        $json_template = json_encode($template);
        $urls = $this->WX_SEND_TEMPLATE_URL.'?access_token='.$accessToken;
        return \Today\Today::httpRequest( $urls, urldecode($json_template) );
    }
    //获取素材总数
    public function get_materialcount(){
        $accessToken = $this->getAccessToken();
        $url = $this->WX_GET_MATERIAL_COUNT_URL.'?access_token='.$accessToken;
        return \Today\Today::httpRequest( $url );
    }
    //获取素材列表
    public function bg_material(){
        $accessToken = $this->getAccessToken();
        $txt = '{
            "type":"news",
            "offset":0,
            "count":20
        }';
        $url = $this->WX_BG_MATERIAL_URL.'?access_token='.$accessToken;
        return \Today\Today::httpRequest( $url,$txt );
    }
    //获取素材列表
    public function get_material($media_id){
        $accessToken = $this->getAccessToken();
        $txt = '{
            "media_id":"'.$media_id.'"
        }';
        $url = $this->WX_GET_MATERIAL_URL.'?access_token='.$accessToken;
        return \Today\Today::httpRequest( $url,$txt );
    }
    //群发
    public function send_material($open_id,$media_id){
        $accessToken = $this->getAccessToken();
        $txt = '{
            "touser":["'.$open_id.'"],
            "mpnews":{
                "media_id":"'.$media_id.'"
            },
            "msgtype":"mpnews",
            "send_ignore_reprint":0
        }';
        $url = $this->WX_SEND_MATERIAL_URL.'?access_token='.$accessToken;
        return \Today\Today::httpRequest( $url,$txt );
    }
    //转发/群发模板消息推送
    public function templateForward($open_id,$url,$data,$topcolor='#7B68EE'){
        $accessToken = $this->getAccessToken();
        $template = array(
            'touser' => $open_id,
            'template_id' => $this->WX_FORWARD,
            'url' => $url,
            'topcolor' => $topcolor,
            'data' => $data
        );
        $json_template = json_encode($template);
        $urls = $this->WX_SEND_TEMPLATE_URL.'?access_token='.$accessToken;
        return \Today\Today::httpRequest( $urls, urldecode($json_template) );
    }
    //关注事件推送
    public function responseMsg(){
        $postArr = file_get_contents("php://input");    //php7.0只能用这种方式获取数据，之前的$GLOBALS['HTTP_RAW_POST_DATA']7.0版本不可用
        $postObj = simplexml_load_string($postArr);
        if(strtolower($postObj->MsgType) == 'event'){
            $toUser		= $postObj->FromUserName;
            $fromUser	= $postObj->ToUserName;
            //用户未关注时
            if(strtolower($postObj->Event) == 'subscribe'){
                $title = '欢迎关注铁樵健康';
                $decription = '用我们贴心的服务，换您满意的微笑！';
                $picurl = 'http://tieqiao.zzzpsj.com/themes/dp/Public/images/welcome.jpg';
                $url = 'http://tieqiao.zzzpsj.com/index.php?g=portal&m=user&a=user_info';
                $time      = time();
                $template  = "<xml>
                              <ToUserName><![CDATA[%s]]></ToUserName>
                              <FromUserName><![CDATA[%s]]></FromUserName>
                              <CreateTime>%s</CreateTime>
                              <MsgType><![CDATA[news]]></MsgType>
                              <ArticleCount>1</ArticleCount>
                              <Articles>
                              <item>
                              <Title><![CDATA[%s]]></Title>
                              <Description><![CDATA[%s]]></Description>
                              <PicUrl><![CDATA[%s]]></PicUrl>
                              <Url><![CDATA[%s]]></Url>
                              </item>
                              </Articles>
                              </xml>";
                $info = sprintf($template, $toUser, $fromUser, $time, $title, $decription, $picurl, $url);
                echo $info;
            }
            //用户已关注时
            if(strtolower($postObj->Event) == 'scan'){
                $title = '您已关注铁樵健康';
                $decription = '用我们贴心的服务，换您满意的微笑！';
                $picurl = 'http://tieqiao.zzzpsj.com/themes/dp/Public/images/welcome.jpg';
                $url = 'http://tieqiao.zzzpsj.com/index.php?g=portal&m=user&a=user_info';
                $time      = time();
                $template  = "<xml>
                              <ToUserName><![CDATA[%s]]></ToUserName>
                              <FromUserName><![CDATA[%s]]></FromUserName>
                              <CreateTime>%s</CreateTime>
                              <MsgType><![CDATA[news]]></MsgType>
                              <ArticleCount>1</ArticleCount>
                              <Articles>
                              <item>
                              <Title><![CDATA[%s]]></Title>
                              <Description><![CDATA[%s]]></Description>
                              <PicUrl><![CDATA[%s]]></PicUrl>
                              <Url><![CDATA[%s]]></Url>
                              </item>
                              </Articles>
                              </xml>";
                $info = sprintf($template, $toUser, $fromUser, $time, $title, $decription, $picurl, $url);
                echo $info;
            }
            //用户已关注时
            if(strtolower($postObj->Event) == 'click'){
                $EventKey = $postObj->EventKey;//菜单的自定义的key值，可以根据此值判断用户点击了什么内容，从而推送不同信息
                switch($EventKey){
                    case "newOne":
                        $news_arr = array(
                            'news1' => array('title' => '为什么医生让我做了CT，又要做核磁共振？', 'decription' => '为什么医生让我做了CT，又要做核磁共振？', 'PicUrl' => 'http://tieqiao.zzzpsj.com/upload_img/push/1.jpg', 'Url' => 'https://mp.weixin.qq.com/s?__biz=MzU1MjcxNzk2Mg==&mid=2247483726&idx=1&sn=b90ea22b52187a9b7ee351505b682f6e&chksm=fbfc9aabcc8b13bd451a27767269e81575e6ad75154acb26bd0b9776f6e7835bf0f81700f983&token=355504380&lang=zh_CN#rd'),
                            'news2' => array('title' => '[传承创新一体 协作融合共赢］第十届江浙沪中西医结合高峰论坛2018年江浙沪中西医结合肿瘤学术年会圆满胜利召开', 'decription' => '[传承创新一体 协作融合共赢］第十届江浙沪中西医结合高峰论坛2018年江浙沪中西医结合肿瘤学术年会圆满胜利召开', 'PicUrl' => 'http://tieqiao.zzzpsj.com/upload_img/push/2.jpg', 'Url' => 'https://mp.weixin.qq.com/s/Ya2Yc-bXiCHJwEZE-WVBxQ'),
                            'news3' => array('title' => '史上最全体检报告漫画解读！速速收藏！', 'decription' => '史上最全体检报告漫画解读！速速收藏！', 'PicUrl' => 'http://tieqiao.zzzpsj.com/upload_img/push/3.jpg', 'Url' => 'https://mp.weixin.qq.com/s?__biz=MzU1MjcxNzk2Mg==&mid=2247483726&idx=3&sn=ddb2c7614c6e17a6b3de381dd59d51b8&chksm=fbfc9aabcc8b13bd730c96cd0e3fc1458f9d8fcf48993461ccabeb9230b1a41694edd090111a&token=355504380&lang=zh_CN#rd'),
                            'news4' => array('title' => 'CSPE 2018｜心律失常诊治讲座荟萃（一）', 'decription' => 'CSPE 2018｜心律失常诊治讲座荟萃（一）', 'PicUrl' => 'http://tieqiao.zzzpsj.com/upload_img/push/4.jpg', 'Url' => 'https://mp.weixin.qq.com/s/AyVSP0QabkINsPaGm0kL4A'),
                            'news5' => array('title' => '尿酸偏高但没有痛风表现，要紧吗？除了痛风，高尿酸血症还有更多危害！', 'decription' => '尿酸偏高但没有痛风表现，要紧吗？除了痛风，高尿酸血症还有更多危害！', 'PicUrl' => 'http://tieqiao.zzzpsj.com/upload_img/push/5.jpg', 'Url' => 'https://mp.weixin.qq.com/s/EWHMSNZdtVU4wk6OsIznlg'),
                            'news6' => array('title' => '珍藏版: 大肠癌的筛查及预防', 'decription' => '珍藏版: 大肠癌的筛查及预防', 'PicUrl' => 'http://tieqiao.zzzpsj.com/upload_img/push/6.jpg', 'Url' => 'https://mp.weixin.qq.com/s?__biz=MzA4NDI5MzYzNA==&mid=2650621113&idx=5&sn=cb38e8ae7c86e0154ada99fe461dcd5a&chksm=87e085d4b0970cc222a80404c897c9f2a3a5b473bf80b11a30da768fff2451748df9812634a5&mpshare=1&scene=1&srcid=0822r375couehojXg0CGgpZ4&rd2werd=1#wechat_redirect'),
                            'news7' => array('title' => '养生有道', 'decription' => '养生有道', 'PicUrl' => 'http://tieqiao.zzzpsj.com/upload_img/push/7.png', 'Url' => 'https://mp.weixin.qq.com/s?__biz=MzU1MjcxNzk2Mg==&mid=2247483726&idx=7&sn=455743c742b1b44cdec990dd0ed31533&chksm=fbfc9aabcc8b13bd27eb91f333785b5da1cae246c931d33e45e2ba73cb781021b5f2c4372a58&token=355504380&lang=zh_CN#rd'),
                            'news8' => array('title' => '亚健康状态和中医“治未病”', 'decription' => '亚健康状态和中医“治未病”', 'PicUrl' => 'http://tieqiao.zzzpsj.com/upload_img/push/8.jpg', 'Url' => 'https://mp.weixin.qq.com/s?__biz=MzU1MjcxNzk2Mg==&mid=2247483726&idx=8&sn=de2f8573f93158ce8cc4889337174701&chksm=fbfc9aabcc8b13bde15dec7234385f3bd0768a7a9878be52ebb0fbcc336e48dd81c7abfd208b&token=355504380&lang=zh_CN#rd')
                        );
                        $time = time();
                        $count = 8;
                        break;
                    case "lock_acount":
                        #返回的消息
                        break;
                    case "other":
                        #返回的消息
                        break;
                }
                $template  = "<xml>
                              <ToUserName><![CDATA[%s]]></ToUserName>
                              <FromUserName><![CDATA[%s]]></FromUserName>
                              <CreateTime>%s</CreateTime>
                              <MsgType><![CDATA[news]]></MsgType>
                              <ArticleCount>".$count."</ArticleCount>
                              <Articles>";
                for ($i=1;$i<=$count;$i++){
                    $template  .= "<item>
                              <Title><![CDATA[%s]]></Title>
                              <Description><![CDATA[%s]]></Description>
                              <PicUrl><![CDATA[%s]]></PicUrl>
                              <Url><![CDATA[%s]]></Url>
                              </item>";
                }
                $template  .= "</Articles>
                              </xml>";
                $info = sprintf($template, $toUser, $fromUser, $time, $news_arr['news1']['title'], $news_arr['news1']['decription'], $news_arr['news1']['PicUrl'], $news_arr['news1']['Url'], $news_arr['news2']['title'], $news_arr['news2']['decription'], $news_arr['news2']['PicUrl'], $news_arr['news2']['Url'], $news_arr['news3']['title'], $news_arr['news3']['decription'], $news_arr['news3']['PicUrl'], $news_arr['news3']['Url'], $news_arr['news4']['title'], $news_arr['news4']['decription'], $news_arr['news4']['PicUrl'], $news_arr['news4']['Url'], $news_arr['news5']['title'], $news_arr['news5']['decription'], $news_arr['news5']['PicUrl'], $news_arr['news5']['Url'], $news_arr['news6']['title'], $news_arr['news6']['decription'], $news_arr['news6']['PicUrl'], $news_arr['news6']['Url'], $news_arr['news7']['title'], $news_arr['news7']['decription'], $news_arr['news7']['PicUrl'], $news_arr['news7']['Url'], $news_arr['news8']['title'], $news_arr['news8']['decription'], $news_arr['news8']['PicUrl'], $news_arr['news8']['Url']);
                echo $info;
            }
        }
    }
}
?>