<?php
/**
 * 用户端
 */
namespace Portal\Controller;
use Common\Controller\HomebasezZJController;

class UserController extends HomebasezZJController {

    private $common_user_model;
    private $common_tag_model;
    private $common_office_model;
    private $common_hospital_model;
    private $common_health_model;

	function _initialize() {
		parent::_initialize();

        $this->common_user_model = D( 'Common_user' );
        $this->common_tag_model = D( 'Common_tag' );
        $this->common_office_model = D( 'Common_office' );
        $this->common_hospital_model = D( 'Common_hospital' );
        $this->common_health_model = D( 'Common_health' );
	}
    //患者登记
    public function register_patient() {
        $id = (int)session('login_id');
        if ( IS_POST ) {
            $result = $this->common_user_model->where(array('id' => $id))->save($_POST);
            if ($result) {
                R('User/info_patient');
            } else {
                $this->error('登记失败！');
            }
        }else{
            $user = $this->common_user_model->find($id);
            if (empty($user['i_card'])){
                $this->display('../Expert/register_patient');
            }else{
                R('User/info_patient');
            }
        }
    }
    //患者个人中心
    public function info_patient() {
        $id = (int)session('login_id');
        $where = array();
        $where['u.id'] = array('eq',$id);
        $patient = $this->common_user_model->alias('u')->field('u.*,h.name as health_n')->join('__COMMON_HEALTH__ h ON u.healthy=h.id','left')->where($where)->select();
        $this->assign( 'patient', $patient[0] );
        $this->display('../Expert/info_patient');
    }
    //医生登记
    public function register_doctor() {
        if ( IS_POST ) {
            $id = (int)session('login_id');
            $_POST['type'] = 1;
            $result = $this->common_user_model->where(array('id' => $id))->save($_POST);
            if ($result) {
                R('User/info_doctor');
            } else {
                $this->error('登记失败！');
            }
        }else{
            $where = array();
            $where['del_flg'] = array('eq',0);
            $hlist = $this->common_hospital_model->where($where)->select();
            $olist = $this->common_office_model->where($where)->select();
            $this->assign( 'hlist', $hlist );
            $this->assign( 'olist', $olist );
            $this->display('../Expert/register_doctor');
        }
    }
    //医生个人中心
    public function info_doctor() {
        $id = (int)session('login_id');
        $doctor = $this->common_user_model->alias('u')->field('u.*,h.name as hospital_n,o.name as office_n,t.name as tag_n')->join('__COMMON_HOSPITAL__ h ON u.hospital=h.id')->join('__COMMON_OFFICE__ o ON u.office=o.id')->join('__COMMON_TAG__ t ON u.tag=t.id')->where(array('u.id' => $id))->find();
        $this->assign( 'doctor', $doctor );
        $this->display('../Expert/info_doctor');
    }
    //科室展示
    public function office_preview() {
        $where = array();
        $where['del_flg'] = array('eq',0);
        $list = $this->common_office_model->where($where)->select();
        $this->assign( 'list', $list );
        $this->display('../Expert/office_preview');
    }
    //医生列表
    public function doctor_list() {
        $office_id = $_GET['office_id'];
        $ofc = $this->common_office_model->find($office_id);
        $where = array();
        $where['u.del_flg'] = array('eq',0);
        $where['u.office'] = array('eq',$office_id);
        $where['u.type'] = array('eq',1);
        $list = $this->common_user_model->alias('u')->field('u.*,h.name as hospital_n')->join('__COMMON_HOSPITAL__ h ON u.hospital=h.id')->where($where)->select();
        $this->assign( 'office_n', $ofc['name'] );
        $this->assign( 'list', $list );
        $this->display('../Expert/doctor_list');
    }
    //医生详情
    public function doctor_detail() {
        $user_id = $_GET['user_id'];
        $doctor = $this->common_user_model->alias('u')->field('u.*,h.name as hospital_n,o.name as office_n,t.name as tag_n')->join('__COMMON_HOSPITAL__ h ON u.hospital=h.id')->join('__COMMON_OFFICE__ o ON u.office=o.id')->join('__COMMON_TAG__ t ON u.tag=t.id')->where(array('u.id' => $user_id))->find();
        $this->assign( 'doctor', $doctor );
        $this->display('../Expert/doctor_detail');
    }
    //咨询问诊
    public function question() {
        R('Rong/index');
    }
    //信息修改
    public function modify() {
        if ( IS_POST ) {
            $id = (int)session('login_id');
            $user = $this->common_user_model->find($id);
            if(!empty($_POST['phone']) && $_POST['phone'] != $user['phone']){
                $where = array();
                $where['phone'] = array('eq',$_POST['phone']);
                $check_user = $this->common_user_model->where($where)->find();
                if (!empty($check_user)) $this->error('该手机号已被使用！');
            }
            if(!empty($_POST['province']) && !empty($_POST['city'])){
                $county = $_POST['county'];
                if (empty($county)){
                    $_POST['county'] = '0';
                }
            }
            $_POST['update_time'] = date('Y-m-d H:i:s',time());
            $result = $this->common_user_model->where(array('id' => $id))->save($_POST);
            if ($result) {
                if ($user['type'] == 0){
                    R('User/info_patient');
                }else{
                    R('User/info_doctor');
                }
            } else {
                $this->error('修改失败！');
            }
        } else {
            $data = $_GET['data'];//数据
            $flg = $_GET['flg'];//数据库字段名
            $menu = $_GET['menu'];//页面字段名
            $check = $_GET['check'];//介绍/擅长
            if ($flg == 'hospital'){
                $where = array();
                $where['del_flg'] = array('eq',0);
                $list = $this->common_hospital_model->where($where)->select();
                $this->assign( 'list', $list );
            }elseif ($flg == 'office'){
                $where = array();
                $where['del_flg'] = array('eq',0);
                $list = $this->common_office_model->where($where)->select();
                $this->assign( 'list', $list );
            }elseif ($flg == 'tag'){
                $where = array();
                $where['del_flg'] = array('eq',0);
                $list = $this->common_tag_model->where($where)->select();
                $this->assign( 'list', $list );
            }elseif ($flg == 'healthy'){
                $where = array();
                $where['up_id'] = array('eq',0);
                $where['del_flg'] = array('eq',0);
                $list = $this->common_health_model->where($where)->select();
                $this->assign( 'list', $list );
            }
            $this->assign( 'data', $data );
            $this->assign( 'flg', $flg );
            $this->assign( 'menu', $menu );
            $this->assign( 'check', $check );
            $this->display('../Expert/modify');
        }
    }
}