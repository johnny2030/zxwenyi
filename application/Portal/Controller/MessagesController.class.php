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
    private $common_record_model;

	function _initialize() {
		parent::_initialize();

        $this->common_user_model = D( 'Common_user' );
        $this->common_tag_model = D( 'Common_tag' );
        $this->common_office_model = D( 'Common_office' );
        $this->common_hospital_model = D( 'Common_hospital' );
        $this->common_health_model = D( 'Common_health' );
        $this->common_messages_model = D( 'Common_messages' );
        $this->common_record_model = D( 'Common_record' );
	}
    //群发
    public function index() {
        $where = array();
        $where['m.type'] = array('eq',2);
        $where['m.del_flg'] = array('eq',0);
        $msg_list = $this->common_messages_model->alias('m')->field('m.*,u.name as name,u.photo as photo')->join('__COMMON_USER__ u ON m.user_id=u.id')->where($where)->order("m.status asc,m.create_time desc")->select();
        $this->assign( 'msg_list', $msg_list );
        $this->display('../Tieqiao/messages');
    }
    //转发
    public function forward() {
        $where = array();
        $where['m.type'] = array('eq',1);
        $where['m.del_flg'] = array('eq',0);
        $msg_list = $this->common_messages_model->alias('m')->field('m.*,u.name as name,u.photo as photo')->join('__COMMON_USER__ u ON m.user_id=u.id')->where($where)->order("m.status asc,m.create_time desc")->select();
        $this->assign( 'msg_list', $msg_list );
        $this->display('../Tieqiao/forward');
    }
    //用户咨询
    public function advice() {
        $where = array();
        $where['m.type'] = array('eq',0);
        $where['m.del_flg'] = array('eq',0);
        $msg_list = $this->common_messages_model->alias('m')->field('m.*,u.name as name,u.photo as photo')->join('__COMMON_USER__ u ON m.user_id=u.id')->where($where)->order("m.status asc,m.create_time desc")->select();
        $this->assign( 'msg_list', $msg_list );
        $this->display('../Tieqiao/advice');
    }
    //详情（医生）
    public function detail() {
	    $id = $_GET['id'];
        $where = array();
        $where['m.id'] = array('eq',$id);
        $msg_Info = $this->common_messages_model->alias('m')->field('m.*,u.name as name,u.photo as photo,u.sex as sex,u.age as age')->join('__COMMON_USER__ u ON m.user_id=u.id')->where($where)->find();

        $where_r = array();
        $where_r['message_id'] = array('eq',$msg_Info['id']);
        $where_r['user_id'] = array('eq',$msg_Info['user_id']);
        $list = $this->common_record_model->where($where_r)->select();
        session('send_id',$msg_Info['user_id']);
        $this->assign( 'list', $list );
        $this->assign( 'msg', $msg_Info );
        $this->display('../Tieqiao/customer_detail');
    }
    //详情（管理员）
    public function detail_mg() {
        $id = $_GET['id'];
        $where = array();
        $where['m.id'] = array('eq',$id);
        $msg_Info = $this->common_messages_model->alias('m')->field('m.*,u.name as name,u.photo as photo,u.sex as sex,u.age as age')->join('__COMMON_USER__ u ON m.user_id=u.id')->where($where)->find();
        $list = $this->common_record_model->where(array('user_id' => $msg_Info['user_id']))->select();
        session('send_id',$msg_Info['user_id']);
        $this->assign( 'list', $list );
        $this->assign( 'msg', $msg_Info );
        $this->display('../Tieqiao/customer_detail_mg');
    }
    //转发医生
    function forward_handle(){
        $id = intval( I( 'get.id' ) );
        $data = array();
        $data['type'] = 1;
        $data['status'] = 0;
        $result = $this->common_messages_model->where(array('id' => $id))->save($data);
        if ($result) {
            $where = array();
            $where['type'] = array('eq',1);
            $where['del_flg'] = array('eq',0);
            $list = $this->common_user_model->where($where)->select();
            $msg_info = $this->common_messages_model->field('title')->find($id);
            $user = $this->common_user_model->field('name')->find($msg_info['user_id']);
            foreach ($list as $sendUser) {
                if ($sendUser['status'] == 0){
                    $url = 'http://tieqiao.zzzpsj.com/index.php?g=portal&m=messages&a=forward';
                    $this->template_send_tq($msg_info['title'],$user['name'],$sendUser['open_id'],$url);
                }else{
                    $url = 'http://tieqiao.zzzpsj.com/index.php?g=portal&m=messages&a=forward';
                    $this->template_send_zj($msg_info['title'],$sendUser['open_id'],$url);
                }
            }
            $this->ajaxReturn('0');
        } else {
            $this->ajaxReturn('1');
        }
    }
    //群发医生显示
    function send_show(){
        $id = intval( I( 'get.id' ) );
        $this->assign( 'msg_id', $id );
        $where = array();
        $where['u.type'] = array('eq',1);
        $where['u.del_flg'] = array('eq',0);
        $list = $this->common_user_model->alias('u')->field('u.*,o.name as office_n,t.name as tag_n')->join('__COMMON_OFFICE__ o ON u.office=o.id','left')->join('__COMMON_TAG__ t ON u.tag=t.id','left')->where($where)->order('u.create_time desc')->select();
        $this->assign( 'list', $list );
        $this->display('../Tieqiao/send_show');
    }
    //群发医生
    function send_handle(){
        $ids = $_POST['ids'];
        $msg_id = $_POST['msg_id'];
        $data = array();
        $data['type'] = 2;
        $data['status'] = 0;
        $result = $this->common_messages_model->where(array('id' => $msg_id))->save($data);
        if ($result) {
            $msg_info = $this->common_messages_model->field('title')->find($msg_id);
            $user = $this->common_user_model->field('name')->find($msg_info['user_id']);
            foreach ($ids as $id) {
                $sendUser = $this->common_user_model->find($id);
                if ($sendUser['status'] == 0){
                    $url = 'http://tieqiao.zzzpsj.com/index.php?g=portal&m=messages&a=index';
                    $this->template_send_tq($msg_info['title'],$user['name'],$sendUser['open_id'],$url);
                }else{
                    $url = 'http://tieqiao.zzzpsj.com/index.php?g=portal&m=messages&a=index';
                    $this->template_send_zj($msg_info['title'],$sendUser['open_id'],$url);
                }
            }
            $this->redirect('Messages/send_show', array('id' => $msg_id), 3, '群发成功');
        }else{
            $this->redirect('Messages/send_show', array('id' => $msg_id), 3, '群发失败');
        }
    }
    public function template_send_tq($title,$name,$open_id,$url) {
        require_once 'today/Wechat_tq.php';
        $wechat = new \Wechat_tq( $this );
        $data=array(
            'first'=>array('value'=>urlencode("有新的咨询问题了。"),'color'=>"#36648B"),
            'keyword1'=>array('value'=>urlencode($name),'color'=>'#36648B'),
            'keyword2'=>array('value'=>urlencode($title),'color'=>'#36648B'),
            'remark'=>array('value'=>urlencode('点击进入咨询页面'),'color'=>'#36648B'),
        );
        $wechat->templateForward($open_id,$url,$data);
    }
    public function template_send_zj($title,$open_id,$url) {
        require_once 'today/Wechat_zj.php';
        $wechat = new \Wechat_zj( $this );
        $time = date('Y-m-d H:i:s',time());
        $data=array(
            'first'=>array('value'=>urlencode("有新的咨询问题了。"),'color'=>"#36648B"),
            'keyword1'=>array('value'=>urlencode($title),'color'=>'#36648B'),
            'keyword2'=>array('value'=>urlencode($time),'color'=>'#36648B'),
            'remark'=>array('value'=>urlencode('点击进入咨询页面'),'color'=>'#36648B'),
        );
        $wechat->templateForward($open_id,$url,$data);
    }
}