<?php
/**
 * 患者端
 */
namespace Portal\Controller;

class PatientController extends CheckController {

    private $common_dp_model;
    private $common_ad_model;
    private $common_user_model;
    private $common_order_model;
    private $common_chattime_model;
    private $common_remind_model;
    private $patient_record_model;
    private $china_model;
    private $formError = array();
	
	function _initialize() {
		parent::_initialize();

        $this->common_dp_model = D( 'Common_dp' );
        $this->common_ad_model = D( 'Common_ad' );
        $this->common_user_model = D( 'Common_user' );
        $this->common_order_model = D( 'Common_order' );
        $this->common_remind_model = D( 'Common_remind' );
        $this->common_chattime_model = D( 'Common_chattime' );
        $this->patient_record_model = D( 'Patient_record' );
        $this->china_model = D( 'China' );
	}
    //患者端首页
    public function index() {
        $time = date("H:i:s");
        //轮播图
        $ad_where = array();
        $ad_where['recommend'] = array('eq',1);
        $ad_where['status'] = array('eq',0);
        $ad_where['del_flg'] = array('eq',0);
        $ad_list = $this->common_ad_model->where($ad_where)->select();
        //主推医生
        $mp_where = array();
        $mp_where['recommend'] = array('eq',2);
        $mp_where['status'] = array('eq',1);
/*        $mp_where['start_time'] = array('elt',$time);
        $mp_where['end_time'] = array('egt',$time);*/
        $mp_where['del_flg'] = array('eq',0);
        $mp_list = $this->common_user_model->where($mp_where)->select();
        //推荐医生
        $rc_where = array();
        $rc_where['recommend'] = array('eq',1);
        $rc_where['status'] = array('eq',1);
/*        $rc_where['start_time'] = array('elt',$time);
        $rc_where['end_time'] = array('egt',$time);*/
        $rc_where['del_flg'] = array('eq',0);
        $rc_list = $this->common_user_model->where($rc_where)->select();
        $this->assign( 'ad_list', $ad_list );
        $this->assign( 'mp_list', $mp_list );
        $this->assign( 'rc_list', $rc_list );
        $this->assign( 'msg_count', session('msg_count') );
        $this->display("/index");
    }
    //医生信息列表
    function doctor_user() {
        $id = (int)session('login_id');
        $open_id = session('open_id');
        $where = array();
        $time = date("H:i:s");
        $user = $this->common_user_model->find($id);
        //我的医生
        $dp_where = array();
        $dp_where['d.patient_id'] = array('eq',$open_id);
        $dp_where['p.del_flg'] = array('eq',0);
        $my_list = $this->common_dp_model->alias('d')->field('distinct d.patient_id,p.*')->join('__COMMON_USER__ p ON d.doctor_id=p.id')->where($dp_where)->order('d.create_time desc')->select();
        $did = array();
        foreach($my_list as $row) {
            $did[] = $row['id'];
        }
        $my_did = implode(',',$did);
        //其他医生
        $where['id'] = array('not in',$my_did);
        $where['province'] = array('eq',$user['province']);
        $where['city'] = array('eq',$user['city']);
        $where['county'] = array('eq',$user['county']);
        $where['status'] = array('eq',1);
        $where['del_flg'] = array('eq',0);
/*        $where['start_time'] = array('elt',$time);
        $where['end_time'] = array('egt',$time);*/
        $list = $this->common_user_model->where($where)->select();
        $this->assign( 'my_list', $my_list );
        $this->assign( 'list', $list );
        $this->assign( 'msg_count', session('msg_count') );
        $this->display('/list');
    }
    //医生信息详情
    function details() {
        $id = intval( I( 'get.id' ) );
        $doctor = $this->common_user_model->find($id);
        $this->assign( 'doctor', $doctor );
        $this->display('/details');
    }
    //付款咨询
    function payment() {
        $did = intval( I( 'get.id' ) );
        $pid = (int)session('login_id');
        $where['d_id'] = array('eq',$did);
        $where['p_id'] = array('eq',$pid);
        $chat = $this->common_chattime_model->where($where)->order('chat_time desc')->find();
        $t = $chat['chat_time'];
        $chat_time = strtotime("$t+1day");//有效时间24小时
        $time = strtotime(date('Y-m-d H:i:s',time()));
        if ($time > $chat_time){
            $doctor = $this->common_user_model->find($did);
            $this->assign( 'doctor', $doctor );
            $this->display('/payment');
        }else{
            $this->assign( 'check', '3' );
            session('send_id',$did);
            $this->display( '../pay_return' );
        }
    }
    //患者信息详情
    function patient_user() {
        $open_id = session('open_id');
        $patient = $this->common_user_model->where(array('open_id' => $open_id))->find();
        if ($patient['province'] != ''){
            $province = $this->china_model->find($patient['province']);
            $patient['provinceName'] = $province['name'];
        }
        if ($patient['city'] != ''){
            $city = $this->china_model->find($patient['city']);
            $patient['cityName'] = $city['name'];
        }
        if ($patient['county'] != ''){
            $county = $this->china_model->find($patient['county']);
            $patient['countyName'] = $county['name'];
        }
        $this->assign( 'patient', $patient );
        $this->assign( 'msg_count', session('msg_count') );
        $this->display('/personal');
    }
    //患者病例图片记录
    function record() {
        $id = (int)session('login_id');
        $where = array();
        $where['user_id'] = array('eq',$id);
        $where['del_flg'] = array('eq',0);
        $list = $this->patient_record_model->where($where)->select();
        $this->assign( 'list', $list );
        $this->assign( 'msg_count', session('msg_count') );
        $this->display('/record');
    }
    //患者信息修改
    function modify() {
        if ( IS_POST ) {
            $id = (int)session('login_id');
            $user = $this->common_user_model->find($id);
            if(!empty($_POST['phone']) && $_POST['phone'] != $user['phone']){
                $where = array();
                $where['phone'] = array('eq',$_POST['phone']);
                $check_user = $this->common_user_model->where($where)->find();
                if (!empty($check_user)) $this->error('该手机号已被使用！');
            }
            $_POST['update_time'] = date('Y-m-d H:i:s',time());
            $result = $this->common_user_model->where(array('id' => $id))->save($_POST);
            if ($result) {
                R('Patient/patient_user');
            } else {
                $this->error('修改失败！');
            }
        } else {
            $data = $_GET['data'];//数据
            $flg = $_GET['flg'];//数据库字段名
            $menu = $_GET['menu'];//页面字段名
            $check = $_GET['check'];//介绍/擅长
            if ($flg == 'area'){
                $where = array();
                $province = $_GET['province'];
                $city = $_GET['city'];
                $county = $_GET['county'];
                if (!empty($province) && !empty($city) && !empty($county)){
                    //省、直辖市
                    $where['pid'] = array('eq',0);
                    $provinceList = $this->china_model->where($where)->order("id asc")->select();
                    //市、区
                    $where['pid'] = array('eq',$province);
                    $cityList = $this->china_model->where($where)->order("id asc")->select();
                    //县、区
                    $where['pid'] = array('eq',$city+1);
                    $list1 = $this->china_model->where($where)->select();
                    $where['pid'] = array('eq',$city);
                    $list2 = $this->china_model->where($where)->select();
                    unset($list2[0]);
                    $countyList = array_merge_recursive($list1,$list2);
                    $this->assign( 'province', $province );
                    $this->assign( 'city', $city );
                    $this->assign( 'county', $county );
                    $this->assign( 'provinceList', $provinceList );
                    $this->assign( 'cityList', $cityList );
                    $this->assign( 'countyList', $countyList );

                }elseif (!empty($province) && !empty($city)){
                    $where['pid'] = array('eq',0);
                    $provinceList = $this->china_model->where($where)->order("id asc")->select();
                    $where['pid'] = array('eq',$province);
                    $cityList = $this->china_model->where($where)->order("id asc")->select();
                    $this->assign( 'province', $province );
                    $this->assign( 'city', $city );
                    $this->assign( 'provinceList', $provinceList );
                    $this->assign( 'cityList', $cityList );
                }else{
                    $where['pid'] = array('eq',0);
                    $provinceList = $this->china_model->where($where)->order("id asc")->select();
                    $where['pid'] = array('eq',110000);
                    $cityList = $this->china_model->where($where)->order("id asc")->select();
                    $this->assign( 'provinceList', $provinceList );
                    $this->assign( 'cityList', $cityList );
                }
            }
            $this->assign( 'data', $data );
            $this->assign( 'flg', $flg );
            $this->assign( 'menu', $menu );
            $this->assign( 'check', $check );
            $this->display('/modify');
        }
    }
    //上传病例图片
    public function upload_image(){
        if ( IS_POST ) {
            $uploadConfig = array(
                'FILE_UPLOAD_TYPE' => sp_is_sae() ? 'Sae' : 'Local',
                'rootPath' => './'.C( 'UPLOADPATH' ),
                'savePath' => 'record/',
                'saveName' => array( 'uniqid', '' ),
                'exts' => array('jpg', 'gif', 'png', 'jpeg'),
                'autoSub' => true
            );
            $upload = new \Think\Upload($uploadConfig);// 实例化上传类
            $info = $upload->upload();// 上传文件
            if(!$info) {// 上传错误提示错误信息
                $this->error($upload->getError());
            }else{// 上传成功
                $data['user_id'] = (int)session('login_id');
                $data['photo_record'] = $info['photo']['url'];
                $data['create_time'] = date('Y-m-d H:i:s',time());
                $this->patient_record_model->add($data);
                R('Patient/record');
            }
        } else {
            $this->display('/upload_show');
        }
    }
    //上传图片
    public function uploadImg(){
        $id = (int)session('login_id');
        $fg = $_POST['fg'];
        require_once 'today/Wechat.php';
        $wechat = new \Wechat( $this );
        if ($fg == 'head'){
            $media_id = $_POST['media_id'];//前端返回的上传后的媒体id
            $img = $wechat->downloadWeixinFile($media_id,'');
            $filename='upload_img/head/'.$this->salt('5').$this->msectime().'.jpg';
            file_put_contents($filename, $img['body']);
            $data['photo'] = '../'.$filename;
            $data['update_time'] = date('Y-m-d H:i:s',time());
            $result = $this->common_user_model->where(array('id' => $id))->save($data);
        }
        if ($fg == 'record'){
            $media_id = $_POST['media_id'];//前端返回的上传后的媒体id
            foreach ($media_id as $img_id){
                \Think\Log::write('图片id:'.$img_id,'WARN');
                $img = $wechat->downloadWeixinFile($img_id,'');
                \Think\Log::write('图片头部:'.$img['header'],'WARN');
                $filename='upload_img/'.$this->salt('5').$this->msectime().'.jpg';
                file_put_contents($filename, $img['body']);
                $data['user_id'] = $id;
                $data['photo_record'] = '../'.$filename;
                $data['create_time'] = date('Y-m-d H:i:s',time());
                $result = $this->patient_record_model->add($data);
            }
        }
        if($result){
            $upimg['check'] = '1';
        }else{
            $upimg['check'] = '0';
        }
        $this->ajaxReturn($upimg);
    }
    //微信支付
    function wxpay() {
        require_once 'today/class.wxpay.php';
        require_once 'today/class.today.php';
        $Wxpay = new \Wxpay;
        $open_id = session('open_id');
        $data['pay_id'] = time();
        $data['payer'] = session('login_id');
        $data['payee'] = $_POST['did'];
        $data['money'] = $_POST['money'];
        $data['pay_name'] = '健康咨询费';
        $result = $this->common_order_model->add($data);
        if ($result){
            if ($_POST['money'] == 0){
                $pay_id = $data['pay_id'];
                $this->redirect("patient/wx_payment_return",array("pay_id"=>$pay_id));
                exit;
            }else{
                $Wxpay->setUrl( 'http://www.jkwdr.cn/index.php?g=portal&m=patient&a=wx_payment_return&pay_id='.$data['pay_id'], 'http://www.jkwdr.cn/wx_payment_notify.php' );
                $prepay = $Wxpay->pay($open_id , \Today\Today::ip()/*'192.168.10.103'*/, $data['pay_id'], (float)$data['money'], 'medical' );
                if ( $prepay['status'] != 200 ) {
                    $this->formError[] = $prepay['msg'];
                } else {
                    $this->assign( 'params', json_encode( $Wxpay->getJsApiPayParams( $prepay['prepay_id'] ) ) );
                    $this->assign( 'pay_id', $data['pay_id'] );
                    $this->display( '../wx-jspay' );
                    exit;
                }
            }
        }
        $this->assign( 'formError', $this->formError );
        $this->display('/payment');
    }
    public function wx_payment_notify() {
        unset( $_GET['g'] );
        unset( $_GET['m'] );
        unset( $_GET['a'] );
        unset( $_POST['g'] );
        unset( $_POST['m'] );
        unset( $_POST['a'] );

        require_once 'today/class.today.php';
        require_once 'today/class.wxpay.php';

        $Wxpay = new \Wxpay;
        $verify = $Wxpay = $Wxpay->verify( $_POST );
        if ( $verify['status'] ) {
            //验证成功，并且支付成功
            $orderNo = $verify['data']['out_trade_no'];
            if ( !$orderNo ) return;
            echo 'success';
        }
    }
    public function wx_payment_return() {
        unset( $_GET['g'] );
        unset( $_GET['m'] );
        unset( $_GET['a'] );

        $pay_id = trim( $_GET['pay_id'] );
        /*if ( !$pay_id ) {
            header( 'HTTP/1.1 404 Not Found' );
            header( 'Status:404 Not Found' );
            if ( sp_template_file_exists( MODULE_NAME.'/404' ) ) $this->display( ':404' );
            return;
        }*/
        if ($pay_id == 0){
            $this->assign( 'check', '0' );
        }elseif ($pay_id == 1){
            $this->assign( 'check', '1' );
        }else{
            //修改订单状态
            $data['pay_status'] = 1;
            $this->common_order_model->where( array( 'pay_id' => $pay_id ) )->save($data);

            $order = D( 'Common_order' )->where( array( 'pay_id' => $pay_id ) )->find();
            if ( !$order ) {
                header( 'HTTP/1.1 404 Not Found' );
                header( 'Status:404 Not Found' );
                if ( sp_template_file_exists( MODULE_NAME.'/404' ) ) $this->display( ':404' );
                return;
            }
            //关联病患
            $dp['doctor_id'] = $order['payee'];
            $dp['patient_id'] = session('open_id');
            $dp['create_time'] = date('Y-m-d H:i:s',time());
            $this->common_dp_model->add($dp);
            //记录聊天时间
            /*$where = array();
            $where['d_id'] = array('eq',$order['payee']);
            $where['p_id'] = array('eq',$order['payer']);
            $chat = $this->common_chattime_model->where($where)->find();*/
            $data['chat_time'] = date('Y-m-d H:i:s',time());
            $data['d_id'] = $order['payee'];
            $data['p_id'] = $order['payer'];
            $this->common_chattime_model->add($data);
            /*if(empty($chat)){
                $data['d_id'] = $order['payee'];
                $data['p_id'] = $order['payer'];
                $this->common_chattime_model->add($data);
            }else{
                $this->common_chattime_model->where(array('id' => $chat['id']))->save($data);
            }*/
            //医生提成
            $doctor = $this->common_user_model->where( array( 'id' => $order['payee'] ) )->find();
            $money = $order['money']*$doctor['proportion'];
            $datas['assets'] = $doctor['assets'] + $money;
            $this->common_user_model->where( array( 'id' => $doctor['id'] ) )->save($datas);
            //向医生发送客服消息
            /*require_once 'today/Wechat.php';
            $wechat = new \Wechat( $this );
            $patient = $this->common_user_model->where( array( 'id' => session('login_id') ) )->find();
            $msg = $patient['name'].',正在向您发起咨询，请及时应答';
            $wechat->customSend($doctor['open_id'],$msg);*/
            session('send_id',$order['payee']);
            $this->assign( 'order', $order );
            $this->assign( 'doctor', $doctor );
            $this->assign( 'check', '2' );
        }
        $this->display( '../pay_return' );
    }
    public function clearSession(){
        unset($_SESSION);
        session_destroy();
        $this->ajaxReturn(true);
    }
    public function instructions(){
        $this->display( '/instructions' );
    }
    //消息提醒
    function message() {
        $id = session('open_id');
        $msg_count = session('msg_count');
        $where = array();
        $where['r.patient_id'] = array('eq',$id);
        $list = $this->common_remind_model->alias('r')->field('p.name as name,o.name as oname,r.flg as flg,r.create_time as create_time')->join('__COMMON_USER__ p ON r.doctor_id=p.id')->join('left join __COMMON_USER__ o ON r.doctor_id_o=o.id')->where($where)->order('r.create_time desc')->select();
        if ($msg_count != 0){
            $data['status'] = 1;
            $where['r.status'] = array('eq',0);
            $this->common_remind_model->alias('r')->where($where)->save($data);
            session('msg_count',0);
        }
        $this->assign( 'list', $list );
        $this->assign( 'msg_count', session('msg_count') );
        $this->display('/message');
    }
    public function videolz(){
        $this->display( '/video' );
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