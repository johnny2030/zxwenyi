<?php
/**
 * 咨询问诊
 */
namespace Portal\Controller;
use Common\Controller\HomebaseController;

class MessagesController extends HomebaseController {

    private $common_user_model;
    private $common_tag_model;
    private $common_office_model;
    private $common_hospital_model;
    private $common_health_model;
    private $common_messages_model;

	function _initialize() {
		parent::_initialize();

        $this->common_user_model = D( 'Common_user' );
        $this->common_tag_model = D( 'Common_tag' );
        $this->common_office_model = D( 'Common_office' );
        $this->common_hospital_model = D( 'Common_hospital' );
        $this->common_health_model = D( 'Common_health' );
        $this->common_messages_model = D( 'Common_messages' );
	}
    //群发
    public function index() {
        $where = array();
        $where['m.type'] = array('eq',2);
        $where['m.del_flg'] = array('eq',0);
        $msg_list = $this->common_messages_model->alias('m')->field('m.*,u.name as name,u.photo as photo')->join('__COMMON_USER__ u ON m.user_id=u.id')->where($where)->select();
        $this->assign( 'msg_list', $msg_list );
        $this->display('../Tieqiao/messages');
    }
    //转发
    public function forward() {
        $where = array();
        $where['m.type'] = array('eq',1);
        $where['m.del_flg'] = array('eq',0);
        $msg_list = $this->common_messages_model->alias('m')->field('m.*,u.name as name,u.photo as photo')->join('__COMMON_USER__ u ON m.user_id=u.id')->where($where)->select();
        $this->assign( 'msg_list', $msg_list );
        $this->display('../Tieqiao/forward');
    }
    //详情
    public function detail() {
	    $id = $_GET['id'];
        $where = array();
        $where['m.id'] = array('eq',$id);
        $msg_Info = $this->common_messages_model->alias('m')->field('m.*,u.name as name,u.photo as photo,u.sex as sex,u.age as age')->join('__COMMON_USER__ u ON m.user_id=u.id')->where($where)->find();
        session('send_id',$msg_Info['user_id']);
        $this->assign( 'msg', $msg_Info );
        $this->display('../Tieqiao/customer_detail');
    }
}