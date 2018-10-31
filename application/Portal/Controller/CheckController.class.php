<?php
namespace Portal\Controller;
use Common\Controller\HomebaseController;

class CheckController extends HomebaseController {

    public function _initialize() {
        parent::_initialize();
        $id=session('login_id');
        $user = D('Common_user')->find($id);
        //判断信息是否完善
        if (!$this->check_info($user)){
            if ($user['status'] == 1){
                R('Index/register_doctor');
            }else{
                R('Index/register_patient');
            }
        }
    }
    //判断信息是否完善
    public function check_info($user) {
        //判断是否是医生
        if ($user['status'] == 1){
            if (empty($user['name']) || empty($user['sex']) || empty($user['phone']) || empty($user['age']) || empty($user['hospital']) || empty($user['office']) || empty($user['tag']) || empty($user['province']) || empty($user['city'])) {
                return false;
            }else{
                return true;
            }
        }else{
            if (empty($user['name']) || empty($user['sex']) || empty($user['phone']) || empty($user['age']) || empty($user['province']) || empty($user['city'])) {
                return false;
            }else{
                return true;
            }
        }
    }
}


