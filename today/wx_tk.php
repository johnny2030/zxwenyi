<?php
//define your token
define("TOKEN", "cxCd8c0sh");
$wechatObj = new wx_tk();
if (isset($_GET['echostr'])) {
    $wechatObj->valid();
}else{
    $wechatObj->responseMsg();
}

class wx_tk
{
	public function valid(){
        $echoStr = $_GET["echostr"];

        //valid signature , option
        if($this->checkSignature()){
        	echo $echoStr;
        	exit;
        }
    }

    public function responseMsg(){
        $postArr = file_get_contents("php://input");    //php7.0只能用这种方式获取数据，之前的$GLOBALS['HTTP_RAW_POST_DATA']7.0版本不可用
        $postObj = simplexml_load_string($postArr);
        $create_time = date('Y-m-d H:i:s',time());
        if(strtolower($postObj->MsgType) == 'event'){
            if(strtolower($postObj->Event) == 'subscribe'){
                $toUser		= $postObj->FromUserName;
                $fromUser	= $postObj->ToUserName;
                $scene_id	= $postObj->EventKey;
                $arr = explode('_', $scene_id);
                $id = end($arr);
                $con = mysql_connect("47.96.115.51:3308","root","123admin");
                mysql_select_db("medical", $con);
                mysql_query("set names 'utf8'");
                $checkuser = mysql_query("SELECT * FROM cmf_common_user WHERE open_id = '$toUser'");
                $onUser = mysql_fetch_array($checkuser);
                if($onUser == null){
                    if ($id == null){
                        mysql_query("INSERT INTO cmf_common_user (open_id, create_time) VALUES ('$toUser', '$create_time')");
                        $content = '欢迎来到健康微达人，立即咨询医生？ <a href="http://www.jkwdr.cn/index.php?g=portal&m=patient&a=doctor_user" >'.' 立即咨询'.'</a>';
                    }else{
                        if($id == 0){
                            $user = mysql_query("SELECT * FROM cmf_common_user WHERE open_id = '$toUser'");
                            $doctor = mysql_fetch_array($user);
                            $num = mysql_num_rows($user);
                            if ($num == 0){
                                mysql_query("INSERT INTO cmf_common_user (open_id, status, create_time) VALUES ('$toUser', '1', '$create_time')");
                                $content = '欢迎来到健康微达人，请完善您的信息： <a href="http://www.jkwdr.cn/index.php?g=portal&m=index&a=register_doctor" >'.'个人中心'.'</a>';
                            }elseif ($doctor['name'] == null){
                                $content = '欢迎来到健康微达人，请完善您的信息： <a href="http://www.jkwdr.cn/index.php?g=portal&m=index&a=register_doctor" >'.'个人中心'.'</a>';
                            }else{
                                $content = '欢迎'.$doctor['name'].'来到健康微达人';
                            }
                        }else{
                            $user = mysql_query("SELECT * FROM cmf_common_user WHERE id = '$id'");
                            $doctor = mysql_fetch_array($user);
                            $dp_list = mysql_query("SELECT * FROM cmf_common_dp WHERE doctor_id = '$id' and patient_id = '$toUser'");
                            $num = mysql_num_rows($dp_list);
                            if ($num == 0){
                                mysql_query("INSERT INTO cmf_common_user (open_id, create_time) VALUES ('$toUser', '$create_time')");
                                mysql_query("INSERT INTO cmf_common_dp (doctor_id, patient_id, create_time) VALUES ('$id', '$toUser', '$create_time')");
                                mysql_query("INSERT INTO cmf_common_remind (doctor_id, patient_id, message, create_time) VALUES ('$id', '$toUser', '关注通知', '$create_time')");
                            }
                            $content = '欢迎来到健康微达人，已为您绑定医生： '.$doctor['name'].'  请尽快完善您的<a href="http://www.jkwdr.cn/index.php?g=portal&m=patient&a=patient_user">个人信息</a>  <a href="http://www.jkwdr.cn/index.php?g=portal&m=patient&a=payment&id='.$id.'" >'.'  立即咨询'.'</a>医生';
                        }
                    }
                }else{
                    if ($id == null){
                        if ($onUser['status'] == 0){
                            mysql_query("INSERT INTO cmf_common_user (open_id, create_time) VALUES ('$toUser', '$create_time')");
                            $content = '欢迎来到健康微达人，立即咨询医生？ <a href="http://www.jkwdr.cn/index.php?g=portal&m=patient&a=doctor_user" >'.' 立即咨询'.'</a>';
                        }else{
                            if ($onUser['name'] == null){
                                $content = '欢迎来到健康微达人，请完善您的信息： <a href="http://www.jkwdr.cn/index.php?g=portal&m=index&a=register_doctor" >'.'个人中心'.'</a>';
                            }else{
                                $content = '欢迎'.$onUser['name'].'来到健康微达人';
                            }
                        }
                    }else{
                        if($id == 0){
                            if ($onUser['name'] == null){
                                $content = '欢迎来到健康微达人，请完善您的信息： <a href="http://www.jkwdr.cn/index.php?g=portal&m=index&a=register_doctor" >'.'个人中心'.'</a>';
                            }else{
                                $content = '欢迎'.$onUser['name'].'来到健康微达人';
                            }
                        }else{
                            $user = mysql_query("SELECT * FROM cmf_common_user WHERE id = '$id'");
                            $doctor = mysql_fetch_array($user);
                            $dp_list = mysql_query("SELECT * FROM cmf_common_dp WHERE doctor_id = '$id' and patient_id = '$toUser'");
                            $num = mysql_num_rows($dp_list);
                            if ($num == 0){
                                mysql_query("INSERT INTO cmf_common_user (open_id, create_time) VALUES ('$toUser', '$create_time')");
                                mysql_query("INSERT INTO cmf_common_dp (doctor_id, patient_id, create_time) VALUES ('$id', '$toUser', '$create_time')");
                                mysql_query("INSERT INTO cmf_common_remind (doctor_id, patient_id, message, create_time) VALUES ('$id', '$toUser', '关注通知', '$create_time')");
                            }
                            $content = '欢迎来到健康微达人，已为您绑定医生： '.$doctor['name'].'  请尽快完善您的<a href="http://www.jkwdr.cn/index.php?g=portal&m=patient&a=patient_user">个人信息</a>  <a href="http://www.jkwdr.cn/index.php?g=portal&m=patient&a=payment&id='.$id.'" >'.'  立即咨询'.'</a>医生';
                        }
                    }
                }
                mysql_close($con);
                $time      = time();
                $msgType   = 'text';
                $template  = "<xml>
                              <ToUserName><![CDATA[%s]]></ToUserName>
                              <FromUserName><![CDATA[%s]]></FromUserName>
                              <CreateTime>%s</CreateTime>
                              <MsgType><![CDATA[%s]]></MsgType>
                              <Content><![CDATA[%s]]></Content>
                              </xml>";
                $info= sprintf($template,$toUser,$fromUser,$time,$msgType,$content);
                echo $info;
            }
            if(strtolower($postObj->Event) == 'scan'){
                $toUser		= $postObj->FromUserName;
                $fromUser	= $postObj->ToUserName;
                $scene_id	= $postObj->EventKey;
                $arr = explode('_', $scene_id);
                $id = end($arr);
                $con = mysql_connect("47.96.115.51:3308","root","123admin");
                mysql_select_db("medical", $con);
                mysql_query("set names 'utf8'");
                $checkuser = mysql_query("SELECT * FROM cmf_common_user WHERE open_id = '$toUser'");
                $onUser = mysql_fetch_array($checkuser);
                if ($id == 0){
                    if ($onUser['status'] == 0){
                        mysql_query("UPDATE cmf_common_user SET status = '1' WHERE open_id = '$toUser'");
                        $content = '您已成为医生，请完善您的信息： <a href="http://www.jkwdr.cn/index.php?g=portal&m=index&a=register_doctor" >'.'个人中心'.'</a>';
                    }else{
                        if ($onUser['name'] == null){
                            $content = '欢迎来到健康微达人，请完善您的信息： <a href="http://www.jkwdr.cn/index.php?g=portal&m=index&a=register_doctor" >'.'个人中心'.'</a>';
                        }else{
                            $content = '欢迎'.$onUser['name'].'来到健康微达人';
                        }
                    }
                }else{
                    $user = mysql_query("SELECT * FROM cmf_common_user WHERE id = '$id'");
                    $doctor = mysql_fetch_array($user);
                    $dp_list = mysql_query("SELECT * FROM cmf_common_dp WHERE doctor_id = '$id' and patient_id = '$toUser'");
                    $num = mysql_num_rows($dp_list);
                    if ($num == 0){
                        mysql_query("INSERT INTO cmf_common_dp (doctor_id, patient_id, create_time) VALUES ('$id', '$toUser', '$create_time')");
                        mysql_query("INSERT INTO cmf_common_remind (doctor_id, patient_id, message, create_time) VALUES ('$id', '$toUser', '关注通知', '$create_time')");
                    }
                    $content = '欢迎来到健康微达人，已为您绑定医生： '.$doctor['name'].'  请尽快完善您的<a href="http://www.jkwdr.cn/index.php?g=portal&m=patient&a=patient_user">个人信息</a>  <a href="http://www.jkwdr.cn/index.php?g=portal&m=patient&a=payment&id='.$id.'" >'.'  立即咨询'.'</a>医生';
                }
                $time      = time();
                $msgType   = 'text';
                $template  = "<xml>
                              <ToUserName><![CDATA[%s]]></ToUserName>
                              <FromUserName><![CDATA[%s]]></FromUserName>
                              <CreateTime>%s</CreateTime>
                              <MsgType><![CDATA[%s]]></MsgType>
                              <Content><![CDATA[%s]]></Content>
                              </xml>";
                $info= sprintf($template,$toUser,$fromUser,$time,$msgType,$content);
                echo $info;
            }
        }

        //接收普通文本消息：用户发送“tuwen”后,将收到4条图文消息
        /*if(strtolower($postObj->MsgType)=='text' && trim($postObj->Content)=='tuwen'){
            $toUser   =$postObj->FromUserName;
            $fromUser =$postObj->ToUserName;
            $arr=array
            (
                array(
                    'title'=>'百度',
                    'description'=>"百度很棒!",   //单图文会显示，多图文不显示description
                    'picUrl'=>'http://www.peng.com/baidu.jpg',
                    'url'=>'http://www.baidu.com',    //这里的网页也可以是自己写的html,php等网页
                ),
                array(
                    'title'=>'中国亚马逊',
                    'description'=>"中国亚马逊很棒！",
                    'picUrl'=>'http://www.peng.com/amazon_cn.png',
                    'url'=>'https://www.amazon.cn/',
                ),
                array(
                    'title'=>'Amazon in UK',
                    'description'=>"Amanon is very good!",
                    'picUrl'=>'http://www.peng.com/amazon_co_uk.png',
                    'url'=>'https://www.amazon.co.uk/',
                ),
                array(
                    'title'=>'Amazon en France',
                    'description'=>"Amazon est très bon!",
                    'picUrl'=>'http://www.peng.com/amazon_fr.png',
                    'url'=>'https://www.amazon.fr/',
                )
            );
            $template="<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[%s]]></MsgType>
                        <ArticleCount>".count($arr)."</ArticleCount>
                        <Articles>";
            foreach($arr as $k=>$v)
            {
                $template .="<item>
                            <Title><![CDATA[".$v['title']."]]></Title>
                            <Description><![CDATA[".$v['description']."]]></Description>
                            <PicUrl><![CDATA[".$v['picUrl']."]]></PicUrl>
                            <Url><![CDATA[".$v['url']."]]></Url>
                            </item>";
            }
            $template .="</Articles>
                        </xml> ";
            echo sprintf($template,$toUser,$fromUser,time(),'news');
        }*/
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
}

?>