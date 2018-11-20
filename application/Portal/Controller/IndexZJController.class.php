<?php
namespace Portal\Controller;
use Common\Controller\HomebaseZJController;

class IndexZJController extends HomebaseZJController {

    private $common_user_model;
    private $common_office_model;

    public function _initialize() {
        parent::_initialize();

        $this->common_user_model = D( 'Common_user' );
        $this->common_office_model = D( 'Common_office' );
    }

    //首页
	public function index() {

    }
    //医生登记（专家登记）
    public function register_doctor_zj() {
        $id = (int)session('login_id');
        if ( IS_POST ) {
            $_POST['type'] = 1;
            $this->common_user_model->where(array('id' => $id))->save($_POST);
            R('UserZJ/info_doctor');
        }else{
            $where = array();
            $where['del_flg'] = array('eq',0);
            $olist = $this->common_office_model->where($where)->select();
            $this->assign( 'olist', $olist );
            $this->display('../Expert/register_doctor');
            exit();
        }
    }
}


