<?php
namespace Portal\Controller;
use Common\Controller\HomebaseZJController;

class CheckZJController extends HomebaseZJController {

    public function _initialize() {
        parent::_initialize();
        $id=session('login_id');
        $user = D('Common_user')->find($id);
        //判断信息是否完善
        if (!$this->check_info($user)){
            R('IndexZJ/register_doctor_zj');
        }
    }
    //判断信息是否完善
    public function check_info($user) {
        if (empty($user['name']) && empty($user['hospital']) && empty($user['office']) && empty($user['tag']) && empty($user['position'])) {
            return false;
        }else{
            return true;
        }
    }
}


