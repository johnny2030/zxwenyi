<?php
/**
 * 聊天咨询
 */
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

require_once 'today/config.php';
require_once 'today/RongCloud/rongcloud.php';
require_once 'simplewind/Core/Library/Think/Upload/Driver/Qiniu.class.php';
class ChatController extends AdminbaseController {

    private $common_user_model;
    private $rong_token_model;
	
	function _initialize() {
		parent::_initialize();

        $this->common_user_model = D( 'Common_user' );
        $this->rong_token_model = D( 'Rong_token' );
	}
    /**
     * 聊天咨询
     */
    function index() {
        $this->display();
    }
    /**
     * 获取token
     */
    public function get_token($id){
        $where = array();
        $where['id'] = array('eq',$id);
        $rong_token = $this->rong_token_model->where($where)->find();
        if (empty($rong_token)){
            $RongCloud = new \RongCloud(RY_KEY, RY_SECRET);
            $user = $this->common_user_model->find($id);
            $token = json_decode($RongCloud->User()->getToken($user['id'], $user['name'], $user['photo']), true);
            $data['token'] = $token['token'];
            $data['id'] = $id;
            $this->rong_token_model->add($data);
            return $token['token'];
        }else{
            return $rong_token['token'];
        }
    }
    /**
     * 获取七牛token
     */
    public function getUpToken(){
        $config = array(
            'secretKey'      => SECRETKEY,
            'accessKey'      => ACCESSKEY,
            'domain'         => DOMAIN,
            'bucket'         => BUCKET,
            'timeout'        => 300,
        );
        $qiniu = new \Think\Upload\Driver\Qiniu($config);
        $upToken = $qiniu->getUpToken();
        $this->ajaxReturn($upToken);
    }
    /**
     * 获取初始数据
     */
    public function get_data(){
        $where = array();
        $where['flg'] = array('eq',1);
        $where['type'] = array('eq',3);
        $where['types'] = array('eq',1);
        $where['del_flg'] = array('eq',0);
        $user = $this->common_user_model->where($where)->find();
        if (!empty($user['type'])){
            $user['name'] = '铁樵专家';
            $user['photo'] = '../upload_img/head_tq/doctor.png';
        }
        $token = $this->get_token($user['id']);
        $data = array();
        $data['appkey'] = RY_KEY;
        $data['token'] = $token;
        $data['manager_user'] = $user;
        $this->ajaxReturn($data);
    }
    /**
     * 获取用户信息
     */
    public function get_user() {
        $id = $_GET['userId'];
        $patient = $this->common_user_model->find($id);
        if (!empty($patient['type'])){
            $patient['name'] = '铁樵专家';
            $patient['photo'] = '../upload_img/head_tq/doctor.png';
        }
        $this->ajaxReturn($patient);
    }

}