<?php
namespace Portal\Controller;
use Common\Controller\HomebaseController;

class CommonMethodController extends HomebaseController {

    private $common_user_model;
    private $common_tag_model;
    private $common_card_model;
    private $common_health_model;

    public function _initialize() {
        parent::_initialize();

        $this->common_user_model = D( 'Common_user' );
        $this->common_tag_model = D( 'Common_tag' );
        $this->common_card_model = D( 'Common_card' );
        $this->common_health_model = D( 'Common_health' );
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
    //判断手机号是否已注册
    function check_phone(){
        $phone = $_GET['phone'];
        $where = array();
        $where['del_flg'] = array('eq',0);
        $where['status'] = array('eq',0);
        $where['phone'] = array('eq',$phone);
        $list = $this->common_user_model->where($where)->select();
        if (empty($list)){
            $this->ajaxReturn('0');
        }else{
            $this->ajaxReturn('1');
        }
    }
    //获取具体科室信息
    function get_tag(){
        $office = $_GET['office'];
        $where = array();
        $where['del_flg'] = array('eq',0);
        $where['office_id'] = array('eq',$office);
        $list = $this->common_tag_model->where($where)->order('create_time desc')->select();
        $data['list'] = $list;
        $this->ajaxReturn($data);
    }
    //获取会员卡信息
    function get_cardInfo(){
        $m_card = $_GET['m_card'];
        $p_card = $_GET['p_card'];
        $where = array();
        $where['del_flg'] = array('eq',0);
        $where['status'] = array('eq',0);
        $where['card_number'] = array('eq',$m_card);
        $where['card_pwd'] = array('eq',$p_card);
        $card = $this->common_card_model->where($where)->find();
        if (empty($card) || empty($card['card_time'])){
            $this->ajaxReturn('0');
        }else{
            $this->ajaxReturn($card['card_time']);
        }
    }
    //获取健康状况（首菜单）
    function get_healthy_f_info(){
        $where = array();
        $where['up_id'] = array('eq',0);
        $where['del_flg'] = array('eq',0);
        $list = $this->common_health_model->where($where)->select();
        $data['list'] = $list;
        $this->ajaxReturn($data);
    }
    //获取健康状况（次级菜单）
    function get_healthy_info(){
        $up_id = $_GET['up_id'];
        $list = array();
        if ($up_id>0){
            $where = array();
            $where['up_id'] = array('eq',$up_id);
            $where['del_flg'] = array('eq',0);
            $list = $this->common_health_model->where($where)->select();
        }
        $data['list'] = $list;
        $this->ajaxReturn($data);
    }
    //上传图片
    public function uploadImg(){
        $id = (int)session('login_id');
        $fg = $_POST['fg'];
        if ($fg == 1){
            require_once 'today/Wechat_tq.php';
            $wechat = new \Wechat_tq( $this );
            $media_id = $_POST['media_id'];//前端返回的上传后的媒体id
            $img = $wechat->downloadWeixinFile($media_id,'');
            $filename='upload_img/head_tq/'.$this->salt('5').$this->msectime().'.jpg';
            file_put_contents($filename, $img['body']);
            $data['photo'] = '../'.$filename;
            $data['update_time'] = date('Y-m-d H:i:s',time());
            $result = $this->common_user_model->where(array('id' => $id))->save($data);
        }else{
            require_once 'today/Wechat_zj.php';
            $wechat = new \Wechat_zj( $this );
            $media_id = $_POST['media_id'];//前端返回的上传后的媒体id
            $img = $wechat->downloadWeixinFile($media_id,'');
            $filename='upload_img/head_zj/'.$this->salt('5').$this->msectime().'.jpg';
            file_put_contents($filename, $img['body']);
            $data['photo'] = '../'.$filename;
            $data['update_time'] = date('Y-m-d H:i:s',time());
            $result = $this->common_user_model->where(array('id' => $id))->save($data);
        }
        if($result){
            $this->ajaxReturn(1);
        }else{
            $this->ajaxReturn(0);
        }
    }
}