<?php
/**
 * 咨询问诊
 */
namespace Portal\Controller;

class MessagesController extends CheckController  {

    private $common_user_model;
    private $common_tag_model;
    private $common_office_model;
    private $common_hospital_model;
    private $common_health_model;
    private $common_messages_model;
    private $common_record_model;
    private $common_evaluate_model;
    private $common_operation_model;
    private $common_chat_model;

	function _initialize() {
		parent::_initialize();

        $this->common_user_model = D( 'Common_user' );
        $this->common_tag_model = D( 'Common_tag' );
        $this->common_office_model = D( 'Common_office' );
        $this->common_hospital_model = D( 'Common_hospital' );
        $this->common_health_model = D( 'Common_health' );
        $this->common_messages_model = D( 'Common_messages' );
        $this->common_record_model = D( 'Common_record' );
        $this->common_evaluate_model = D( 'Common_evaluate' );
        $this->common_operation_model = D( 'Common_operation' );
        $this->common_chat_model = D( 'Common_chat' );
	}
    //转发
    public function index() {
        $id = (int)session('login_id');
        $where = array();
        $where['m.type'] = array('eq',2);
        $where['m.status'] = array('neq',2);
        $where['m.del_flg'] = array('eq',0);
        $where['p.doctor_id'] = array('eq',$id);
        $msg_list = $this->common_messages_model->alias('m')->field('m.*,u.name as name,u.photo as photo')->join('__COMMON_USER__ u ON m.user_id=u.id')->join('__COMMON_OPERATION__ p ON m.id=p.msg_id')->where($where)->order("m.status asc,m.operation_time desc")->select();
        $this->assign( 'msg_list', $msg_list );
        $this->display('../Tieqiao/messages');
    }
    //群发
    public function forward() {
        $where = array();
        $where['m.type'] = array('eq',1);
        $where['m.status'] = array('neq',2);
        $where['m.del_flg'] = array('eq',0);
        $msg_list = $this->common_messages_model->alias('m')->field('m.*,u.name as name,u.photo as photo')->join('__COMMON_USER__ u ON m.user_id=u.id')->where($where)->order("m.status asc,m.operation_time desc")->select();
        $this->assign( 'msg_list', $msg_list );
        $this->display('../Tieqiao/forward');
    }
/*    //用户咨询
    public function advice() {
        $where = array();
        $where['m.status'] = array('neq',2);
        $where['m.del_flg'] = array('eq',0);
        $msg_list = $this->common_messages_model->alias('m')->field('m.*,u.name as name,u.photo as photo')->join('__COMMON_USER__ u ON m.user_id=u.id')->where($where)->order("m.status asc,m.create_time desc")->select();
        $this->assign( 'msg_list', $msg_list );
        $this->display('../Tieqiao/advice');
    }*/
    //评价结论
    public function evaluate() {
        if ( IS_POST ) {
            $_POST['user_id'] = (int)session('login_id');
            $_POST['user_time'] = date('Y-m-d H:i:s',time());
            $this->common_evaluate_model->where(array('msg_id' => $_POST['msg_id']))->save($_POST);
            $msg_info = $this->common_messages_model->alias('m')->field('m.*,u.name as name')->join('__COMMON_USER__ u ON m.user_id=u.id')->where(array('m.id' => $_POST['msg_id']))->find();
            if (empty($msg_info['doctor_id'])){
                $manager_user = $this->common_user_model->find($msg_info['manager_id']);
            }else{
                $doctor_user = $this->common_user_model->find($msg_info['doctor_id']);
                $manager_user = $this->common_user_model->find($msg_info['manager_id']);
            }
            //铁樵
            require_once 'today/Wechat_tq.php';
            $wechat = new \Wechat_tq( $this );
            //专家
            require_once 'today/Wechat_zj.php';
            $wechat_zj = new \Wechat_zj( $this );
            //推送医生
            if (empty($doctor_user)){
                //推送管理员
                if ($manager_user['status'] == 0){
                    $wechat->customSendImg($manager_user['open_id'],'http://tieqiao.zzzpsj.com/index.php?g=portal&m=messages&a=detail_mg&id='.$_POST['msg_id'],'您收到了'.$msg_info['name'].'的评价','点击这里查看详情');
                }else{
                    $wechat_zj->customSendImg($manager_user['open_id'],'http://tieqiao.zzzpsj.com/index.php?g=portal&m=messages&a=detail_mg&id='.$_POST['msg_id'],'您收到了'.$msg_info['name'].'的评价','点击这里查看详情');
                }
            }else{
                //推送管理员
                if ($manager_user['status'] == 0){
                    $wechat->customSendImg($manager_user['open_id'],'http://tieqiao.zzzpsj.com/index.php?g=portal&m=messages&a=detail_mg&id='.$_POST['msg_id'],$doctor_user['name'].'收到了'.$msg_info['name'].'的评价','点击这里查看详情');
                }else{
                    $wechat_zj->customSendImg($manager_user['open_id'],'http://tieqiao.zzzpsj.com/index.php?g=portal&m=messages&a=detail_mg&id='.$_POST['msg_id'],$doctor_user['name'].'收到了'.$msg_info['name'].'的评价','点击这里查看详情');
                }
                //推送医生
                if ($doctor_user['status'] == 0){
                    $wechat->customSendImg($doctor_user['open_id'],'http://tieqiao.zzzpsj.com/index.php?g=portal&m=messages&a=detail&id='.$_POST['msg_id'],'您收到了'.$msg_info['name'].'的评价','点击这里查看详情');
                }else{
                    $wechat->customSendImg($doctor_user['open_id'],'http://tieqiao.zzzpsj.com/index.php?g=portal&m=messages&a=detail&id='.$_POST['msg_id'],'您收到了'.$msg_info['name'].'的评价','点击这里查看详情');
                }
            }
            $this->redirect('Messages/detail', array('id' => $_POST['msg_id']));
        }
    }
    /*public function evaluate() {
        $flg = session('flg');
        $id = (int)session('login_id');
        if ( IS_POST  && empty($flg)) {
            session('flg','redt');
            $_POST['user_id'] = $id;
            $_POST['user_time'] = date('Y-m-d H:i:s',time());
            $this->common_evaluate_model->where(array('id' => $_POST['id']))->save($_POST);
            $msg_info = $this->common_messages_model->alias('m')->field('m.*,u.name as name,d.open_id as open_id,d.status as status')->join('__COMMON_USER__ u ON m.user_id=u.id')->join('__COMMON_USER__ d ON m.doctor_id=d.id')->where(array('m.id' => $_POST['msg_id']))->find();
            if ($msg_info['status'] == 0){
                require_once 'today/Wechat_tq.php';
                $wechat = new \Wechat_tq( $this );
            }else{
                require_once 'today/Wechat_zj.php';
                $wechat = new \Wechat_zj( $this );
            }
            $wechat->customSendImg($msg_info['open_id'],'http://tieqiao.zzzpsj.com/index.php?g=portal&m=messages&a=evaluate&msg_id='.$_POST['id'],'您收到了'.$msg_info['name'].'的评价','点击这里查看');
            $this->redirect('Messages/evaluate', array('msg_id' => $_POST['id']));
        }else{
            session('flg',null);
            $msg_id = $_GET['msg_id'];
            $user = $this->common_user_model->find($id);
            $elte_info = $this->common_evaluate_model->alias('e')->field('e.*,u.name as uname,u.age as age,u.sex as sex,u.photo as uphoto')->join('__COMMON_USER__ u ON e.user_id=u.id','left')->where(array('e.id' => $msg_id))->find();
            $this->assign( 'type', $user['type'] );
            $this->assign( 'msg_id', $elte_info['msg_id'] );
            $this->assign( 'elte_info', $elte_info );
            $this->display('../Tieqiao/evaluate');
        }
    }*/
    //问题详情（患者）
    public function chat_p() {
        //咨询问题
        $id = $_GET['id'];
        $msg_Info = $this->common_messages_model->find($id);
        if (empty($msg_Info['doctor_id'])){
            session('send_id',$msg_Info['manager_id']);
        }else{
            session('send_id',$msg_Info['doctor_id']);
        }
        $this->assign( 'msg', $msg_Info );
        $this->display('../Tieqiao/chat_p');
    }
    //问题详情（医生）
    public function detail() {
	    //咨询问题
	    $id = $_GET['id'];
	    $type = (int)session('type');
        $where = array();
        $where['m.id'] = array('eq',$id);
        $msg_Info = $this->common_messages_model->alias('m')->field('m.*,u.name as name,u.photo as photo,u.sex as sex,u.age as age')->join('__COMMON_USER__ u ON m.user_id=u.id')->where($where)->find();
        $where_r = array();
        $where_r['message_id'] = array('eq',$msg_Info['id']);
        $where_r['user_id'] = array('eq',$msg_Info['user_id']);
        $list = $this->common_record_model->where($where_r)->select();
        $chat_list = array();
        //咨询记录
        if ($msg_Info['status'] == 2){
            $where = array();
            $where['msg_id'] = array('eq',$id);
            $chat_lists = $this->common_chat_model->where($where)->select();
            foreach ($chat_lists as $chat) {
                $content = urldecode($chat['content']);
                $chat['content'] = $content;
                $chat_list[] = $chat;
            }
            $elte_info = $this->common_evaluate_model->where($where)->find();
        } elseif ($type != 0 && !empty($msg_Info['operation_reason'])){
            //问题返回记录
            $where = array();
            $where['msg_id'] = array('eq',$id);
            $chat_lists = $this->common_chat_model->where($where)->select();
            foreach ($chat_lists as $chat) {
                $chat['content'] = urldecode($chat['content']);
                $chat_list[] = $chat;
            }
        }
        if ($type == 0){
            if (empty($msg_Info['doctor_id'])){
                session('send_id',$msg_Info['manager_id']);
            }else{
                session('send_id',$msg_Info['doctor_id']);
            }
        }else{
            session('send_id',$msg_Info['user_id']);
        }
        $this->assign( 'list', $list );
        $this->assign( 'msg', $msg_Info );
        $this->assign( 'chat_list', $chat_list );
        $this->assign( 'elte_info', $elte_info );
        $this->assign( 'type', $type );
        $this->assign( 'login_id', session('login_id') );
        $this->display('../Tieqiao/customer_detail');
    }
    //问题详情（管理员）
    public function detail_mg() {
        //咨询问题
        $id = $_GET['id'];
        $where = array();
        $where['m.id'] = array('eq',$id);
        $msg_Info = $this->common_messages_model->alias('m')->field('m.*,u.name as name,u.photo as photo,u.sex as sex,u.age as age')->join('__COMMON_USER__ u ON m.user_id=u.id')->where($where)->find();
        //病例图片
        $where_r = array();
        $where_r['message_id'] = array('eq',$msg_Info['id']);
        $where_r['user_id'] = array('eq',$msg_Info['user_id']);
        $list = $this->common_record_model->where($where_r)->select();
        $chat_list = array();
        //咨询记录
        if ($msg_Info['status'] == 2){
            $where = array();
            $where['msg_id'] = array('eq',$id);
            $chat_lists = $this->common_chat_model->where($where)->select();
            foreach ($chat_lists as $chat) {
                $content = urldecode($chat['content']);
                $chat['content'] = $content;
                $chat_list[] = $chat;
            }
            $elte_info = $this->common_evaluate_model->where($where)->find();
        } elseif (!empty($msg_Info['operation_reason'])){
            //问题返回记录
            $where = array();
            $where['msg_id'] = array('eq',$id);
            $chat_lists = $this->common_chat_model->where($where)->select();
            foreach ($chat_lists as $chat) {
                $chat['content'] = urldecode($chat['content']);
                $chat_list[] = $chat;
            }
        }
        session('send_id',$msg_Info['user_id']);
        $this->assign( 'list', $list );
        $this->assign( 'msg', $msg_Info );
        $this->assign( 'chat_list', $chat_list );
        $this->assign( 'elte_info', $elte_info );
        $this->assign( 'type', session('type') );
        $this->assign( 'login_id', session('login_id') );
        $this->display('../Tieqiao/customer_detail_mg');
    }
    //接受咨询
    function handle(){
        $msg_id = $_GET['msg_id'];
        $id = (int)session('login_id');
        $type = (int)session('type');
        $msg_info = $this->common_messages_model->find($msg_id);
        if ($type != 3){
            if (empty($msg_info['doctor_id'])){
                $data = array();
                $data['doctor_id'] = $id;
                $result = $this->common_messages_model->where(array('id' => $msg_id))->save($data);
                if ($result){
                    $this->ajaxReturn('0');
                }else{
                    $this->ajaxReturn('2');
                }
            }else{
                $this->ajaxReturn('1');
            }
        }else{
            if (empty($msg_info['manager_id'])){
                $data = array();
                $data['manager_id'] = $id;
                $result = $this->common_messages_model->where(array('id' => $msg_id))->save($data);
                if ($result){
                    $this->ajaxReturn('0');
                }else{
                    $this->ajaxReturn('2');
                }
            }else{
                $this->ajaxReturn('1');
            }
        }
    }
    //判断处理次数
    function check_fq(){
        $id = intval( I( 'get.id' ) );
        $msg_info = $this->common_messages_model->find($id);
        if ($msg_info == "" || $msg_info == null){
            $this->ajaxReturn('2');
        }else if ($msg_info['operation_fq'] < 2){
            $this->ajaxReturn('0');
        }else{
            $this->ajaxReturn('1');
        }
    }
    //转发医生显示
    function send_show(){
        $id = intval( I( 'get.id' ) );
        $where = array();
        $where['u.type'] = array('eq',1);
        $where['u.check'] = array('eq',1);
        $where['u.del_flg'] = array('eq',0);
        $list = $this->common_user_model->alias('u')->field('u.*,o.name as office_n,t.name as tag_n')->join('__COMMON_OFFICE__ o ON u.office=o.id','left')->join('__COMMON_TAG__ t ON u.tag=t.id','left')->where($where)->order('u.create_time desc')->select();
        $this->assign( 'msg_id', $id );
        $this->assign( 'list', $list );
        $this->display('../Tieqiao/send_show');
    }
    //转发医生
    function send_handle(){
        $ids = $_POST['ids'];
        $msg_id = $_POST['msg_id'];
        $msg_info = $this->common_messages_model->find($msg_id);

        $data = array();
        $data['type'] = 2;
        $data['status'] = 0;
        $data['operation_fq'] = $msg_info['operation_fq'] + 1;
        $data['operation_time'] = date('Y-m-d H:i:s',time());
        $this->common_messages_model->where(array('id' => $msg_id))->save($data);

        $user = $this->common_user_model->find($msg_info['user_id']);
        foreach ($ids as $id) {
            $sendUser = $this->common_user_model->find($id);
            $opt = $this->common_operation_model->where(array('msg_id' => $msg_id,'doctor_id' => $id))->find();
            if (!$opt){
                $datas = array();
                $datas['msg_id'] = $msg_id;
                $datas['doctor_id'] = $id;
                $this->common_operation_model->add($datas);
            }
            if ($sendUser['status'] == 0){
                $url = 'http://tieqiao.zzzpsj.com/index.php?g=portal&m=messages&a=detail&id='.$msg_id;
                $this->template_send_tq($msg_info['content'],$user['name'],$sendUser['open_id'],$url);
            }else{
                $url = 'http://tieqiao.zzzpsj.com/index.php?g=portal&m=messages&a=detail&id='.$msg_id;
                $this->template_send_zj($msg_info['content'],$sendUser['open_id'],$url);
            }
        }
        $this->redirect('Messages/detail_mg', array('id' => $msg_id));
        /*$this->redirect('Messages/send_show', array('id' => $msg_id), 3, '转发成功');*/
    }
    //群发医生
    function forward_handle(){
        $id = intval( I( 'get.id' ) );
        $msg_info = $this->common_messages_model->find($id);
        $data = array();
        $data['type'] = 1;
        $data['status'] = 0;
        $data['operation_fq'] = $msg_info['operation_fq'] + 1;
        $data['operation_time'] = date('Y-m-d H:i:s',time());
        $this->common_messages_model->where(array('id' => $id))->save($data);
        $where = array();
        $where['type'] = array('eq',1);
        $where['check'] = array('eq',1);
        $where['del_flg'] = array('eq',0);
        $list = $this->common_user_model->where($where)->select();

        $user = $this->common_user_model->find($msg_info['user_id']);
        foreach ($list as $sendUser) {
            if ($sendUser['status'] == 0){
                $url = 'http://tieqiao.zzzpsj.com/index.php?g=portal&m=messages&a=detail&id='.$id;
                $this->template_send_tq($msg_info['content'],$user['name'],$sendUser['open_id'],$url);
            }else{
                $url = 'http://tieqiao.zzzpsj.com/index.php?g=portal&m=messages&a=detail&id='.$id;
                $this->template_send_zj($msg_info['content'],$sendUser['open_id'],$url);
            }
        }
        $this->ajaxReturn('0');
    }
    public function template_send_tq($title,$name,$open_id,$url) {
        require_once 'today/Wechat_tq.php';
        $wechat = new \Wechat_tq( $this );
        $data=array(
            'first'=>array('value'=>urlencode("有新的咨询问题了。"),'color'=>"#36648B"),
            'keyword1'=>array('value'=>urlencode($name),'color'=>'#36648B'),
            'keyword2'=>array('value'=>urlencode($title),'color'=>'#36648B'),
            'remark'=>array('value'=>urlencode('点击进入咨询页面'),'color'=>'#FF3030'),
        );
        $wechat->templateForward($open_id,$url,$data);
    }
    public function template_send_zj($title,$open_id,$url) {
        require_once 'today/Wechat_zj.php';
        $wechat = new \Wechat_zj( $this );
        $time = date('Y-m-d H:i:s',time());
        $data=array(
            'first'=>array('value'=>urlencode("有新的咨询问题了。"),'color'=>"#36648B"),
            'keyword1'=>array('value'=>urlencode($title),'color'=>'#36648B'),
            'keyword2'=>array('value'=>urlencode($time),'color'=>'#36648B'),
            'remark'=>array('value'=>urlencode('点击进入咨询页面'),'color'=>'#FF3030'),
        );
        $wechat->templateForward($open_id,$url,$data);
    }
}