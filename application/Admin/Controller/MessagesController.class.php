<?php
/**
 * 医生管理
 */
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class MessagesController extends AdminbaseController {

	function _initialize() {
		parent::_initialize();

	}
	//医生信息列表
	function index() {
		$this->display();
	}
	function sends_many(){
        $this->display();
    }

}