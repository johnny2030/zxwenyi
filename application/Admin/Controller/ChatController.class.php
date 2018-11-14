<?php
/**
 * 聊天咨询
 */
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class ChatController extends AdminbaseController {

    private $common_user_model;
	
	function _initialize() {
		parent::_initialize();

        $this->common_user_model = D( 'Common_user' );
	}
	//科室信息列表
	function index() {
		$this->display();
	}
}