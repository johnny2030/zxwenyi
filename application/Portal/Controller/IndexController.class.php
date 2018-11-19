<?php
namespace Portal\Controller;
use Common\Controller\HomebaseController;

class IndexController extends HomebaseController {

    private $common_user_model;

    public function _initialize() {
        parent::_initialize();

        $this->common_user_model = D( 'Common_user' );
    }

    //首页
	public function index() {
        $this->display('../Tieqiao/index');
        /*$open_id = session('open_id');
        $where = array();
        $where['open_id'] = array('eq',$open_id);
        $user = $this->common_user_model->where($where)->find();
        if (session('login_id') == ""){
            session('login_id',$user['id']);
        }
        $check = A('Check');
        if (!$check->check_info($user)){
            if ($user['status'] == 1){
                $this->register_doctor();
            }else{
                $this->register_patient();
            }
        }else{
            if ($user['status'] == 1){
                R('Doctor/index');
            }else{
                R('Patient/index');
            }
        }*/
    }
    //获取素材总数
    function get_materialcount() {
        require_once 'today/Wechat_tq.php';
        $wechat = new \Wechat_tq( $this );
        $result = $wechat->get_materialcount();
        $this->assign( 'patient', $result );
        $this->display();
    }
    //获取素材列表
    function bg_material() {
        require_once 'today/Wechat_tq.php';
        $wechat = new \Wechat_tq( $this );
        $result = $wechat->bg_material();
        $this->assign( 'patient', $result );
        $this->display();
    }
    //获取永久素材
    function get_material() {
        require_once 'today/Wechat_tq.php';
        $wechat = new \Wechat_tq( $this );
        $result = $wechat->get_material('mNdVxM-2RSlGmkcMe0p9v8vARtULkCj1_6Tl8AhEk8o');
        $this->assign( 'patient', $result );
        $this->display();
    }
    //发送素材
    function send_material() {
        require_once 'today/Wechat_tq.php';
        $wechat = new \Wechat_tq( $this );
        $result = $wechat->customSendMedia('oiizC04BxX-ieip24KiYScDKpWA8','mNdVxM-2RSlGmkcMe0p9v25prBwgiaAlX4I8jyQ25_A');
        $this->assign( 'patient', $result );
        $this->display();
    }
}


