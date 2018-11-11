<?php
namespace Portal\Controller;
use Common\Controller\HomebaseController;

require_once 'today/config.php';
require_once 'today/RongCloud/rongcloud.php';
require_once 'simplewind/Core/Library/Think/Upload/Driver/Qiniu.class.php';
class RongController extends HomebaseController {

    private $common_user_model;
    private $rong_token_model;
    private $common_chattime_model;

    public function _initialize() {
        parent::_initialize();
        $this->common_user_model = D( 'Common_user' );
        $this->rong_token_model = D( 'Rong_token' );
        $this->common_chattime_model = D( 'Common_chattime' );
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
        require_once 'today/config.php';
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
    public function get_user() {
        $id = $_GET['userId'];
        $patient = $this->common_user_model->find($id);
        $this->ajaxReturn($patient);
    }
    public function check_chat() {
        $where = array();
        //发送人
        $id = session('login_id');
        $user = $this->common_user_model->find($id);
        //接收人
        $sendId = $_GET['userId'];
        $sendUser = $this->common_user_model->find($sendId);
        if ($user['status'] == 0){
            $where['d_id'] = array('eq',$_GET['userId']);
            $where['p_id'] = array('eq',session('login_id'));
        }else{
            $where['p_id'] = array('eq',$_GET['userId']);
            $where['d_id'] = array('eq',session('login_id'));
        }
        $chat = $this->common_chattime_model->where($where)->order('chat_time desc')->find();
        $t = $chat['chat_time'];
        $chat_time = strtotime("$t+1day");//有效时间24小时
        $time = strtotime(date('Y-m-d H:i:s',time()));
        if ($time > $chat_time){
            $this->ajaxReturn(0);
        }else{
            if (empty($chat['chat_end_time'])){
                $data = array();
                $data['chat_end_time'] = date('Y-m-d H:i:s',time());
                $this->common_chattime_model->where(array('id' => $chat['id']))->save($data);
                $this->template_send($user,$sendUser,'http://www.jkwdr.cn/index.php?g=portal&m=rong&a=index');
            }else{
                $chat_end_time = strtotime($chat['chat_end_time']);
                $timediff = $time-$chat_end_time;
                $remain = $timediff%86400;
                $remain = $remain%3600;
                $mins = intval($remain/60);
                if ($mins>5){
                    $this->template_send($user,$sendUser,'http://www.jkwdr.cn/index.php?g=portal&m=rong&a=index');
                }
                $data = array();
                $data['chat_end_time'] = date('Y-m-d H:i:s',time());
                $this->common_chattime_model->where(array('id' => $chat['id']))->save($data);
            }
            $this->ajaxReturn(1);
        }
    }
    public function end_chat() {
        $t = date('Y-m-d H:i:s',time());
        $data = array();
        $data['chat_time'] = date('Y-m-d H:i:s',strtotime("$t-1day"));
        $where = array();
        $where['p_id'] = array('eq',$_GET['userId']);
        $where['d_id'] = array('eq',session('login_id'));
        $this->common_chattime_model->where($where)->save($data);

        require_once 'today/Wechat_tq.php';
        $wechat = new \Wechat_tq( $this );
        $doctor_user = $this->common_user_model->find(session('login_id'));
        $patient_user = $this->common_user_model->find($_GET['userId']);
        $wechat->customSendImg($doctor_user['open_id'],'http://www.jkwdr.cn/','咨询关闭提醒',$patient_user['name'].'发起的咨询已被您关闭，欢迎使用健康微达人');
        $wechat->customSendImg($patient_user['open_id'],'http://www.jkwdr.cn/','咨询关闭提醒','您的咨询已被'.$doctor_user['name'].'关闭，欢迎使用健康微达人');
        $this->ajaxReturn(1);
    }
    public function checkUser() {
        $id = session('login_id');
        $user = $this->common_user_model->field('status')->find($id);
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
        $this->ajaxReturn(' '.$upToken);
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
        \Think\Log::write('语音文件名:'.$filename,'WARN');
        /*file_put_contents($filename, $result['body']);
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
        //向患者发送模板消息
        require_once 'today/Wechat_tq.php';
        $wechat = new \Wechat_tq( $this );
        if ($user['status'] == 0){
            $data=array(
                'first'=>array('value'=>urlencode("您发起的咨询已被接收，请耐心等待医生回复。"),'color'=>"#00CD00"),
                'keyword1'=>array('value'=>urlencode($user['name']),'color'=>'#4876FF'),
                'keyword2'=>array('value'=>urlencode($sendUser['hospital']),'color'=>'#4876FF'),
                'keyword3'=>array('value'=>urlencode($sendUser['office']),'color'=>'#4876FF'),
                'keyword4'=>array('value'=>urlencode($sendUser['name']),'color'=>'#4876FF'),
                'keyword5'=>array('value'=>urlencode(date('Y-m-d H:i:s',time())),'color'=>'#4876FF'),
                'remark'=>array('value'=>urlencode('点击进入咨询页面'),'color'=>'#FF0000'),
            );
            $wechat->templateSend($user['open_id'],$user['status'],$url,$data);
            if ($user['sex'] == 1){
                $sex = '男';
            }elseif ($user['sex'] == 2){
                $sex = '女';
            }else{
                $sex = '保密';
            }
            $data=array(
                'first'=>array('value'=>urlencode("您好，有患者向您提出咨询，请及时应答。"),'color'=>"#00CD00"),
                'keyword1'=>array('value'=>urlencode($user['name']),'color'=>'#4876FF'),
                'keyword2'=>array('value'=>urlencode($sex),'color'=>'#4876FF'),
                'remark'=>array('value'=>urlencode('点击这里回复患者'),'color'=>'#FF0000'),
            );
            $wechat->templateSend($sendUser['open_id'],$sendUser['status'],$url,$data);
        }else{
            $data=array(
                'first'=>array('value'=>urlencode("您好，您的问题医生已经回复，请及时查看。"),'color'=>"#00CD00"),
                'keyword1'=>array('value'=>urlencode($sendUser['name']),'color'=>'#4876FF'),
                'keyword2'=>array('value'=>urlencode($user['hospital']),'color'=>'#4876FF'),
                'keyword3'=>array('value'=>urlencode($user['office']),'color'=>'#4876FF'),
                'keyword4'=>array('value'=>urlencode($user['name']),'color'=>'#4876FF'),
                'keyword5'=>array('value'=>urlencode(date('Y-m-d H:i:s',time())),'color'=>'#4876FF'),
                'remark'=>array('value'=>urlencode('点击这里查看回复'),'color'=>'#FF0000'),
            );
            $wechat->templateSend($sendUser['open_id'],$sendUser['status'],$url,$data);
        }
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


