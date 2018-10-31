<?php
namespace Portal\Controller;
use Common\Controller\HomebaseController;

class CommonMethodController extends HomebaseController {

    private $china_model;

    public function _initialize() {
        parent::_initialize();

        $this->china_model = D( 'China' );
    }
    /**
     * 微信jssdk上传音频
     * @param string $media_id 前端返回的上传后的媒体id
     * @param boolean $flg 是否获取base64码 默认不获取
     * @return object
     */
    public function uploadVoice($media_id,$flg = false) {
        /*$qiniuStorage = new \Think\Upload\Driver\Qiniu\QiniuStorage();
          $qiniu = new \Think\Upload\Driver\Qiniu();
          \Think\Log::write('语音文件名:'.$filename,'WARN');
          file_put_contents($filename, $result['body']);
          $str = file_get_contents('./test.png');
          echo base64_encode($str);
        */
        require_once 'today/Wechat.php';
        $wechat = new \Wechat( $this );
        $result = $wechat->downloadWeixinFile($media_id,'');
        $prefix=explode("/", $result['header']['content_type']);
        $filename=$this->salt('5').$this->msectime().'.'.$prefix[1];
        $data['vic'] = base64_encode($result['body']);
        $this->ajaxReturn($data);
    }
    /**
     * 微信jssdk上传图片
     * @param string $media_id 前端返回的上传后的媒体id
     * @param boolean $flg 是否获取base64码 默认不获取
     * @return object
     */
    public function uploadImg($media_id,$flg = false) {
        require_once 'today/Wechat.php';
        $wechat = new \Wechat( $this );
        $result = $wechat->downloadWeixinFile($media_id,'');
        $filename=$this->salt('5').$this->msectime().'.jpg';
        file_put_contents('upload_img/'.$filename, $result['body']);
        if ($flg){
            $data['img'] = base64_encode($result['body']);
        }

        $data['img_name'] = $filename;
        $data['img_url'] = 'http://www.jkwdr.cn/upload_img/'.$filename;
        $this->ajaxReturn($data);
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
    //省市县联动
    function get_data(){
        $where = array();
        $pid = $_GET['pid'];
        $county = $_GET['county'];
        if (empty($county)){
            $where['pid'] = array('eq',$pid);
            $list = $this->china_model->where($where)->select();
        }else{
            /*$where['pid'] = array(array('eq',$pid+$county),array('eq',$pid), 'or') ;*/
            $where['pid'] = array('eq',$pid+$county);
            $list1 = $this->china_model->where($where)->select();
            $where['pid'] = array('eq',$pid);
            $list2 = $this->china_model->where($where)->select();
            unset($list2[0]);
            $list = array_merge_recursive($list1,$list2);
        }
        $data['list'] = $list;
        $this->ajaxReturn($data);
    }
}