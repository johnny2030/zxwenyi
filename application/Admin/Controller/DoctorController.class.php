<?php
/**
 * 医生管理
 */
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class DoctorController extends AdminbaseController {

    private $common_user_model;
	
	function _initialize() {
		parent::_initialize();

        $this->common_user_model = D( 'Common_user' );
	}
	//医生信息列表
	function index() {
		$where = array();
		//姓名
		$name=I('name');
		$this->assign( 'name', $name );
		if ( $name ) $where['u.name'] = array('like',"%$name%");
        $where['u.type'] = array('eq',1);
        $where['u.del_flg'] = array('eq',0);
		$count = $this->common_user_model->alias('u')->where($where)->count();
		$page = $this->page($count, 20);
		$list = $this->common_user_model->alias('u')->field('u.*,o.name as office_n')->join('__COMMON_OFFICE__ o ON u.office=o.id')->where($where)->order('u.create_time desc')->select();
		$this->assign("page", $page->show('Admin'));
		$this->assign( 'list', $list );
		$this->display();
	}
	//编辑医生信息
	function edit() {
		if ( IS_POST ) {
			$id = (int)$_POST['id'];
            $_POST['update_time'] = date('Y-m-d H:i:s',time());
			$result = $this->common_user_model->where(array('id' => $id))->save($_POST);
			if ($result) {
                //记录日志
                LogController::log_record($id,2);
				$this->success('编辑信息成功！');
			} else {
				$this->error('编辑信息失败！');
			}
		} else {
			$id = intval( I( 'get.id' ) );
			$hospital = $this->common_user_model->find($id);
			$this->assign($hospital);
			$this->display();
		}
	}
    //删除医生信息
    function delete() {
        if ( isset( $_POST['ids'] ) ) {//批量逻辑删除
            $ids = implode( ',', $_POST['ids'] );
            $data['del_flg'] = 1;
            $data['update_time'] = date('Y-m-d H:i:s',time());
            if ( $this->common_user_model->where( "id in ($ids)" )->save( $data ) !== false ) {
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
            $data['update_time'] = date('Y-m-d H:i:s',time());
            if ( $this->common_user_model->where( "id in ($object)" )->save( $data ) !== false ) {
                //记录日志
                LogController::modify_log_type($log_id, 4);
                $this->success('恢复成功');
            } else {
                $this->error('恢复失败');
            }
        } else if ( isset( $_GET['id'] ) && $_GET['complete_delete'] ) {//彻底物理删除
            $object = $_GET['object'];
            $log_id = $_GET['id'];
            if ( $this->common_user_model->where( "id in ($object)" )->delete() !== false ) {
                //记录日志
                LogController::modify_log_type($log_id, 5);
                $this->success('彻底删除成功');
            } else {
                $this->error('彻底删除失败');
            }
        } else {//单个逻辑删除
            $id = intval( I( 'get.id' ) );
            $data['del_flg'] = 1;
            $data['update_time'] = date('Y-m-d H:i:s',time());
            if ( $this->common_user_model->where(array('id' => $id))->save($data) !== false ) {
                //记录日志
                LogController::log_record($id,3);
                $this->success('删除成功');
            } else {
                $this->error('删除失败');
            }
        }
    }
}