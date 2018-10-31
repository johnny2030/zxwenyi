<?php
namespace Portal\Controller;
use Common\Controller\HomebaseController; 

class AdController extends HomebaseController {

    private $common_ad_model;

    public function _initialize() {
        parent::_initialize();

        $this->common_ad_model = D( 'Common_ad' );
    }

    //首页
	public function index() {
        //主推医生
        $mp_where = array();
        $mp_where['recommend'] = array('eq',2);
        $mp_where['status'] = array('eq',0);
        $mp_where['del_flg'] = array('eq',0);
        $mp_list = $this->doctor_user_model->where($mp_where)->select();
        //推荐医生
        $rc_where = array();
        $rc_where['recommend'] = array('eq',1);
        $rc_where['status'] = array('eq',0);
        $rc_where['del_flg'] = array('eq',0);
        $rc_list = $this->doctor_user_model->where($rc_where)->select();
        $this->assign( 'mp_list', $mp_list );
        $this->assign( 'rc_list', $rc_list );
    	$this->display(":index");
    }

}


