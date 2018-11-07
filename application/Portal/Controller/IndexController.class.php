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
    function register_patient() {
        $id = (int)session('login_id');
        if ( IS_POST ) {
            $_POST['update_time'] = date('Y-m-d H:i:s',time());
            $result = $this->common_user_model->where(array('id' => $id))->save($_POST);
            if ($result) {
                $this->assign( 'check', 0 );
                $this->assign( 'url', 'index.php' );
                $this->display('../Public/return');
            } else {
                $this->assign( 'check', 1 );
                $this->assign( 'url', 'index.php?g=portal&m=index&a=register_patient' );
                $this->display('../Public/return');
            }
        }else{
            $where = array();
            $where['pid'] = array('eq',0);
            $provinceList = $this->china_model->where($where)->order("id asc")->select();
            $where['pid'] = array('eq',110000);
            $cityList = $this->china_model->where($where)->order("id asc")->select();
            $user = $this->common_user_model->find($id);
            $this->assign( 'provinceList', $provinceList );
            $this->assign( 'cityList', $cityList );
            $this->assign( 'user', $user );
            $this->display('/perfect_info');
            exit();
        }
    }
    //医生信息注册
    function register_doctor() {
        $id = (int)session('login_id');
        if ( IS_POST ) {
            $open_id = session('open_id');
            $_POST['status'] = '1';
            $_POST['create_time'] = date('Y-m-d H:i:s',time());
            $result = $this->common_user_model->where( array( 'open_id' => $open_id ) )->save($_POST);
            if ($result) {
                $this->assign( 'check', 0 );
                $this->assign( 'url', 'index.php' );
                $this->display('../Public/return');
            } else {
                $this->assign( 'check', 1 );
                $this->assign( 'url', 'index.php?g=portal&m=index&a=register_doctor' );
                $this->display('../Public/return');
            }
        } else {
            $where = array();
            $where['pid'] = array('eq',0);
            $provinceList = $this->china_model->where($where)->order("id asc")->select();
            $where['pid'] = array('eq',110000);
            $cityList = $this->china_model->where($where)->order("id asc")->select();
            $hlist = $this->common_hospital_model->select();
            $olist = $this->common_office_model->select();
            $tlist = $this->common_tag_model->select();
            $user = $this->common_user_model->find($id);
            $this->assign( 'hlist', $hlist );
            $this->assign( 'olist', $olist );
            $this->assign( 'tlist', $tlist );
            $this->assign( 'provinceList', $provinceList );
            $this->assign( 'cityList', $cityList );
            $this->assign( 'user', $user );
            $this->display('../Doctor/register');
            exit();
        }
    }
}


