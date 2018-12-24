<?php
namespace Portal\Controller;

require_once 'today/config.php';
require_once 'today/RongCloud/rongcloud.php';
require_once 'simplewind/Core/Library/Think/Upload/Driver/Qiniu.class.php';
class RongController extends CheckController {

    private $common_user_model;
    private $rong_token_model;
    private $common_chattime_model;
    private $common_messages_model;
    private $common_evaluate_model;
    private $common_record_model;
    private $common_chat_model;

    public function _initialize() {
        parent::_initialize();
        $this->common_user_model = D( 'Common_user' );
        $this->rong_token_model = D( 'Rong_token' );
        $this->common_chattime_model = D( 'Common_chattime' );
        $this->common_messages_model = D( 'Common_messages' );
        $this->common_evaluate_model = D( 'Common_evaluate' );
        $this->common_record_model = D( 'Common_record' );
        $this->common_chat_model = D( 'Common_chat' );
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
    public function index(){
        $this->display('../Rongcloud/demo/user1/index');
    }
    public function get_data(){
        $sendid= session('send_id');
        $sendUser = $this->common_user_model->find($sendid);
        if (!empty($sendUser['type'])){
            $sendUser['name'] = '铁樵专家';
            $sendUser['photo'] = '../upload_img/head_tq/doctor.png';
        }
        $token = $this->get_token();
        $data = array();
        $data['appkey'] = RY_KEY;
        $data['token'] = $token;
        $data['sendUser'] = $sendUser;
        $this->ajaxReturn($data);
    }
    public function getdata_kefu(){
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
        $this->ajaxReturn($user);
    }
    public function get_user() {
        $id = $_GET['userId'];
        $patient = $this->common_user_model->find($id);
        if (!empty($patient['type'])){
            $patient['name'] = '铁樵专家';
            $patient['photo'] = '../upload_img/head_tq/doctor.png';
        }
        $this->ajaxReturn($patient);
    }
    public function check_chat() {
        $id=session('login_id');
        $msg_id = session('msg_id');
        $type = session('type');
        $user = $this->common_user_model->find($id);
        $msg_info = $this->common_messages_model->find($msg_id);
        if ($msg_info['status'] != 2){
            //暂时使用
            $where = array();
            //接收人
            $sendId = $_GET['userId'];
            $sendUser = $this->common_user_model->find($sendId);
            if ($type == 0){
                $where['d_id'] = array('eq',$_GET['userId']);
                $where['p_id'] = array('eq',session('login_id'));
            }else{
                $where['p_id'] = array('eq',$_GET['userId']);
                $where['d_id'] = array('eq',session('login_id'));
            }
            $chat = $this->common_chattime_model->where($where)->order('chat_time desc')->find();
            $time = strtotime(date('Y-m-d H:i:s',time()));
            if (empty($chat['chat_end_time'])){
                $data = array();
                $data['chat_end_time'] = date('Y-m-d H:i:s',time());
                $this->common_chattime_model->where(array('id' => $chat['id']))->save($data);
                $this->template_send($user,$sendUser,'http://tieqiao.zzzpsj.com/index.php?g=portal&m=messages&a=detail&id='.$msg_id);
            }else{
                $chat_end_time = strtotime($chat['chat_end_time']);
                $timediff = $time-$chat_end_time;
                $remain = $timediff%86400;
                $remain = $remain%3600;
                $mins = intval($remain/60);
                if ($mins>5){
                    $this->template_send($user,$sendUser,'http://tieqiao.zzzpsj.com/index.php?g=portal&m=messages&a=detail&id='.$msg_id);
                }
                $data = array();
                $data['chat_end_time'] = date('Y-m-d H:i:s',time());
                $this->common_chattime_model->where(array('id' => $chat['id']))->save($data);
            }
            $this->ajaxReturn(1);
        }else{
            if ($type == 3){
                $this->ajaxReturn(1);
            }else{
                $this->ajaxReturn(0);
            }
        }
        /*if ($user['type'] == 0){
            $where['doctor_id'] = array('eq',$_GET['userId']);
            $where['user_id'] = array('eq',$id);
        }else{
            $where['user_id'] = array('eq',$_GET['userId']);
            $where['doctor_id'] = array('eq',$id);
        }
        $where['status'] = array('eq',1);
        $where['del_flg'] = array('eq',0);
        $msg_info = $this->common_messages_model->where($where)->find();
        if (empty($msg_info['id'])){
            $this->ajaxReturn(0);
        } else {
            $where = array();
            //接收人
            $sendId = $_GET['userId'];
            $sendUser = $this->common_user_model->find($sendId);
            if ($user['type'] == 0){
                $where['d_id'] = array('eq',$_GET['userId']);
                $where['p_id'] = array('eq',session('login_id'));
            }else{
                $where['p_id'] = array('eq',$_GET['userId']);
                $where['d_id'] = array('eq',session('login_id'));
            }
            $chat = $this->common_chattime_model->where($where)->order('chat_time desc')->find();
            $time = strtotime(date('Y-m-d H:i:s',time()));
            if (empty($chat['chat_end_time'])){
                $data = array();
                $data['chat_end_time'] = date('Y-m-d H:i:s',time());
                $this->common_chattime_model->where(array('id' => $chat['id']))->save($data);
                $this->template_send($user,$sendUser,'http://tieqiao.zzzpsj.com/index.php?g=portal&m=rong&a=index');
            }else{
                $chat_end_time = strtotime($chat['chat_end_time']);
                $timediff = $time-$chat_end_time;
                $remain = $timediff%86400;
                $remain = $remain%3600;
                $mins = intval($remain/60);
                if ($mins>5){
                    $this->template_send($user,$sendUser,'http://tieqiao.zzzpsj.com/index.php?g=portal&m=rong&a=index');
                }
                $data = array();
                $data['chat_end_time'] = date('Y-m-d H:i:s',time());
                $this->common_chattime_model->where(array('id' => $chat['id']))->save($data);
            }
            $this->ajaxReturn(1);
        }*/
    }
    public function end_chat() {
        $id = (int)session('login_id');
        $msg_id = session('msg_id');
        $msg_info = $this->common_messages_model->find($msg_id);
        if ($msg_info['status'] == 2){
            $this->ajaxReturn(2);
        }else{
            //关闭咨询
            $msgInfo = array();
            $msgInfo['status'] = 2;
            $msgInfo['end_time'] = date('Y-m-d H:i:s',time());
            $this->common_messages_model->where(array('id' => $msg_id))->save($msgInfo);
            //添加总结
            $data = array();
            $data['msg_id'] = $msg_id;
            $data['doctor_id'] = $id;
            $data['content'] = $_GET['msg'];
            $data['doctor_time'] = date('Y-m-d H:i:s',time());
            $result = $this->common_evaluate_model->add($data);
            //推送通知
            require_once 'today/Wechat_tq.php';
            $wechat = new \Wechat_tq( $this );
            $patient_user = $this->common_user_model->find($_GET['userId']);
            $doctor_user = $this->common_user_model->find($id);
            if ($doctor_user['status'] == 0){
                $wechat->customSendImg($doctor_user['open_id'],'http://tieqiao.zzzpsj.com/index.php?g=portal&m=messages&a=detail&id='.$msg_id,$patient_user['name'].'发起的咨询已被您关闭','点击这里查看总结');
                $wechat->customSendImg($patient_user['open_id'],'http://tieqiao.zzzpsj.com/index.php?g=portal&m=messages&a=detail&id='.$msg_id,'本次咨询已结束，期待下次为您服务','请为本次服务做出评价');
            }else{
                require_once 'today/Wechat_zj.php';
                $wechat_zj = new \Wechat_zj( $this );
                $wechat_zj->customSendImg($doctor_user['open_id'],'http://tieqiao.zzzpsj.com/index.php?g=portal&m=messages&a=detail&id='.$msg_id,$patient_user['name'].'发起的咨询已被您关闭','点击这里查看总结');
            }
            $this->ajaxReturn(1);
        }
       /* $where = array();
        $where['user_id'] = array('eq',$_GET['userId']);
        $where['doctor_id'] = array('eq',$id);
        $where['status'] = array('eq',1);
        $where['del_flg'] = array('eq',0);
        $msg_info = $this->common_messages_model->where($where)->find();
        if (empty($msg_info['id'])){
            $this->ajaxReturn(2);
        } else {
            //关闭咨询
            $msgInfo = array();
            $msgInfo['status'] = 2;
            $msgInfo['end_time'] = date('Y-m-d H:i:s',time());
            $this->common_messages_model->where(array('id' => $msg_info['id']))->save($msgInfo);

            //添加总结
            $data = array();
            $data['msg_id'] = $msg_info['id'];
            $data['doctor_id'] = $id;
            $data['content'] = $_GET['msg'];
            $data['doctor_time'] = date('Y-m-d H:i:s',time());
            $result = $this->common_evaluate_model->add($data);

            require_once 'today/Wechat_tq.php';
            $wechat = new \Wechat_tq( $this );
            $patient_user = $this->common_user_model->find($_GET['userId']);
            $doctor_user = $this->common_user_model->find($id);
            if ($doctor_user['status'] == 0){
                $wechat->customSendImg($doctor_user['open_id'],'http://tieqiao.zzzpsj.com/index.php?g=portal&m=messages&a=evaluate&msg_id='.$result,$patient_user['name'].'发起的咨询已被您关闭','点击这里查看总结');
                $wechat->customSendImg($patient_user['open_id'],'http://tieqiao.zzzpsj.com/index.php?g=portal&m=messages&a=evaluate&msg_id='.$result,'本次咨询已结束，期待下次为您服务','请为本次服务做出评价');
            }else{
                require_once 'today/Wechat_zj.php';
                $wechat_zj = new \Wechat_zj( $this );
                $wechat_zj->customSendImg($doctor_user['open_id'],'http://tieqiao.zzzpsj.com/index.php?g=portal&m=messages&a=evaluate&msg_id='.$result,$patient_user['name'].'发起的咨询已被您关闭','点击这里查看总结');
            }
            $this->ajaxReturn(1);
        }*/
    }
    public function checkUser() {
        $id = session('login_id');
        $user = $this->common_user_model->field('type')->find($id);
        $this->ajaxReturn($user);
    }
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
    public function uploadVoice() {
        $media_id = $_POST['media_id'];//前端返回的上传后的媒体id
        /*$qiniuStorage = new \Think\Upload\Driver\Qiniu\QiniuStorage();
        $qiniu = new \Think\Upload\Driver\Qiniu();*/
        require_once 'today/Wechat_tq.php';
        $wechat = new \Wechat_tq( $this );
        $result = $wechat->downloadWeixinFile($media_id,'');
        $prefix=explode("/", $result['header']['content_type']);
        $filename=$this->salt('5').$this->msectime().'.'.$prefix[1];
        /*\Think\Log::write('语音文件名:'.$filename,'WARN');
        file_put_contents($filename, $result['body']);
          $str = file_get_contents('./test.png');
          echo base64_encode($str);
        */
        $data['vic'] = base64_encode($result['body']);
        $this->ajaxReturn($data);
    }
    public function uploadImg() {
        $media_id = $_POST['media_id'];//前端返回的上传后的媒体id
        /*$qiniuStorage = new \Think\Upload\Driver\Qiniu\QiniuStorage();
        $qiniu = new \Think\Upload\Driver\Qiniu();*/
        require_once 'today/Wechat_tq.php';
        $wechat = new \Wechat_tq( $this );
        $result = $wechat->downloadWeixinFile($media_id,'');
        $filename=$this->salt('5').$this->msectime().'.jpg';
        \Think\Log::write('图片文件名:'.$filename,'WARN');
        file_put_contents('upload_img/'.$filename, $result['body']);
        /*  $str = file_get_contents('./test.png');
          echo base64_encode($str);
        */
        $data['img'] = base64_encode($result['body']);
        $data['img_name'] = $filename;
        $data['img_url'] = 'http://www.jkwdr.cn/upload_img/'.$filename;
        $this->ajaxReturn($data);
    }
    public function template_send($user,$sendUser,$url) {
        //发送模板消息
        require_once 'today/Wechat_tq.php';
        $wechat = new \Wechat_tq( $this );
        if ($user['type'] == 0){
            $data=array(
                'serviceInfo'=>array('value'=>urlencode("您发起的咨询已被接收，请耐心等待医生回复。"),'color'=>"#36648B"),
                'serviceType'=>array('value'=>urlencode('问题咨询'),'color'=>'#36648B'),
                'serviceStatus'=>array('value'=>urlencode('处理中'),'color'=>'#36648B'),
                'time'=>array('value'=>urlencode(date('Y-m-d H:i:s',time())),'color'=>'#36648B'),
                'remark'=>array('value'=>urlencode('点击进入咨询页面'),'color'=>'#36648B'),
            );
            $wechat->templateSend($user['open_id'],$url,$data);
            if ($user['status'] == 0){
                $data=array(
                    'serviceInfo'=>array('value'=>urlencode("您好，有患者向您提出咨询，请及时应答。"),'color'=>"#36648B"),
                    'serviceType'=>array('value'=>urlencode('问题咨询'),'color'=>'#36648B'),
                    'serviceStatus'=>array('value'=>urlencode('处理中'),'color'=>'#36648B'),
                    'time'=>array('value'=>urlencode(date('Y-m-d H:i:s',time())),'color'=>'#36648B'),
                    'remark'=>array('value'=>urlencode('点击进入咨询页面'),'color'=>'#36648B'),
                );
                $wechat->templateSend($sendUser['open_id'],$url,$data);
            }else{
                require_once 'today/Wechat_zj.php';
                $wechat_zj = new \Wechat_zj( $this );
                $where = array();
                $where['user_id'] = array('eq',$user['id']);
                $where['doctor_id'] = array('eq',$sendUser['id']);
                $where['status'] = array('eq',1);
                $where['del_flg'] = array('eq',0);
                $msg_info = $this->common_messages_model->where($where)->select();
                $data=array(
                    'first'=>array('value'=>urlencode("您好，有患者向您提出咨询，请及时应答。"),'color'=>"#36648B"),
                    'keyword1'=>array('value'=>urlencode($msg_info[0]['title']),'color'=>'#36648B'),
                    'keyword2'=>array('value'=>urlencode(date('Y-m-d H:i:s',time())),'color'=>'#36648B'),
                    'remark'=>array('value'=>urlencode('点击进入咨询页面'),'color'=>'#36648B'),
                );
                $wechat_zj->templateSend($sendUser['open_id'],$url,$data);
            }

        }else{
            $data=array(
                'serviceInfo'=>array('value'=>urlencode("您好，您的问题医生已经回复，请及时查看。"),'color'=>"#36648B"),
                'serviceType'=>array('value'=>urlencode('问题咨询'),'color'=>'#36648B'),
                'serviceStatus'=>array('value'=>urlencode('处理中'),'color'=>'#36648B'),
                'time'=>array('value'=>urlencode(date('Y-m-d H:i:s',time())),'color'=>'#36648B'),
                'remark'=>array('value'=>urlencode('点击这里查看回复'),'color'=>'#36648B'),
            );
            $wechat->templateSend($sendUser['open_id'],$url,$data);
        }
    }
    public function set_time(){
        $id=session('login_id');
        $type=(int)session('type');
        $msg_id = $_GET['msgId'];
        $msg_info = $this->common_messages_model->find($msg_id);
        if ($msg_info['status'] == 0){
            //记录聊天时间
            $data = array();
            $data['chat_time'] = date('Y-m-d H:i:s',time());
            $data['d_id'] = session('login_id');
            $data['p_id'] = session('send_id');
            $this->common_chattime_model->add($data);
            //修改咨询状态
            $data_msg = array();
            $data_msg['doctor_id'] = session('login_id');
            $data_msg['status'] = 1;
            $data_msg['start_time'] = date('Y-m-d H:i:s',time());
            $result = $this->common_messages_model->where(array('id' => $msg_id))->save($data_msg);
            if ($result){
                session('msg_id',$msg_id);
                $user = $this->common_user_model->find($msg_info['doctor_id']);
                $sendUser = $this->common_user_model->find($msg_info['user_id']);
                $this->template_send($user,$sendUser,'http://tieqiao.zzzpsj.com/index.php?g=portal&m=messages&a=detail&id='.$msg_id);
                $this->ajaxReturn('0');
            }else{
                $this->ajaxReturn('1');
            }
        }elseif ($type == 3){
            session('msg_id',$msg_id);
            $this->ajaxReturn('0');
        }elseif ($msg_info['doctor_id'] == $id){
            session('msg_id',$msg_id);
            $this->ajaxReturn('0');
        }elseif ($msg_info['user_id'] == $id){
            session('msg_id',$msg_id);
            $this->ajaxReturn('0');
        }else{
            $this->ajaxReturn('2');
        }
    }
    public function chat_history(){
        $login_id = session('login_id');
        $msg_id = session('msg_id');
        $type = session('type');
        $userId = $_GET['userId'];
        $msg = $_GET['msg'];
        $photo = $_GET['photo'];
        $data = array();
        $data['msg_id'] = $msg_id;
        if (empty($type)){
            $data['user_id'] = $login_id;
            $data['doctor_id'] = $userId;
            $data['status'] = 1;
        }else{
            $data['user_id'] = $userId;
            $data['doctor_id'] = $login_id;
            $data['status'] = 2;
        }
        if (empty($msg)){
            $data['photo'] = $photo;
        }else{
            $data['content'] = $msg;
        }
        $data['create_time'] = date('Y-m-d H:i:s',time());
        $result = $this->common_chat_model->add($data);
        if ($result){
            $this->ajaxReturn('0');
        }else{
            $this->ajaxReturn('1');
        }
    }
    //点击用户头像查看详情
    public function show_msg(){
        $id=session('login_id');
        $user = $this->common_user_model->find($id);
        $where = array();
        if ($user['type'] == 0){
            $where['m.user_id'] = array('eq',$id);
            $where['m.doctor_id'] = array('eq',$_GET['userId']);
            $where['m.status'] = array('eq',1);
            $where['m.del_flg'] = array('eq',0);
        }else{
            $where['m.user_id'] = array('eq',$_GET['userId']);
            $where['m.doctor_id'] = array('eq',$id);
            $where['m.status'] = array('eq',1);
            $where['m.del_flg'] = array('eq',0);
        }
        $msg_Info = $this->common_messages_model->alias('m')->field('m.*,u.name as name,u.photo as photo,u.sex as sex,u.age as age')->join('__COMMON_USER__ u ON m.user_id=u.id')->where($where)->find();

        $where_r = array();
        $where_r['message_id'] = array('eq',$msg_Info['id']);
        $where_r['user_id'] = array('eq',$msg_Info['user_id']);
        $list = $this->common_record_model->where($where_r)->select();
        session('send_id',$msg_Info['user_id']);
        $this->assign( 'list', $list );
        $this->assign( 'msg', $msg_Info );
        $this->display('../Tieqiao/customer_detail');
    }
        /**
     * 生成毫秒级时间戳
     */
    public function msectime(){
        list($msec, $sec) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
    }

    /**
     * 随机取出字符串
     * @param  int $strlen 字符串位数
     * @return string
     */
    public function salt($strlen){
        $str  = "abcdefghkmnprstuvwxyzABCDEFGHKMNPRSTUVWXYZ23456789";
        $salt = '';
        $_len = strlen($str)-1;
        for ($i = 0; $i < $strlen; $i++) {
            $salt .= $str[mt_rand(0,$_len)];
        }
        return $salt;
    }
}


