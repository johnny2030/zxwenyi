<?php
/**
 * 用户端
 */
namespace Portal\Controller;
use Common\Controller\HomebaseZJController;

class UserZJController extends HomebaseZJController {

    private $common_user_model;
    private $common_tag_model;
    private $common_office_model;
    private $common_hospital_model;
    private $common_health_model;
    private $common_card_model;

	function _initialize() {
		parent::_initialize();

        $this->common_user_model = D( 'Common_user' );
        $this->common_tag_model = D( 'Common_tag' );
        $this->common_office_model = D( 'Common_office' );
        $this->common_hospital_model = D( 'Common_hospital' );
        $this->common_health_model = D( 'Common_health' );
        $this->common_card_model = D( 'Common_card' );
	}
    //医生登记
    public function register_doctor() {
        $id = (int)session('login_id');
        if ( IS_POST ) {
            $_POST['type'] = 1;
            $result = $this->common_user_model->where(array('id' => $id))->save($_POST);
            if ($result) {
                R('UserZJ/info_doctor');
            } else {
                $this->error('登记失败！');
            }
        }else{
            $user = $this->common_user_model->find($id);
            if (empty($user['phone'])){
                $where = array();
                $where['del_flg'] = array('eq',0);
                $hlist = $this->common_hospital_model->where($where)->select();
                $olist = $this->common_office_model->where($where)->select();
                $this->assign( 'hlist', $hlist );
                $this->assign( 'olist', $olist );
                $this->display('../Expert/register_doctor');
            }else{
                R('UserZJ/info_doctor');
            }
        }
    }
    //医生个人中心
    public function info_doctor() {
        $flg = session('flg');
        if ( IS_POST && empty($flg)) {
            $id = (int)session('login_id');
            $_POST['update_time'] = date('Y-m-d H:i:s',time());
            $result = $this->common_user_model->where(array('id' => $id))->save($_POST);
            if ($result){
                session('flg','redt');
                R('UserZJ/info_doctor');
            }else{
                $this->ajaxReturn(0);
            }
        }else{
            session('flg',null);
            $id = (int)session('login_id');
            $doctor = $this->common_user_model->where(array('id' => $id))->find();
            $where = array();
            $where['del_flg'] = array('eq',0);
            $list = $this->common_office_model->where($where)->select();
            $this->assign( 'list', $list );
            $this->assign( 'doctor', $doctor );
            $this->display('../Expert/info_doctor');
        }
    }
}