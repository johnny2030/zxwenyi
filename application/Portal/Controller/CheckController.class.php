<?php
namespace Portal\Controller;
use Common\Controller\HomebaseController;

class CheckController extends HomebaseController {

    public function _initialize() {
        parent::_initialize();
        $id=session('login_id');
        $user = D('Common_user')->find($id);
        //判断信息是否完善
        if (!$user){
            R('Index/register_patient');
        }else if (empty($user['type']) || $user['type'] == 0){
            if (!$this->check_info_pt($user)){
                R('Index/register_patient');
            }
        }else{
            if (!$this->check_info_dc($user)){
                R('Index/register_doctor');
            }
        }
    }
    //判断信息是否完善（医生）
    public function check_info_dc($user) {
        if (empty($user['hospital']) && empty($user['office']) && empty($user['tag']) && empty($user['position'])) {
            return false;
        }else{
            return true;
        }
    }
    //判断信息是否完善（用户）
    public function check_info_pt($user) {
        if (empty($user['m_card_id'])) {
            return false;
        }else{
            return true;
        }
    }
}


