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
    }
    public function user_info() {
        $id = (int)session('login_id');
        $user = $this->common_user_model->find($id);
        if ($user['type'] == 0){
            R('User/info_patient');
        }else{
            R('User/info_doctor');
        }
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
        $open_id=session('open_id');
        $wechat->customSendMedia($open_id,'mNdVxM-2RSlGmkcMe0p9v25prBwgiaAlX4I8jyQ25_A');
    }
}


