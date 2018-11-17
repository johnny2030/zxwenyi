<?php
/**
 * 管理员设置
 */
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class ManagerController extends AdminbaseController {

    private $common_user_model;
	
	function _initialize() {
		parent::_initialize();

        $this->common_user_model = D( 'Common_user' );
	}
	//管理员列表
	function index() {
        $where = array();
        //姓名
        $name=I('name');
        $this->assign( 'name', $name );
        if ( $name ) $where['name'] = array('like',"%$name%");
        $where['type'] = array('eq',2);
        $where['del_flg'] = array('eq',0);
        $count = $this->common_user_model->where($where)->count();
        $page = $this->page($count, 20);
        $list = $this->common_user_model->where($where)->order('create_time desc')->select();
        $this->assign("page", $page->show('Admin'));
        $this->assign( 'list', $list );
        $this->display();
	}
    //添加管理员
    function add() {
        if ( IS_POST ) {
            //图片上传
            $_POST['smeta']['thumb'] = sp_asset_relative_url($_POST['smeta']['thumb']);
            $_POST['photo'] = json_encode($_POST['smeta']);
            $_POST['type'] = 2;
            $_POST['create_time'] = date('Y-m-d H:i:s',time());
            $result = $this->common_user_model->add($_POST);
            if ($result) {
                //记录日志
                LogController::log_record($result,1);
                $this->success('添加管理员成功！');
            } else {
                $this->error('添加管理员失败！');
            }
        } else {
            $this->display();
        }
    }
	//编辑管理员
	function edit() {
		if ( IS_POST ) {
			$id = (int)$_POST['id'];
            $_POST['smeta']['thumb'] = sp_asset_relative_url($_POST['smeta']['thumb']);
            $_POST['photo'] = json_encode($_POST['smeta']);
            $_POST['update_time'] = date('Y-m-d H:i:s',time());
			$result = $this->common_user_model->where(array('id' => $id))->save($_POST);
			if ($result) {
                //记录日志
                LogController::log_record($id,2);
				$this->success('编辑管理员成功！');
			} else {
				$this->error('编辑管理员失败！');
			}
		} else {
			$id = intval( I( 'get.id' ) );
			$manager = $this->common_user_model->find($id);
			$this->assign($manager);
			$this->display();
		}
	}
    //设置为管理员
    function set_manager() {
        $id = intval( I( 'get.id' ) );
        $flg = $_GET['flg'];
        if ($flg == 1){
            $where = array();
            $where['flg'] = array('eq',1);
            $where['del_flg'] = array('eq',0);
            $manager = $this->common_user_model->where($where)->find();
            if(!empty($manager)){
                $this->error('已有管理员启用，请先停用！');
            }
        }
        $data = array();
        $data['flg'] = $flg;
        $data['update_time'] = date('Y-m-d H:i:s',time());
        $result = $this->common_user_model->where(array('id' => $id))->save($data);
        if ($result) {
            $this->success('设置成功！');
        } else {
            $this->error('设置失败！');
        }
    }
    //删除管理员
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