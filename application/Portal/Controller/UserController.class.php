<?php
/**
 * 用户端
 */
namespace Portal\Controller;

class UserController extends CheckController {

    private $common_user_model;
    private $common_tag_model;
    private $common_office_model;
    private $common_hospital_model;
    private $common_health_model;
    private $common_card_model;
    private $common_messages_model;
    private $common_position_model;
    private $common_record_model;
    private $common_determine_model;
    private $common_result_model;
    private $common_result_rel_model;
    private $common_operation_model;

	function _initialize() {
		parent::_initialize();

        $this->common_user_model = D( 'Common_user' );
        $this->common_tag_model = D( 'Common_tag' );
        $this->common_office_model = D( 'Common_office' );
        $this->common_hospital_model = D( 'Common_hospital' );
        $this->common_health_model = D( 'Common_health' );
        $this->common_card_model = D( 'Common_card' );
        $this->common_messages_model = D( 'Common_messages' );
        $this->common_position_model = D( 'Common_position' );
        $this->common_record_model = D( 'Common_record' );
        $this->common_determine_model = D( 'Common_determine' );
        $this->common_result_model = D( 'Common_result' );
        $this->common_result_rel_model = D( 'Common_result_rel' );
        $this->common_operation_model = D( 'Common_operation' );
	}
    //用户身份判断
    public function user_info() {
        $id = (int)session('login_id');
        $user = $this->common_user_model->find($id);
        if ($user['type'] == 0){
            R('User/info_patient');
        }else{
            R('User/info_doctor');
        }
    }
    //患者个人中心
    public function info_patient() {
	    $flg = session('flg');
        $id = (int)session('login_id');
        if ( IS_POST && empty($flg)) {
            $healthy = $_POST['healthy'];
            if (empty($healthy)){
                $_POST['healthy'] = 0;
            }
            $_POST['update_time'] = date('Y-m-d H:i:s',time());
            $result = $this->common_user_model->where(array('id' => $id))->save($_POST);
            if ($result){
                session('flg','redt');
                R('User/info_patient');
            }else{
                $this->ajaxReturn(0);
            }
        }else{
            session('flg',null);
            $patient = $this->common_user_model->find($id);

            $where_h = array();
            $where_h['up_id'] = array('eq',0);
            $where_h['del_flg'] = array('eq',0);
            $list = $this->common_health_model->where($where_h)->select();

            $where_s = array();
            $where_s['up_id'] = array('eq',$patient['health']);
            $where_s['del_flg'] = array('eq',0);
            $lists = $this->common_health_model->where($where_s)->select();

            $this->assign( 'list', $list );
            $this->assign( 'lists', $lists );
            $this->assign( 'patient', $patient );
            $this->display('../Tieqiao/info_patient');
        }
    }
    //医生个人中心
    public function info_doctor() {
        $flg = session('flg');
        $id = (int)session('login_id');
        if ( IS_POST && empty($flg)) {
            $_POST['update_time'] = date('Y-m-d H:i:s',time());
            $result = $this->common_user_model->where(array('id' => $id))->save($_POST);
            if ($result){
                session('flg','redt');
                R('User/info_doctor');
            }else{
                $this->ajaxReturn(0);
            }
        }else{
            session('flg',null);
            $doctor = $this->common_user_model->where(array('id' => $id))->find();
            $where = array();
            $where['del_flg'] = array('eq',0);
            $list = $this->common_office_model->where($where)->select();
            $tlist = $this->common_tag_model->where($where)->select();
            $plist = $this->common_position_model->where($where)->select();
            $this->assign( 'list', $list );
            $this->assign( 'tlist', $tlist );
            $this->assign( 'plist', $plist );
            $this->assign( 'doctor', $doctor );
            $this->display('../Tieqiao/info_doctor');
        }
    }
    //科室展示
    public function office_preview() {
        $where = array();
        $where['del_flg'] = array('eq',0);
        $list = $this->common_office_model->where($where)->select();
        $this->assign( 'list', $list );
        $this->display('../Tieqiao/office_preview');
    }
    //医生列表
    public function doctor_list() {
        $office_id = $_GET['office_id'];
        $ofc = $this->common_office_model->find($office_id);
        $where = array();
        $where['u.del_flg'] = array('eq',0);
        $where['u.office'] = array('eq',$office_id);
        $where['u.type'] = array('eq',1);
        $list = $this->common_user_model->alias('u')->field('u.*,p.name as position_n')->join('__COMMON_POSITION__ p ON u.position=p.id','left')->where($where)->select();
        $this->assign( 'office_n', $ofc['name'] );
        $this->assign( 'list', $list );
        $this->display('../Tieqiao/doctor_list');
    }
    //医生详情
    public function doctor_detail() {
        $user_id = $_GET['user_id'];
        $doctor = $this->common_user_model->alias('u')->field('u.*,o.name as office_n,t.name as tag_n')->join('__COMMON_OFFICE__ o ON u.office=o.id','left')->join('__COMMON_TAG__ t ON u.tag=t.id','left')->where(array('u.id' => $user_id))->find();
        $this->assign( 'doctor', $doctor );
        $this->display('../Tieqiao/doctor_detail');
    }
    //咨询问诊
    public function question() {
        $id = (int)session('login_id');
        $user = $this->common_user_model->find($id);
        if ($user['type'] == 0){
            R('User/question_p');
        }else{
            R('User/question_d');
        }
    }
    function question_d(){
        $id = (int)session('login_id');
        $where = array();
        $where['m.type'] = array('eq',2);
        $where['m.status'] = array('neq',2);
        $where['m.del_flg'] = array('eq',0);
        $where['p.doctor_id'] = array('eq',$id);
        $forward = $this->common_messages_model->alias('m')->field('m.*,u.name as name,u.photo as photo')->join('__COMMON_USER__ u ON m.user_id=u.id')->join('__COMMON_OPERATION__ p ON m.id=p.msg_id')->where($where)->order("m.status asc,m.operation_time desc")->select();
        $where = array();
        $where['m.type'] = array('eq',1);
        $where['m.status'] = array('neq',2);
        $where['m.del_flg'] = array('eq',0);
        $messages = $this->common_messages_model->alias('m')->field('m.*,u.name as name,u.photo as photo')->join('__COMMON_USER__ u ON m.user_id=u.id')->where($where)->order("m.status asc,m.operation_time desc")->select();
        $this->assign( 'forward', $forward );
        $this->assign( 'messages', $messages );
        $this->display('../Tieqiao/question_d');
    }
    function question_p(){
        if ( IS_POST ) {
            $id = (int)session('login_id');
            $_POST['user_id'] = $id;
            $_POST['create_time'] = date('Y-m-d H:i:s',time());
            $result = $this->common_messages_model->add($_POST);
            if (!$result){
                $this->formError[] = '咨询失败';
            }
            if ($_POST['img'] != null && $_POST['img'] != ''){
                $st = $this->uploadImg($_POST['img'],$result);
                if (!$st){
                    $this->formError[] = '上传病例图片失败';
                }
            }
            $where = array();
            $where['del_flg'] = array('eq',0);
            $where['status'] = array('eq',0);
            $where['type'] = array('eq',3);
            $where['flg'] = array('eq',1);
            $list = $this->common_user_model->field('open_id')->where($where)->select();
            $user = $this->common_user_model->field('name')->find($id);
            foreach($list as $value){
                $url = 'http://tieqiao.zzzpsj.com/index.php?g=portal&m=messages&a=advice';
                $this->template_send_tq($_POST['content'],$user['name'],$value['open_id'],$url);
            }
            $this->formError[] = '咨询成功，请耐心等待医生回复';
            $this->assign( 'formError', $this->formError );
            $this->display('../Tieqiao/question');
        } else {
            $this->display('../Tieqiao/question');
        }
    }
    function self_test(){
	    if ( IS_POST ){
            $type = $_POST['type'];
            $types = $_POST['types'];
            $result = $_POST['result'];
            if (empty($result)){
                $result = array();
            }else{
                $result = explode(",", $result);
            }
            if ($types == 0){
                $rlt = array();
                $lt = count($result) - 1;
                $pinghe = $result[$lt];
                $data = array();
                if ($pinghe>=60){
                    $flg = true;
                    for ($i = 0;$i<$lt;$i++){
                        if ($result[$i]>30){
                            $flg = false;
                        }
                    }
                    if($flg){
                        $data['type'] = 1;
                        $data['status'] = 1;
                        $data['result'] = 1;
                        $rlt[] = $this->common_result_rel_model->add($data);
                    }else{
                        for ($i = 0;$i<$lt;$i++){
                            if ($result[$i]>40){
                                $flg = false;
                            }
                        }
                    }
                    if($flg){
                        $data['type'] = 9;
                        $data['status'] = 2;
                        $data['result'] = 1;
                        $rlt[] = $this->common_result_rel_model->add($data);
                    }
                }
                for ($i = 0;$i<$lt;$i++){
                    if ($result[$i]>=40){
                        $data['type'] = $i+1;
                        $data['status'] = 1;
                        $data['result'] = 1;
                        $rlt[] = $this->common_result_rel_model->add($data);
                    }elseif ($result[$i]>=30 && $result[$i]<=39){
                        $data['type'] = $i+1;
                        $data['status'] = 3;
                        $data['result'] = 1;
                        $rlt[] = $this->common_result_rel_model->add($data);
                    }
                }
                $rlt = implode(",", $rlt);
                $this->redirect('User/self_test_result', array('result' => $rlt));
            }else{
                if ($type>$types){
                    $lt = count($result) - 1;
                    unset($result[$lt]);
                }else{
                    $where = array();
                    $where['type'] = $type;
                    $list = $this->common_determine_model->where($where)->select();
                    $num = count($list);
                    $vl = 0;
                    foreach($list as $data){
                        $id = $data['id'];
                        $vl += (int)$_POST[$id];
                    }
                    $vl = $vl - $num;
                    $num = $num * 4;
                    $result[] = floor($vl/$num*100);
                }
                $result = implode(",", $result);
                $this->redirect('User/self_test', array('result' => $result,'type' => $types));
            }
        }else{
            $type = $_GET['type'];
            $result = $_GET['result'];
            if (empty($type)){
                $type = 1;
            }
            $where = array();
            $where['type'] = $type;
            $list = $this->common_determine_model->where($where)->select();
            $this->assign( 'type', $type );
            $this->assign( 'result', $result );
            $this->assign( 'list', $list );
            $this->display('../Tieqiao/self_test');
        }
    }
    function self_test_result(){
        $result = $_POST['result'];
        $where = array();
        $where['id'] = array('in',$result);
        $list = $this->common_result_model->select();
        $this->assign( 'list', $list );
        $this->display('../Tieqiao/self_test_result');
    }
    //上传图片
    public function uploadImg($img,$msg_id){
        $id = (int)session('login_id');
        require_once 'today/Wechat_tq.php';
        $wechat = new \Wechat_tq( $this );
        $cm=new CommonMethodController();
        $flg = true;
        foreach($img as $media_id){
            $img = $wechat->downloadWeixinFile($media_id,'');
            $filename='upload_img/record/'.$cm->salt('5').$cm->msectime().'.jpg';
            file_put_contents($filename, $img['body']);
            $data['message_id'] = $msg_id;
            $data['user_id'] = $id;
            $data['photo'] = '../'.$filename;
            $data['create_time'] = date('Y-m-d H:i:s',time());
            $result = $this->common_record_model->add($data);
            if (!$result){
                $flg = false;
            }
        }
        return $flg;
    }
    public function template_send_tq($title,$name,$open_id,$url) {
        require_once 'today/Wechat_tq.php';
        $wechat = new \Wechat_tq( $this );
        $data=array(
            'first'=>array('value'=>urlencode("有新的咨询问题了。"),'color'=>"#36648B"),
            'keyword1'=>array('value'=>urlencode($name),'color'=>'#36648B'),
            'keyword2'=>array('value'=>urlencode($title),'color'=>'#36648B'),
            'remark'=>array('value'=>urlencode('点击进入咨询页面'),'color'=>'#36648B'),
        );
        $wechat->templateForward($open_id,$url,$data);
    }
    public function template_send_zj($msg_info,$sendUser,$url) {
        require_once 'today/Wechat_zj.php';
        $wechat = new \Wechat_zj( $this );
        $time = date('Y-m-d H:i:s',time());
        $data=array(
            'first'=>array('value'=>urlencode("有新的咨询问题了。"),'color'=>"#36648B"),
            'keyword1'=>array('value'=>urlencode($msg_info['title']),'color'=>'#36648B'),
            'keyword2'=>array('value'=>urlencode($time),'color'=>'#36648B'),
            'remark'=>array('value'=>urlencode('点击进入咨询页面'),'color'=>'#36648B'),
        );
        $wechat->templateForward($sendUser['open_id'],$url,$data);
    }
    public function result(){
        $ck = $_GET['ck'];
        if ($ck == 1){
            $this->assign( 'url', 'index.php?g=portal&m=user&a=question' );
            $this->display('../Public/return');
        }else{
            $this->assign( 'url', 'index.php?g=portal&m=user&a=question' );
            $this->display('../Public/return');
        }
    }
    public function about(){
        $this->display('../Tieqiao/about');
    }
}