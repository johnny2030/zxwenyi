<?php
/**
 * 用户端
 */
namespace Portal\Controller;

class UserController extends CheckController {

    private $wxinfor_model;
    private $common_user_model;
    private $formError = array();

	function _initialize() {
		parent::_initialize();

        $this->wxinfor_model = D( 'Wxinfor' );
        $this->common_user_model = D( 'Common_user' );
	}
    //患者登记
    public function register_patient() {
        $this->display('../Tieqiao/register_patient');
    }
    //患者个人中心
    public function info_patient() {
        $this->display('../Tieqiao/info_patient');
    }
    //医生登记
    public function register_doctor() {
        $this->display('../Tieqiao/register_doctor');
    }
    //医生个人中心
    public function info_doctor() {
        $this->display('../Tieqiao/info_doctor');
    }
    //科室展示
    public function office_preview() {
        $this->display('../Tieqiao/info_doctor');
    }
    //医生列表
    public function doctor_list() {
        $this->display('../Tieqiao/info_doctor');
    }
    //医生详情
    public function doctor_detail() {
        $this->display('../Tieqiao/info_doctor');
    }
	//患者信息列表
	function patient_user() {
        $id = (int)session('login_id');
        $where = array();
        $where['c.doctor_id'] = array('eq',$id);
        $where['c.status'] = array('eq',0);
        $where['c.del_flg'] = array('eq',0);
        $list = $this->common_dp_model->alias('c')->field('distinct c.patient_id,p.*')->join('__COMMON_USER__ p ON c.patient_id=p.open_id')->where($where)->order('c.create_time desc')->select();
		$this->assign( 'list', $list );
        $this->assign( 'msg_count', session('msg_count') );
		$this->display('../Doctor/list');
	}
    //患者信息详情
    function details() {
	    //患者信息
        $id = intval( I( 'get.id' ) );
        $patient = $this->common_user_model->find($id);
        //病例图片
        $where = array();
        $where['user_id'] = array('eq',$id);
        $where['del_flg'] = array('eq',0);
        $list = $this->patient_record_model->where($where)->select();

        $this->assign( 'list', $list );
        $this->assign( 'patient', $patient );
        $this->assign( 'msg_count', session('msg_count') );
        $this->display('../Doctor/details');
    }
    //患者信息详情
    function details_s() {
        //患者信息
        $id = intval( I( 'get.id' ) );
        $sd = $_GET['id'];
        \Think\Log::write('——————id:'.$id.'sd:'.$sd,'WARN');
        $patient = $this->common_user_model->find($id);
        //病例图片
        $where = array();
        $where['user_id'] = array('eq',$id);
        $where['del_flg'] = array('eq',0);
        $list = $this->patient_record_model->where($where)->select();

        $this->assign( 'list', $list );
        $this->assign( 'patient', $patient );
        $this->display('../Doctor/details_s');
    }
    //账户管理
    function account() {
        $id = (int)session('login_id');
        $doctor = $this->common_user_model->field('assets')->find($id);
        $this->assign( 'doctor', $doctor );
        $this->assign( 'msg_count', session('msg_count') );
        $this->display('../Doctor/account');
    }
    //明细
    function income() {
        $id = (int)session('login_id');
        $where = array();
        $where['pay_status'] = array('eq',1);
        $where['payee|payer'] = array('eq',$id);
        $where['del_flg'] = array('eq',0);
        $list = $this->common_order_model->where($where)->select();
        $this->assign( 'list', $list );
        $this->assign( 'msg_count', session('msg_count') );
        $this->display('../Doctor/income');
    }
    //医生圈
    function doctor_cp() {
        $this->assign( 'msg_count', session('msg_count') );
        $this->display('../Doctor/doctor_cp');
    }
    //医生信息详情
    function doctor_user() {
        $id = (int)session('login_id');
        $doctor = $this->common_user_model->find($id);
        if ($doctor['province'] != ''){
            $province = $this->china_model->find($doctor['province']);
            $doctor['provinceName'] = $province['name'];
        }
        if ($doctor['city'] != ''){
            $city = $this->china_model->find($doctor['city']);
            $doctor['cityName'] = $city['name'];
        }
        if ($doctor['county'] != ''){
            $county = $this->china_model->find($doctor['county']);
            $doctor['countyName'] = $county['name'];
        }
        $this->assign( 'doctor', $doctor );
        $this->assign( 'msg_count', session('msg_count') );
        $this->display('../Doctor/personal');
    }
    //医生信息修改
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
                R('Doctor/doctor_user');
            } else {
                $this->error('修改失败！');
            }
        } else {
            $data = $_GET['data'];//数据
            $flg = $_GET['flg'];//数据库字段名
            $menu = $_GET['menu'];//页面字段名
            $check = $_GET['check'];//介绍/擅长
            if ($flg == 'hospital'){
                $list = $this->common_hospital_model->select();
            }
            if ($flg == 'office'){
                $list = $this->common_office_model->select();
            }
            if ($flg == 'tag'){
                $list = $this->common_tag_model->select();
            }
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
            $this->assign( 'list', $list );
            $this->assign( 'data', $data );
            $this->assign( 'flg', $flg );
            $this->assign( 'menu', $menu );
            $this->assign( 'check', $check );
            $this->display('../Doctor/modify');
        }
    }
    //微信提现
    function wxpay() {
        if ( IS_POST ) {
            require_once 'today/class.wxpay.php';
            require_once 'today/class.today.php';
            $Wxpay = new \Wxpay;
            $open_id = session('open_id');
            $id = session('login_id');
            $doctor = $this->common_user_model->find($id);
            if ($doctor['assets'] < $_POST['money']) $this->error('账户金额不足，请更改提现金额');
            $data['pay_id'] = time();
            $data['pay_name'] = '提现';
            $data['payer'] = '0';
            $data['payee'] = $id;
            $data['money'] = $_POST['money'];
            $data['flg'] = '1';
            $data['time'] = date('Y-m-d H:i:s',time());
            $result = $this->common_order_model->add($data);
            if ($result){
                $Wxpay->setUrl( 'http://www.jkwdr.cn/index.php?g=portal&m=doctor&a=wx_payment_return&pay_id='.$data['pay_id'], 'http://www.jkwdr.cn/wx_payment_transfer_notify.php' );
                $transfer = array();
                $transfer['orderNo'] = $data['pay_id'];
                $transfer['openid'] = $open_id;
                $transfer['money'] = $_POST['money'];
                $transfer['ip'] = \Today\Today::ip();//'192.168.1.103';
                $prepay = $Wxpay->transfer($transfer);
                if ( $prepay['status'] != 200 ) {
                    $this->formError[] = $prepay['msg'];
                } else {
                    $this->assign( 'params', json_encode( $Wxpay->getJsApiPayParams( $prepay['prepay_id'] ) ) );
                    $this->assign( 'pay_id', $data['pay_id'] );
                    $this->display( '../wx-transfer-jspay' );
                    exit;
                }
            }
            $this->assign( 'prepay', $prepay );
            $this->assign( 'formError', $this->formError );
            $this->display('../Doctor/transfer');
        }else{
            $this->display('../Doctor/transfer');
        }
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
        if ( !$pay_id ) {
            header( 'HTTP/1.1 404 Not Found' );
            header( 'Status:404 Not Found' );
            if ( sp_template_file_exists( MODULE_NAME.'/404' ) ) $this->display( ':404' );
            return;
        }
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
        //医生提现
        $doctor = $this->common_user_model->find($order['payee']);
        $datas['assets'] = $doctor['assets'] - $order['money'];
        $this->common_user_model->where( array( 'id' => $doctor['id'] ) )->save($datas);
        R('Doctor/account');
    }
    public function clearSession(){
        unset($_SESSION);
        session_destroy();
        $this->ajaxReturn(true);
    }
    //医生二维码展示
    function qrcode(){
        $id = session('login_id');
        $doctor = $this->common_user_model->find($id);
        if (empty($doctor['qrcode'])){
            //获取图片信息
            require_once 'today/Wechat.php';
            $wechat = new \Wechat( $this );
            $qr_url = $wechat->getUserQRcode($id);
            $qrcode_info = $wechat->downloadWeixinFile('',$qr_url);
            $logo_info = $wechat->downloadWeixinFile('',$doctor['photo']);
            //合并生成带logo的二维码
            require_once 'today/QRCode.php';
            $myqrcode = new \MyQRCode( $this );
            $qrcode = $myqrcode->mergeLogoCode($qrcode_info['body'],$logo_info['body']);
            $data['qrcode'] = $qrcode;
            $this->common_user_model->where( array( 'id' => $id ) )->save($data);
        }else{
            $qrcode = $doctor['qrcode'];
        }
        $this->assign( 'qrcode', $qrcode );
        $this->assign( 'msg_count', session('msg_count') );
        $this->display('../Doctor/qrcode');
    }
    //消息提醒
    function message() {
        $id = session('login_id');
        $msg_count = session('msg_count');
        //判断是否存在患者信息  不存在则添加
        $check = array();
        $check['status'] = array('eq',0);
        $check['doctor_id'] = array('eq',$id);
        $plist = $this->common_remind_model->field('patient_id')->where($check)->select();
        if (!empty($plist)){
            foreach ($plist as $k => $p){
                $patient = $this->common_user_model->where( array( 'open_id' => $p['patient_id'] ) )->find();
                if (empty($patient)){
                    require_once 'today/Wechat.php';
                    $wechat = new \Wechat( $this );
                    $wechat->getUserInfor( $p['patient_id'],true );
                    $wxinfor = $this->wxinfor_model->where( array( 'open_id' => $p['patient_id'] ) )->find();
                    $data = array();
                    $data['create_time'] = date('Y-m-d H:i:s',time());
                    $data['open_id'] = $p['patient_id'];
                    $data['name'] = $wxinfor['nickname'];
                    $data['sex'] = $wxinfor['sex'];
                    $data['photo'] = $wxinfor['headimgurl'];
                    $this->common_user_model->add($data);
                }
            }
        }
        $where = array();
        $where['r.doctor_id'] = array('eq',$id);
        $list = $this->common_remind_model->alias('r')->field('p.name as name,o.name as oname,r.flg as flg,r.create_time as create_time')->join('__COMMON_USER__ p ON r.patient_id=p.open_id')->join('left join __COMMON_USER__ o ON r.doctor_id_o=o.id')->where($where)->order('r.create_time desc')->select();
        if ($msg_count != 0){
            $data['status'] = 1;
            $where['r.status'] = array('eq',0);
            $this->common_remind_model->alias('r')->where($where)->save($data);
            session('msg_count',0);
        }
        $this->assign( 'list', $list );
        $this->display('../Doctor/message');
    }
    function shortword(){
        $id = session('login_id');
        if ( IS_POST ) {
            $where = array();
            $where['user_id'] = array('eq',$id);
            $where['del_flg'] = array('eq',0);
            $count = $this->doctor_shortword_model->where($where)->count();
            if($count<9){
                $_POST['user_id'] = $id;
                $_POST['create_time'] = date('Y-m-d H:i:s',time());
                $result = $this->doctor_shortword_model->add($_POST);
                if (!$result) {
                    $this->formError[] = '设置失败';
                }
            }else{
                $this->formError[] = '最多只能设置9条快捷用语';
            }
            $list = $this->doctor_shortword_model->where($where)->select();
            $this->assign( 'list', $list );
            $this->assign( 'formError', $this->formError );
            $this->display('../Doctor/shortword');
        } else {
            $where = array();
            $where['user_id'] = array('eq',$id);
            $where['del_flg'] = array('eq',0);
            $list = $this->doctor_shortword_model->where($where)->select();
            $this->assign( 'list', $list );
            $this->display('../Doctor/shortword');
        }
    }
    //转诊
    function transfer_t() {
        $pid = $_GET['id'];
        $did = session('login_id');
        $where = array();
        $where['id'] = array('neq',$did);
        $where['status'] = array('eq',1);
        $where['del_flg'] = array('eq',0);
        $list = $this->common_user_model->where($where)->select();
        $this->assign( 'list', $list );
        $this->assign( 'pid', $pid );
        $this->display('../Doctor/transfer_t');
    }
    //医生信息详情
    function details_d() {
        $id = intval( I( 'get.id' ) );
        $doctor = $this->common_user_model->find($id);
        $this->assign( 'doctor', $doctor );
        $this->display('../Doctor/details_d');
    }
    //确认转诊
    function transfer_e() {
        $did_o = session('login_id');
        $pid = $_GET['pid'];
        $did = $_GET['did'];
        //当前医生
        $doctor_o = $this->common_user_model->find($did_o);
        //转诊医生
        $doctor = $this->common_user_model->find($did);
        //患者
        $patient = $this->common_user_model->find($pid);
        $where = array();
        $where['doctor_id'] = array('eq',$did);
        $where['patient_id'] = array('eq',$patient['open_id']);
        $where['del_flg'] = array('eq',0);
        $dp = $this->common_dp_model->where($where)->find();
        $data = array();
        $data['doctor_id'] = $did;
        $data['patient_id'] = $patient['open_id'];
        $data['create_time'] = date('Y-m-d H:i:s',time());
        if ($dp == null){
            $this->common_dp_model->add($data);
        }
        $data['message'] = '转诊通知';
        $data['doctor_id_o'] = $did_o;
        $data['flg'] = 1;
        $this->common_remind_model->add($data);
        require_once 'today/Wechat.php';
        $wechat = new \Wechat( $this );
        $wechat->customSendImg($patient['open_id'],'http://www.jkwdr.cn/index.php?g=portal&m=doctor&a=patient_user','转诊通知',$doctor_o['name'].'医生向您推荐了'.$doctor['name'].'医生，点击咨询');
        $wechat->customSendImg($doctor['open_id'],'http://www.jkwdr.cn/index.php?g=portal&m=doctor&a=patient_user','转诊通知',$doctor_o['name'].'医生向您推荐了'.$patient['name'].'患者，点击查看');
        $this->display('../Doctor/transfer_e');
    }
    //上传头像
    public function uploadImg(){
        if ( IS_POST ) {
            $uploadConfig = array(
                'FILE_UPLOAD_TYPE' => sp_is_sae() ? 'Sae' : 'Local',
                'rootPath' => './'.C( 'UPLOADPATH' ),
                'savePath' => 'upload_head/',
                'saveName' => array( 'uniqid', '' ),
                'exts' => array('jpg', 'gif', 'png', 'jpeg'),
                'autoSub' => true
            );
            $upload = new \Think\Upload($uploadConfig);// 实例化上传类
            $info = $upload->upload();// 上传文件
            if(!$info) {// 上传错误提示错误信息
                $this->error($upload->getError());
            }else{// 上传成功
                $id = (int)session('login_id');
                $data['photo'] = $info['photo']['url'];
                $data['update_time'] = date('Y-m-d H:i:s',time());
                $this->common_user_model->where(array('id' => $id))->save($data);
                R('Doctor/doctor_user');
            }
        } else {
            $this->display('../Doctor/uploadImg');
        }
    }
    //语音录制测试
    function wxjs() {
        $this->display('../Doctor/wxjs');
    }
}