<?php

/**
 *
 */

$PAYMENTS = array(
	'alipay' => '支付宝',
	'wxpay' => '微信',
	'depay' => '到货付款'
);

$SMS_ENABLE = true;  //是否启用发送短信接口
$TIME_LIMIT = 120;  //发送短信时间间隔，单位：秒
//开发环境
define('RY_KEY','m7ua80gbmjmjm');
define('RY_SECRET','06UxW29eWPO2');
//生产环境
/*define('RY_KEY','x18ywvqfxbt2c');
define('RY_SECRET','WQUVUwLuZS6k');
define('DB_CHARSET','utf8');*/
define('ACCESSKEY','CXwpMSKtGfk7Z6AqOUbLwno5VYboJe6bSWc-uA4I');
define('SECRETKEY','snsnFHZpyqdxZql8Qi4cSDPgdKeLSWVcDWZJzLaX');
define('UPHOST','http://up.qiniu.com');
define('DOMAIN','qiniu.jkwdr.cn');
define('BUCKET','jkwdr-storage');

?>