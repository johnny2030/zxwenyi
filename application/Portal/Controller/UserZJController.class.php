<?php
/**
 * 用户端
 */
namespace Portal\Controller;

class UserZJController extends CheckZJController {

    private $common_user_model;
    private $common_office_model;

	function _initialize() {
		parent::_initialize();

        $this->common_user_model = D( 'Common_user' );
        $this->common_office_model = D( 'Common_office' );

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