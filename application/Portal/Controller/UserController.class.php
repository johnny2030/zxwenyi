<?php
/**
 * 用户端
 */
namespace Portal\Controller;
use Common\Controller\HomebaseController;

class UserController extends HomebaseController {

    private $common_user_model;
    private $common_tag_model;
    private $common_office_model;
    private $common_hospital_model;

	function _initialize() {
		parent::_initialize();

        $this->common_user_model = D( 'Common_user' );
        $this->common_tag_model = D( 'Common_tag' );
        $this->common_office_model = D( 'Common_office' );
        $this->common_hospital_model = D( 'Common_hospital' );
	}
    //患者登记
    public function register_patient() {
        if ( IS_POST ) {
            $id = (int)session('login_id');
            $result = $this->common_user_model->where(array('id' => $id))->save($_POST);
            if ($result) {
                R('User/info_patient');
            } else {
                $this->error('登记失败！');
            }
        }else{
            $this->display('../Tieqiao/register_patient');
        }
    }
    //患者个人中心
    public function info_patient() {
        $id = (int)session('login_id');
        $patient = $this->common_user_model->find($id);
        $this->assign( 'patient', $patient );
        $this->display('../Tieqiao/info_patient');
    }
    //医生登记
    public function register_doctor() {
        if ( IS_POST ) {
            $id = (int)session('login_id');
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
            $this->display('../Tieqiao/register_doctor');
        }
    }
    //医生个人中心
    public function info_doctor() {
        $id = (int)session('login_id');
        $doctor = $this->common_user_model->find($id);
        $this->assign( 'doctor', $doctor );
        $this->display('../Tieqiao/info_doctor');
    }
    //科室展示
    public function office_preview() {
        $where = array();
        $where['del_flg'] = array('eq',0);
        $list = $this->common_office_model->where($where)->select();
        $this->assign( 'list', $list );
        $this->display('../Tieqiao/office_preview');
    }
    //医生列表
    public function doctor_list() {
        $office_id = $_GET['office_id'];
        $where = array();
        $where['del_flg'] = array('eq',0);
        $where['office'] = array('eq',$office_id);
        $list = $this->common_user_model->where($where)->select();
        $this->assign( 'list', $list );
        $this->display('../Tieqiao/doctor_list');
    }
    //医生详情
    public function doctor_detail() {
        $user_id = $_GET['user_id'];
        $doctor = $this->common_user_model->find($user_id);
        $this->assign( 'doctor', $doctor );
        $this->display('../Tieqiao/doctor_detail');
    }
    //问题列表
    public function question() {
        $this->display('../Tieqiao/question');
    }
    //咨询问题
    public function question_apply() {
        $this->display('../Tieqiao/question_apply');
    }
    //对话列表
    public function question_chat() {
        $this->display('../Tieqiao/question_chat');
    }
}