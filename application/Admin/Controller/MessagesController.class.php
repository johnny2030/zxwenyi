<?php
/**
 * 咨询管理
 */
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class MessagesController extends AdminbaseController {

    private $common_user_model;
    private $common_messages_model;

	function _initialize() {
		parent::_initialize();

        $this->common_user_model = D( 'Common_user' );
        $this->common_messages_model = D( 'Common_messages' );
	}
	//咨询信息列表
	function index() {
        $where = array();
        //申请人姓名
        $name=I('name');
        $this->assign( 'name', $name );
        if ( $name ) $where['u.name'] = array('like',"%$name%");
        //处理人姓名
        $doctor_name=I('doctor_name');
        $this->assign( 'doctor_name', $doctor_name );
        if ( $doctor_name ) $where['d.name'] = array('like',"%$doctor_name%");
        //咨询状态
        $status=I('status');
        $this->assign( 'status', $status );
        if ( $status ) $where['m.status'] = array('eq',$status);
        $where['m.del_flg'] = array('eq',0);
        $count = $this->common_messages_model->alias('m')->where($where)->count();
        $page = $this->page($count, 20);
        $list = $this->common_messages_model->alias('m')->field('m.*,u.name as name_u,d.name as name_d')->join('__COMMON_USER__ u ON m.user_id=u.id')->join('__COMMON_USER__ d ON m.doctor_id=d.id','left')->where($where)->order('m.status,m.create_time desc')->select();
        $this->assign("page", $page->show('Admin'));
        $this->assign( 'list', $list );
		$this->display();
	}
    //添加咨询信息
    function add() {
        if ( IS_POST ) {
            $_POST['create_time'] = date('Y-m-d H:i:s',time());
            $result = $this->common_messages_model->add($_POST);
            if ($result) {
                //记录日志
                LogController::log_record($result,1);
                $this->success('添加咨询信息成功！');
            } else {
                $this->error('添加咨询信息失败！');
            }
        } else {
            $where = array();
            $where['type'] = array('eq',0);
            $where['del_flg'] = array('eq',0);
            $user_list = $this->common_user_model->where($where)->select();
            $this->assign( 'user_list', $user_list );
            $this->display();
        }
    }
    //编辑咨询信息
    function edit() {
        if ( IS_POST ) {
            $id = (int)$_POST['id'];
            $result = $this->common_messages_model->where(array('id' => $id))->save($_POST);
            if ($result) {
                //记录日志
                LogController::log_record($id,2);
                $this->success('编辑咨询信息成功！');
            } else {
                $this->error('编辑咨询信息失败！');
            }
        } else {
            $id = intval( I( 'get.id' ) );
            $messages = $this->common_messages_model->find($id);

            $where = array();
            $where['type'] = array('eq',0);
            $where['del_flg'] = array('eq',0);
            $user_list = $this->common_user_model->where($where)->select();

            $this->assign( 'user_list', $user_list );
            $this->assign($messages);
            $this->display();
        }
    }
    //删除咨询信息
    function delete() {
        if ( isset( $_POST['ids'] ) ) {//批量逻辑删除
            $ids = implode( ',', $_POST['ids'] );
            $data['del_flg'] = 1;
            if ( $this->common_messages_model->where( "id in ($ids)" )->save( $data ) !== false ) {
                //记录日志
                LogController::log_record($ids,3);
                $this->success('删除成功');
            } else {
                $this->error('删除失败');
            }
        } else if ( isset( $_GET['object'] ) && $_GET['restore'] ) {//恢复数据
            $object = $_GET['object'];
            $log_id = $_GET['id'];
            $data['del_flg'] = 0;
            if ( $this->common_messages_model->where( "id in ($object)" )->save( $data ) !== false ) {
                //记录日志
                LogController::modify_log_type($log_id, 4);
                $this->success('恢复成功');
            } else {
                $this->error('恢复失败');
            }
        } else if ( isset( $_GET['id'] ) && $_GET['complete_delete'] ) {//彻底物理删除
            $object = $_GET['object'];
            $log_id = $_GET['id'];
            if ( $this->common_messages_model->where( "id in ($object)" )->delete() !== false ) {
                //记录日志
                LogController::modify_log_type($log_id, 5);
                $this->success('彻底删除成功');
            } else {
                $this->error('彻底删除失败');
            }
        } else {//单个逻辑删除
            $id = intval( I( 'get.id' ) );
            $data['del_flg'] = 1;
            if ( $this->common_messages_model->where(array('id' => $id))->save($data) !== false ) {
                //记录日志
                LogController::log_record($id,3);
                $this->success('删除成功');
            } else {
                $this->error('删除失败');
            }
        }
    }
	//群发
	function sends_many(){
        $id = intval( I( 'get.id' ) );
        $this->assign( 'msg_id', $id );
        $where = array();
        $where['u.type'] = array('eq',1);
        $where['u.del_flg'] = array('eq',0);
        $count = $this->common_user_model->alias('u')->where($where)->count();
        $page = $this->page($count, 20);
        $list = $this->common_user_model->alias('u')->field('u.*,h.name as hospital_n,o.name as office_n,t.name as tag_n')->join('__COMMON_HOSPITAL__ h ON u.hospital=h.id','left')->join('__COMMON_OFFICE__ o ON u.office=o.id','left')->join('__COMMON_TAG__ t ON u.tag=t.id','left')->where($where)->order('u.create_time desc')->select();
        $this->assign("page", $page->show('Admin'));
        $this->assign( 'list', $list );
        $this->display();
    }
    //转发
    function forward(){
        $id = intval( I( 'get.id' ) );
        $data = array();
        $data['type'] = 1;
        $result = $this->common_messages_model->where(array('id' => $id))->save($data);
        if ($result) {
            $where = array();
            $where['type'] = array('eq',1);
            $where['del_flg'] = array('eq',0);
            $list = $this->common_user_model->where($where)->select();
            $msg_info = $this->common_messages_model->find($id);
            $user = $this->common_user_model->find($msg_info['user_id']);
            foreach ($list as $sendUser) {
                if ($sendUser['status'] == 0){
                    $url = 'http://tieqiao.zzzpsj.com/index.php?g=portal&m=messages&a=forward';
                    $this->template_send_tq($msg_info,$user,$sendUser,$url);
                }else{
                    $url = 'http://tieqiao.zzzpsj.com/index.php?g=portal&m=messages&a=forward';
                    $this->template_send_zj($msg_info,$sendUser,$url);
                }
            }
            $this->success('转发成功！');
        } else {
            $this->error('转发失败！');
        }
        $this->display();
    }
    //群发处理
    function handle(){
        if ( IS_POST ) {
            $ids = $_POST['ids'];
            $msg_id = $_POST['msg_id'];
            $data = array();
            $data['type'] = 2;
            $this->common_messages_model->where(array('id' => $msg_id))->save($data);
            $msg_info = $this->common_messages_model->find($msg_id);
            $user = $this->common_user_model->find($msg_info['user_id']);
            foreach ($ids as $id) {
                $sendUser = $this->common_user_model->find($id);
                if ($sendUser['status'] == 0){
                    $url = 'http://tieqiao.zzzpsj.com/index.php?g=portal&m=messages&a=index';
                    $this->template_send_tq($msg_info,$user,$sendUser,$url);
                }else{
                    $url = 'http://tieqiao.zzzpsj.com/index.php?g=portal&m=messages&a=index';
                    $this->template_send_zj($msg_info,$sendUser,$url);
                }
            }
        }else{
            $id = $_GET['id'];
            $msg_id = $_GET['msg_id'];
            $data = array();
            $data['type'] = 2;
            $this->common_messages_model->where(array('id' => $msg_id))->save($data);
            $msg_info = $this->common_messages_model->find($msg_id);
            $sendUser = $this->common_user_model->find($id);
            if ($sendUser['status'] == 0){
                $user = $this->common_user_model->find($msg_info['user_id']);
                $url = 'http://tieqiao.zzzpsj.com/index.php?g=portal&m=messages&a=index';
                $this->template_send_tq($msg_info,$user,$sendUser,$url);
            }else{
                $url = 'http://tieqiao.zzzpsj.com/index.php?g=portal&m=messages&a=index';
                $this->template_send_zj($msg_info,$sendUser,$url);
            }
        }
        $this->success('发送成功！');
        $this->display();
    }
    public function template_send_tq($msg_info,$user,$sendUser,$url) {
        require_once 'today/Wechat_tq.php';
        $wechat = new \Wechat_tq( $this );
        $data=array(
            'first'=>array('value'=>urlencode("有新的咨询问题了。"),'color'=>"#36648B"),
            'keyword1'=>array('value'=>urlencode($user['name']),'color'=>'#36648B'),
            'keyword2'=>array('value'=>urlencode($msg_info['title']),'color'=>'#36648B'),
            'remark'=>array('value'=>urlencode('点击进入咨询页面'),'color'=>'#36648B'),
        );
        $wechat->templateForward($sendUser['open_id'],$url,$data);
    }
    public function template_send_zj($msg_info,$sendUser,$url) {
        require_once 'today/Wechat_zj.php';
        $wechat = new \Wechat_zj( $this );
        $time = date('Y-m-d H:i:s',time());
        $data=array(
            'first'=>array('value'=>urlencode("有新的咨询问题了。"),'color'=>"#36648B"),
            'keyword1'=>array('value'=>urlencode($msg_info['title']),'color'=>'#36648B'),
            'keyword2'=>array('value'=>urlencode($time),'color'=>'#36648B'),
            'remark'=>array('value'=>urlencode('点击进入咨询页面'),'color'=>'#36648B'),
        );
        $wechat->templateForward($sendUser['open_id'],$url,$data);
    }
}