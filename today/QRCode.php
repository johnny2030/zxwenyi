<?php

/**
* QR Code
*/

require_once 'qrcode/phpqrcode.php';

class MyQRCode {

	public function createQRCode( $text, $outfile = false, $level = 'L', $size = 3, $margin = 4 ) {
		return QRcode::png( $text, $outfile, $level, $size, $margin );
	}

	public function getQRCodeBase64( $text, $outfile = false, $level = 'L', $size = 3, $margin = 4 ) {
		/* 示例
		require_once 'core/v2.1/QRCode.php';
		$qr = new MyQRCode();
		$base64 = $qr->getQRCodeBase64( HTTP_ROOT.'check/'.$order['id'], 'data/'.$this->createRnd().'.png', 'L', 5 );
		$this->setData( $base64, 'base64' );
		*/

		//防止恶意替换文件并最终删除该文件
		if ( file_exists( $outfile ) ) return;

		$this->createQRCode( $text, $outfile, $level, $size, $margin );
		$fp = fopen( $outfile, 'r' );
		$file = fread( $fp, filesize( $outfile ) );
		fclose( $fp );
		unlink( $outfile );
		return 'data:image/png;base64,'.chunk_split( base64_encode( $file ) );
	}

	public function getQRCodeImage( $text, $outfile = false, $level = 'L', $size = 3, $margin = 4 ) {
		/* 示例
		$code = trim( get2( 'code' ) );
		if ( empty( $code ) ) exit;

		ob_end_clean();
		header( 'content-type:image/png' );

		require_once 'core/v2.1/QRCode.php';

		$qr = new MyQRCode;
		$im = $qr->getQRCodeImage( '<'.$code.'>', 'data/'.$this->createRnd().'.png', 'L', 5 );
		imagepng( $im );
		*/

		//防止恶意替换文件并最终删除该文件
		if ( file_exists( $outfile ) ) return;

		$this->createQRCode( $text, $outfile, $level, $size, $margin );
		$im = imagecreatefrompng( $outfile );
		return $im;
	}
	//生成带logo的二维码
    public function createLogoCode($text,$logo){
        //二维码图片保存路径
        $pathname = APP_PATH . '../upload_img/qrcode/';
        if (!is_dir($pathname)) { //若目录不存在则创建之
            mkdir($pathname);
        }
        //二维码图片保存路径(若不生成文件则设置为false)
        $filename = $pathname . "qrcode_".$this->salt('5').$this->msectime().".jpg";
        //二维码容错率，默认L
        $level = "L";
        //二维码图片每个黑点的像素，默认4
        $size = '10';
        //二维码边框的间距，默认2
        $padding = 2;
        //保存二维码图片并显示出来，$filename必须传递文件路径
        $saveandprint = true;
        //生成二维码图片
        QRcode::png($text, $filename, $level, $size, $padding, $saveandprint);
        //二维码logo
        $QR = imagecreatefromstring(file_get_contents($filename));
        $logo = imagecreatefromstring(file_get_contents($logo));
        $QR_width = imagesx($QR);
        $QR_height = imagesy($QR);
        $logo_width = imagesx($logo);
        $logo_height = imagesy($logo);
        $logo_qr_width = $QR_width / 5;
        $scale = $logo_width / $logo_qr_width;
        $logo_qr_height = $logo_height / $scale;
        $from_width = ($QR_width - $logo_qr_width) / 2;
        imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width, $logo_qr_height, $logo_width, $logo_height);
        imagepng($QR, $filename);
        return $filename;
    }
    //合并生成中间带logo的二维码
    public function mergeLogoCode($QR,$logo){
        $createcode='upload_img/qrcode/'.$this->salt('5').$this->msectime().'.jpg';
        $QR = imagecreatefromstring($QR);
        $logo = imagecreatefromstring($logo);
        $QR_width = imagesx($QR);
        $QR_height = imagesy($QR);
        $logo_width = imagesx($logo);
        $logo_height = imagesy($logo);
        $logo_qr_height = $logo_qr_width = $QR_width/5 - 6;
        $from_width = ($QR_width-$logo_qr_width)/2;
        imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width, $logo_qr_height, $logo_width, $logo_height);
        imagepng($QR,$createcode);
        return $createcode;
    }
    /**
     * 生成毫秒级时间戳
     */
    public function msectime(){
        list($msec, $sec) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
    }
    /**
     * 随机取出字符串
     * @param  int $strlen 字符串位数
     * @return string
     */
    public function salt($strlen){
        $str  = "abcdefghkmnprstuvwxyzABCDEFGHKMNPRSTUVWXYZ23456789";
        $salt = '';
        $_len = strlen($str)-1;
        for ($i = 0; $i < $strlen; $i++) {
            $salt .= $str[mt_rand(0,$_len)];
        }
        return $salt;
    }

}

?>