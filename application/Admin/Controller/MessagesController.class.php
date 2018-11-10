<?php
/**
 * 消息管理
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
	//消息信息列表
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
        $list = $this->common_messages_model->alias('m')->field('m.*,u.name as name_u,d.name as name_d')->join('__COMMON_USER__ u ON m.user_id=u.id')->join('__COMMON_USER__ d ON m.doctor_id=d.id','left')->where($where)->order('m.create_time desc')->select();
        $this->assign("page", $page->show('Admin'));
        $this->assign( 'list', $list );
		$this->display();
	}
	//群发
	function sends_many(){
        $this->display();
    }
    //转发
    function forward(){
        $this->display();
    }
    //处理
    function handle(){
        $this->display();
    }
    //删除消息
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

}