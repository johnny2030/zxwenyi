<?php
/**
 * 医院管理
 */
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class HospitalController extends AdminbaseController {

    private $common_hospital_model;
	
	function _initialize() {
		parent::_initialize();

        $this->common_hospital_model = D( 'Common_hospital' );
	}
	//医院信息列表
	function index() {
		$where = array();
		//姓名
		$name=I('name');
		$this->assign( 'name', $name );
		if ( $name ) $where['name'] = array('like',"%$name%");
        $where['del_flg'] = array('eq',0);
		$count = $this->common_hospital_model->where($where)->count();
		$page = $this->page($count, 20);
		$list = $this->common_hospital_model->where($where)->limit( $page->firstRow, $page->listRows )->order("create_time desc")->select();
		$this->assign("page", $page->show('Admin'));
		$this->assign( 'list', $list );
		$this->display();
	}

	//添加医院信息
	function add() {
		if ( IS_POST ) {
            $_POST['create_time'] = date('Y-m-d H:i:s',time());
			$result = $this->common_hospital_model->add($_POST);
			if ($result) {
                //记录日志
                LogController::log_record($result,1);
				$this->success('添加医院信息成功！');
			} else {
				$this->error('添加医院信息失败！');
			}
		} else {
			$this->display();
		}
	}
	//编辑医院信息
	function edit() {
		if ( IS_POST ) {
			$id = (int)$_POST['id'];
			$result = $this->common_hospital_model->where(array('id' => $id))->save($_POST);
			if ($result) {
                //记录日志
                LogController::log_record($id,2);
				$this->success('编辑医院信息成功！');
			} else {
				$this->error('编辑医院信息失败！');
			}
		} else {
			$id = intval( I( 'get.id' ) );
			$hospital = $this->common_hospital_model->find($id);
			$this->assign($hospital);
			$this->display();
		}
	}
    //删除医院信息
    function delete() {
        if ( isset( $_POST['ids'] ) ) {//批量逻辑删除
            $ids = implode( ',', $_POST['ids'] );
            $data['del_flg'] = 1;
            if ( $this->common_hospital_model->where( "id in ($ids)" )->save( $data ) !== false ) {
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
            if ( $this->common_hospital_model->where( "id in ($object)" )->save( $data ) !== false ) {
                //记录日志
                LogController::modify_log_type($log_id, 4);
                $this->success('恢复成功');
            } else {
                $this->error('恢复失败');
            }
        } else if ( isset( $_GET['id'] ) && $_GET['complete_delete'] ) {//彻底物理删除
            $object = $_GET['object'];
            $log_id = $_GET['id'];
            if ( $this->common_hospital_model->where( "id in ($object)" )->delete() !== false ) {
                //记录日志
                LogController::modify_log_type($log_id, 5);
                $this->success('彻底删除成功');
            } else {
                $this->error('彻底删除失败');
            }
        } else {//单个逻辑删除
            $id = intval( I( 'get.id' ) );
            $data['del_flg'] = 1;
            if ( $this->common_hospital_model->where(array('id' => $id))->save($data) !== false ) {
                //记录日志
                LogController::log_record($id,3);
                $this->success('删除成功');
            } else {
                $this->error('删除失败');
            }
        }
    }
}