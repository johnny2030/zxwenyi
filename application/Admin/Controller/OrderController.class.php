<?php
/**
 * 医生管理
 */
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class OrderController extends AdminbaseController {

    private $common_user_model;
    private $common_order_model;
	
	function _initialize() {
		parent::_initialize();

        $this->common_user_model = D( 'Common_user' );
        $this->common_order_model = D( 'Common_order' );
	}
	//医生信息列表
	function index() {
		$where = array();
		//姓名
		$name=I('name');
        $start_time = I('start_time');
        $end_time = I('end_time');
        if ( !empty($name) ) {
            $where['p.name|d.name'] = array('like',"%$name%",'and');
        }
        if ( !empty($start_time) && !empty($end_time) ){
            $where['o.time']=array('between',array($start_time,$end_time));
        }elseif ( !empty($start_time) ) {
            $where['o.time'] = array('egt ',$start_time,'and');
        }elseif ( !empty($end_time) ) {
            $where['o.time'] = array('elt',$end_time,'and');
        }
        $where['o.del_flg'] = array('eq',0);
        $count = $this->common_order_model->alias('o')->field('p.name as pname, d.name as dname,(o.money * d.proportion) as dmoney,(o.money * (1 - d.proportion)) as wmoney,(d.proportion * 100) as proportion, o.*')->join('__COMMON_USER__ p ON o.payer=p.id')->join('__COMMON_USER__ d ON o.payee=d.id')->where($where)->count();
		$page = $this->page($count, 20);
		$list = $this->common_order_model->alias('o')->field('p.name as pname, d.name as dname,(o.money * d.proportion) as dmoney,(o.money * (1 - d.proportion)) as wmoney,(d.proportion * 100) as proportion, o.*')->join('__COMMON_USER__ p ON o.payer=p.id')->join('__COMMON_USER__ d ON o.payee=d.id')->where($where)->limit( $page->firstRow, $page->listRows )->order("o.time desc")->select();
        $asum = $this->common_order_model->alias('o')->field('SUM(o.money) as asum')->join('__COMMON_USER__ p ON o.payer=p.id')->join('__COMMON_USER__ d ON o.payee=d.id')->where($where)->limit( $page->firstRow, $page->listRows )->find();
        $wsum = $this->common_order_model->alias('o')->field('SUM(o.money * (1 - d.proportion)) as wsum')->join('__COMMON_USER__ p ON o.payer=p.id')->join('__COMMON_USER__ d ON o.payee=d.id')->where($where)->limit( $page->firstRow, $page->listRows )->find();
		$this->assign("page", $page->show('Admin'));
        $this->assign( 'name', $name );
        $this->assign( 'end_time', $end_time );
        $this->assign( 'start_time', $start_time );
        $this->assign( 'asum', $asum['asum'] );
        $this->assign( 'wsum', $wsum['wsum'] );
		$this->assign( 'list', $list );
		$this->display();
	}
    //删除订单信息
    function delete() {
        if ( isset( $_POST['ids'] ) ) {//批量逻辑删除
            $ids = implode( ',', $_POST['ids'] );
            $data['del_flg'] = 1;
            $data['update_time'] = date('Y-m-d H:i:s',time());
            if ( $this->common_order_model->where( "id in ($ids)" )->save( $data ) !== false ) {
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
            if ( $this->common_order_model->where( "id in ($object)" )->save( $data ) !== false ) {
                //记录日志
                LogController::modify_log_type($log_id, 4);
                $this->success('恢复成功');
            } else {
                $this->error('恢复失败');
            }
        } else if ( isset( $_GET['id'] ) && $_GET['complete_delete'] ) {//彻底物理删除
            $object = $_GET['object'];
            $log_id = $_GET['id'];
            if ( $this->common_order_model->where( "id in ($object)" )->delete() !== false ) {
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
            if ( $this->common_order_model->where(array('id' => $id))->save($data) !== false ) {
                //记录日志
                LogController::log_record($id,3);
                $this->success('删除成功');
            } else {
                $this->error('删除失败');
            }
        }
    }
}