<?php
namespace Portal\Controller;
use Common\Controller\HomebaseController;

class IndexController extends HomebaseController {

    private $common_user_model;
    private $common_position_model;
    private $common_office_model;
    private $common_hospital_model;
    private $common_card_model;
    private $common_health_model;
    private $common_healthy_model;

    public function _initialize() {
        parent::_initialize();

        $this->common_user_model = D( 'Common_user' );
        $this->common_office_model = D( 'Common_office' );
        $this->common_position_model = D( 'Common_position' );
        $this->common_hospital_model = D( 'Common_hospital' );
        $this->common_card_model = D( 'Common_card' );
        $this->common_health_model = D( 'Common_health' );
        $this->common_healthy_model = D( 'Common_healthy' );
    }

    //首页
	public function index() {
        $this->display('../Tieqiao/index');
    }
    //医生注册
    public function register_doctor() {
        $id = (int)session('login_id');
        if ( IS_POST ) {
            $_POST['type'] = 1;
            $result = $this->common_user_model->where(array('id' => $id))->save($_POST);
            if ($result) {
                $this->assign( 'check', 0 );
                $this->assign( 'msg_d', '欢迎您加入上海市中西医结合学会医生联盟专家委员会，审核通过后您将成为我们的咨询专家，');
                $this->assign( 'url', "index.php?g=portal&m=user&a=info_doctor");
            } else {
                $this->assign( 'check', 1 );
                $this->assign( 'url', "index.php?g=portal&m=user&a=register_doctor");
            }
            $this->display('../Public/return');
        }else{
            $user = $this->common_user_model->find($id);
            if (empty($user['hospital']) && empty($user['office']) && empty($user['tag']) && empty($user['position'])) {
                $where = array();
                $where['del_flg'] = array('eq',0);
/*                $hlist = $this->common_hospital_model->where($where)->select();
                $this->assign( 'hlist', $hlist );*/
                $olist = $this->common_office_model->where($where)->select();
                $plist = $this->common_position_model->where($where)->select();
                $this->assign( 'olist', $olist );
                $this->assign( 'plist', $plist );
                $this->display('../Tieqiao/register_doctor');
            }else{
                session('flg','redt');
                R('User/info_doctor');
            }
            exit();
        }
    }
    //用户注册
    public function register_patient() {
        $id = (int)session('login_id');
        if ( IS_POST ) {
            $healthy = $_POST['healthy'];
            if (!empty($healthy)){
                $data = array();
                foreach ($healthy as $h){
                    $data['user_id'] = $id;
                    $data['healthy'] = $h;
                    $this->common_healthy_model->add($data);
                }
            }
            $result = $this->common_user_model->where(array('id' => $id))->save($_POST);
            $data = array();
            $data['user_id'] = $id;
            $data['status'] = 1;
            $data['use_time'] = date('Y-m-d H:i:s',time());
            $results = $this->common_card_model->where(array('id' => $_POST['m_card_id']))->save($data);
            if ($result && $results) {
                $this->assign( 'check', 0 );
                $this->assign( 'msg_p', '您也可以通过点击公众号菜单“会员中心”→“我的咨询”对您的健康问题进行咨询，愿我们能为您的身心健康保驾护航！');
                $this->assign( 'url', "index.php?g=portal&m=user&a=question");
            } else {
                $this->assign( 'check', 1 );
                $this->assign( 'url', "index.php?g=portal&m=user&a=register_patient");
            }
            $this->display('../Public/return');
        }else{
            $user = $this->common_user_model->find($id);
            $where_h = array();
            $where_h['up_id'] = array('eq',0);
            $where_h['del_flg'] = array('eq',0);
            $list = $this->common_health_model->where($where_h)->select();
            //所有疾病
            $where_s = array();
            $where_s['up_id'] = array('eq',2);
            $where_s['del_flg'] = array('eq',0);
            $lists = $this->common_health_model->where($where_s)->select();

            $this->assign( 'list', $list );
            $this->assign( 'lists', $lists );
            if (empty($user['m_card_id'])){
                $this->display('../Tieqiao/register_patient');
            }else{
                session('flg','redt');
                R('User/info_patient');
            }
            exit();
        }
    }
    //获取素材总数
    function get_materialcount() {
        require_once 'today/Wechat_tq.php';
        $wechat = new \Wechat_tq( $this );
        $result = $wechat->get_materialcount();
        $this->assign( 'patient', $result );
        $this->display();
    }
    //获取素材列表
    function bg_material() {
        require_once 'today/Wechat_tq.php';
        $wechat = new \Wechat_tq( $this );
        $result = $wechat->bg_material();
        $this->assign( 'patient', $result );
        $this->display();
    }
    //获取永久素材
    function get_material() {
        require_once 'today/Wechat_tq.php';
        $wechat = new \Wechat_tq( $this );
        $result = $wechat->get_material('mNdVxM-2RSlGmkcMe0p9v8vARtULkCj1_6Tl8AhEk8o');
        $this->assign( 'patient', $result );
        $this->display();
    }
    //发送素材
    function send_material() {
        require_once 'today/Wechat_tq.php';
        $wechat = new \Wechat_tq( $this );
        $open_id=session('open_id');
        $wechat->customSendMedia($open_id,'mNdVxM-2RSlGmkcMe0p9v25prBwgiaAlX4I8jyQ25_A');
    }
}


