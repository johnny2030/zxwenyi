<?php
/**
 * 会员卡管理
 */
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class McardController extends AdminbaseController {

    private $common_user_model;
    private $common_card_model;
	
	function _initialize() {
		parent::_initialize();

        $this->common_user_model = D( 'Common_user' );
        $this->common_card_model = D( 'Common_card' );
	}
	//会员卡信息列表
	function index() {
		$where = array();
		//会员卡号
		$card_number=I('card_number');
		$this->assign( 'card_number', $card_number );
		if ( $card_number ) $where['c.card_number'] = array('eq',$card_number);
        $where['c.del_flg'] = array('eq',0);
		$count = $this->common_card_model->alias('c')->where($where)->count();
		$page = $this->page($count, 20);
		$list = $this->common_card_model->alias('c')->field('c.*,u.name as name')->join('__COMMON_USER__ u ON c.user_id=u.id','left')->where($where)->limit( $page->firstRow, $page->listRows )->order("c.create_time desc")->select();
		$this->assign("page", $page->show('Admin'));
		$this->assign( 'list', $list );
		$this->display();
	}

	//添加会员卡
	function add() {
		if ( IS_POST ) {
            $_POST['create_time'] = date('Y-m-d H:i:s',time());
			$result = $this->common_card_model->add($_POST);
			if ($result) {
                //记录日志
                LogController::log_record($result,1);
				$this->success('添加会员卡成功！');
			} else {
				$this->error('添加会员卡失败！');
			}
		} else {
			$this->display();
		}
	}
    //编辑会员卡
    function edit() {
        if ( IS_POST ) {
            $id = (int)$_POST['id'];
            $result = $this->common_card_model->where(array('id' => $id))->save($_POST);
            if ($result) {
                //记录日志
                LogController::log_record($id,2);
                $this->success('编辑会员卡成功！');
            } else {
                $this->error('编辑会员卡失败！');
            }
        } else {
            $id = intval( I( 'get.id' ) );
            $card = $this->common_card_model->find($id);
            $this->assign($card);
            $this->display();
        }
    }
    //删除会员卡
    function delete() {
        if ( isset( $_POST['ids'] ) ) {//批量逻辑删除
            $ids = implode( ',', $_POST['ids'] );
            $data['del_flg'] = 1;
            if ( $this->common_card_model->where( "id in ($ids)" )->save( $data ) !== false ) {
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
            if ( $this->common_card_model->where( "id in ($object)" )->save( $data ) !== false ) {
                //记录日志
                LogController::modify_log_type($log_id, 4);
                $this->success('恢复成功');
            } else {
                $this->error('恢复失败');
            }
        } else if ( isset( $_GET['id'] ) && $_GET['complete_delete'] ) {//彻底物理删除
            $object = $_GET['object'];
            $log_id = $_GET['id'];
            if ( $this->common_card_model->where( "id in ($object)" )->delete() !== false ) {
                //记录日志
                LogController::modify_log_type($log_id, 5);
                $this->success('彻底删除成功');
            } else {
                $this->error('彻底删除失败');
            }
        } else {//单个逻辑删除
            $id = intval( I( 'get.id' ) );
            $data['del_flg'] = 1;
            if ( $this->common_card_model->where(array('id' => $id))->save($data) !== false ) {
                //记录日志
                LogController::log_record($id,3);
                $this->success('删除成功');
            } else {
                $this->error('删除失败');
            }
        }
    }
    //判断会员卡号是否存在
    function check_number(){
	    $where = array();
        $card_number=I('card_number');
        $where['card_number'] = array('eq',$card_number);
        $where['del_flg'] = array('eq',0);
        $list = $this->common_card_model->where($where)->select();
        if (empty($list)){
            $this->ajaxReturn('0');
        }else{
            $this->ajaxReturn('1');
        }
    }
}