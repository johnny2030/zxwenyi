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
     * 获取token
     */
    public function get_token(){
        $id=session('login_id');
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
        $id=session('login_id');
        $lgUser = $this->common_user_model->find($id);
        $sendid= session('send_id');
        $sendUser = $this->common_user_model->find($sendid);
        $token = $this->get_token();
        $data = array();
        $data['appkey'] = RY_KEY;
        $data['token'] = $token;
        $data['lgUser'] = $lgUser;
        $data['sendUser'] = $sendUser;
        $this->ajaxReturn($data);
    }
    /**
     * 聊天咨询
     */
	function index() {
		$this->display();
	}

}