<?php
/**
 * 用户端
 */
namespace Portal\Controller;

class UserController extends CheckController {

    /*private $wxinfor_model;*/
    private $common_user_model;
    private $formError = array();

	function _initialize() {
		parent::_initialize();

        /*$this->wxinfor_model = D( 'Wxinfor' );*/
        $this->common_user_model = D( 'Common_user' );
	}
    //患者登记
    public function register_patient() {
        $this->display('../Tieqiao/register_patient');
    }
    //患者个人中心
    public function info_patient() {
        $this->display('../Tieqiao/info_patient');
    }
    //医生登记
    public function register_doctor() {
        $this->display('../Tieqiao/register_doctor');
    }
    //医生个人中心
    public function info_doctor() {
        $this->display('../Tieqiao/info_doctor');
    }
    //科室展示
    public function office_preview() {
        $this->display('../Tieqiao/office_preview');
    }
    //医生列表
    public function doctor_list() {
        $this->display('../Tieqiao/doctor_list');
    }
    //医生详情
    public function doctor_detail() {
        $this->display('../Tieqiao/doctor_detail');
    }
}