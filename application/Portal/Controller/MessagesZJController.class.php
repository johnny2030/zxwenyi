<?php
/**
 * 咨询问诊
 */
namespace Portal\Controller;
use Common\Controller\HomebaseZJController;

require_once 'today/Wechat_zj.php';
class MessagesZJController extends HomebaseZJController {

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
        $this->display('../Expert/messages');
    }
    //转发
    public function forward() {
        $where = array();
        $where['m.type'] = array('eq',1);
        $where['m.del_flg'] = array('eq',0);
        $msg_list = $this->common_messages_model->alias('m')->field('m.*,u.name as name,u.photo as photo')->join('__COMMON_USER__ u ON m.user_id=u.id')->where($where)->select();
        $this->assign( 'msg_list', $msg_list );
        $this->display('../Expert/forward');
    }
    //详情
    public function detail() {
	    $id = $_GET['id'];
        $where = array();
        $where['m.id'] = array('eq',$id);
        $msg_Info = $this->common_messages_model->alias('m')->field('m.*,u.name as name,u.photo as photo,u.sex as sex,u.age as age')->join('__COMMON_USER__ u ON m.user_id=u.id')->where($where)->find();
        session('send_id',$msg_Info['user_id']);
        $this->assign( 'msg', $msg_Info );
        $this->display('../Expert/customer_detail');
    }
    //获取素材总数
    function get_materialcount() {
        $wechat = new \Wechat_zj( $this );
        $result = $wechat->get_materialcount();
        $this->assign( 'patient', $result );
        $this->display();
    }
    //获取素材列表
    function bg_material() {
        $wechat = new \Wechat_zj( $this );
        $result = $wechat->bg_material();
        $this->assign( 'patient', $result );
        $this->display();
    }
    //获取永久素材
    function get_material() {
        $wechat = new \Wechat_zj( $this );
        $result = $wechat->get_material('HpdJXzi7lVSt1al6zyjDEE29rGvEws0TaArkUeckyLQ');
        $this->assign( 'patient', $result );
        $this->display();
    }
    //发送素材信息
    function send_material() {
        $wechat = new \Wechat_zj( $this );
        $open_id=session('open_id');
        $result = $wechat->customSendMedia($open_id,'');
        $this->assign( 'patient', $result );
    }
    //学会动态
    function send_dynamic() {
        $wechat = new \Wechat_zj( $this );
        $open_id=session('open_id');
        $wechat->customSendMedia($open_id,'HpdJXzi7lVSt1al6zyjDEE29rGvEws0TaArkUeckyLQ');
    }
    //科技信息
    function send_technology() {
        $wechat = new \Wechat_zj( $this );
        $open_id=session('open_id');
        $wechat->customSendMedia($open_id,'HpdJXzi7lVSt1al6zyjDEBEUeimmmSB54T2PPhNVBog');
    }
    //指南解读
    function send_guide() {
        $wechat = new \Wechat_zj( $this );
        $open_id=session('open_id');
        $wechat->customSendMedia($open_id,'HpdJXzi7lVSt1al6zyjDEEdPj5icFPy5Mmtx0klE04k');
    }
    //学分查询
    function send_credit() {
        $wechat = new \Wechat_zj( $this );
        $open_id=session('open_id');
        $wechat->customSend($open_id,'欢迎关注专家联盟！');
    }
}