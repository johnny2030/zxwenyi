<?php
/**
 * 医生管理
 */
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class DoctorController extends AdminbaseController {

    private $common_user_model;
    private $common_schedule_model;
    private $common_schedule_info_model;
	
	function _initialize() {
		parent::_initialize();

        $this->common_user_model = D( 'Common_user' );
        $this->common_schedule_model = D( 'Common_schedule' );
        $this->common_schedule_info_model = D( 'Common_schedule_info' );
	}
	//医生信息列表
	function index() {
		$where = array();
		//姓名
		$name=I('name');
		$this->assign( 'name', $name );
		if ( $name ) $where['u.name'] = array('like',"%$name%");
        $where['u.type'] = array('neq',0);
        $where['u.open_id'] = array('neq','');
        $where['u.del_flg'] = array('eq',0);
		$count = $this->common_user_model->alias('u')->where($where)->count();
		$page = $this->page($count, 20);
		$list = $this->common_user_model->alias('u')->field('u.*,o.name as office_n,t.name as tag_n,p.name as position_n')->join('__COMMON_OFFICE__ o ON u.office=o.id','left')->join('__COMMON_TAG__ t ON u.tag=t.id','left')->join('__COMMON_POSITION__ p ON u.position=p.id','left')->where($where)->order('u.create_time desc')->select();
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
    //审核通过/驳回
    function check(){
        $id = $_GET['id'];
        $user = $this->common_user_model->find($id);
        if ($user['status'] == 0){
            require_once 'today/Wechat_tq.php';
            $wechat = new \Wechat_tq( $this );
        }else{
            require_once 'today/Wechat_zj.php';
            $wechat = new \Wechat_zj( $this );
        }
        $reason = $_GET['reason'];
        $check = $_GET['check'];
        $data = array();
        $data['check'] = $check;
        if (!empty($reason)){
            $data['reason'] = $reason;
            $result = $this->common_user_model->where(array('id' => $id))->save($data);
            if ($result){
                $wechat->customSendImg($user['open_id'],'http://tieqiao.zzzpsj.com/index.php?g=portal&m=user&a=info_doctor','您的审核被驳回','驳回原因：'.$reason);
                $this->ajaxReturn(0);
            }else{
                $this->ajaxReturn(1);
            }
        }else{
            $result = $this->common_user_model->where(array('id' => $id))->save($data);
            if ($result) {
                $wechat->customSendImg($user['open_id'],'http://tieqiao.zzzpsj.com/index.php?g=portal&m=user&a=question','您已成为中西医结合学会医生联盟咨询专家，欢迎您参与咨询活动','点击这里立即查看');
                $this->success('医生审核成功！');
            } else {
                $this->error('医生审核失败！');
            }
        }
    }
    //排班
    function submenu() {
        if ( IS_POST ) {
            $id = $_POST['id'];
            $ids = $_POST['ids'];
            $hospital = $_POST['hospital'];
            $time = $_POST['time'];
            $office = $_POST['office'];
            $nature = $_POST['nature'];
            $info = $this->common_schedule_info_model->where(array('user_id' => $id))->select();
            if (empty($info)){
                for ($i = 0; $i < count($ids);$i++){
                    $data = array();
                    $data['user_id'] = $id;
                    $data['scd_id'] = $ids[$i];
                    $data['hospital'] = $hospital[$i];
                    $data['time'] = $time[$i];
                    $data['office'] = $office[$i];
                    $data['nature'] = $nature[$i];
                    $this->common_schedule_info_model->add($data);
                }
            }else{
                for ($i = 0; $i < count($ids);$i++){
                    $data = array();
                    $data['user_id'] = $id;
                    $data['scd_id'] = $ids[$i];
                    $data['hospital'] = $hospital[$i];
                    $data['time'] = $time[$i];
                    $data['office'] = $office[$i];
                    $data['nature'] = $nature[$i];
                    $where = array();
                    $where['user_id'] = array('eq',$id);
                    $where['scd_id'] = array('eq',$ids[$i]);
                    $this->common_schedule_info_model->where($where)->save($data);
                }
            }
            $this->display();
        }else{
            $id = $_GET['id'];
            $where = array();
            $where['si.user_id'] = array('eq',$id);
            $scd_list = $this->common_schedule_model->alias('s')->field('s.*,si.hospital as hospital,si.time as time,si.office as office,si.nature as nature')->join('__COMMON_SCHEDULE_INFO__ si ON si.scd_id=s.id','left')->where($where)->order("s.week asc,s.times asc")->select();
            if (empty($scd_list)){
                $list = $this->common_schedule_model->order("week asc,times asc")->select();
                $this->assign( 'list', $list );
            }else{
                $this->assign( 'list', $scd_list );
            }
            $this->assign( 'id', $id );
            $this->display();
        }
    }
}