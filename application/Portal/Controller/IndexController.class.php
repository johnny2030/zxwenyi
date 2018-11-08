<?php
namespace Portal\Controller;
use Common\Controller\HomebaseController; 

class IndexController extends HomebaseController {

    private $common_user_model;

    public function _initialize() {
        parent::_initialize();

        $this->common_user_model = D( 'Common_user' );
    }

    //首页
	public function index() {
        $this->display('../Tieqiao/index');
        /*$open_id = session('open_id');
        $where = array();
        $where['open_id'] = array('eq',$open_id);
        $user = $this->common_user_model->where($where)->find();
        if (session('login_id') == ""){
            session('login_id',$user['id']);
        }
        $check = A('Check');
        if (!$check->check_info($user)){
            if ($user['status'] == 1){
                $this->register_doctor();
            }else{
                $this->register_patient();
            }
        }else{
            if ($user['status'] == 1){
                R('Doctor/index');
            }else{
                R('Patient/index');
            }
        }*/
    }
    //患者信息注册
    function register_patient() {}
    //医生信息注册
    function register_doctor() {}
}


