<?php

require_once 'today/class.today.php';

$data = file_get_contents( 'php://input' );
$data = simplexml_load_string( $data );
$data = \Today\Today::Xml2Array( $data );

$url = 'http://www.jkwdr.cn/index.php?g=portal&m=patient&a=wx_payment_notify';

\Today\Today::httpRequest( $url, $data );