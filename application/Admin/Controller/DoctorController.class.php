<?php
/**
 * 医生管理
 */
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class DoctorController extends AdminbaseController {

    private $common_user_model;
    private $common_qrcode_model;
	
	function _initialize() {
		parent::_initialize();

        $this->common_user_model = D( 'Common_user' );
        $this->common_qrcode_model = D( 'Common_qrcode' );
	}
	//医生信息列表
	function index() {
		$where = array();
		//姓名
		$name=I('name');
		$this->assign( 'name', $name );
		if ( $name ) $where['name'] = array('like',"%$name%");

        $where['status'] = array('eq',1);
        $where['del_flg'] = array('eq',0);
		$count = $this->common_user_model->where($where)->count();
		$page = $this->page($count, 20);
		$list = $this->common_user_model->where($where)->limit( $page->firstRow, $page->listRows )->order("create_time asc")->select();
		$this->assign("page", $page->show('Admin'));
		$this->assign( 'list', $list );
		$this->display();
	}

	//添加医生信息
	function add() {
		if ( IS_POST ) {
            $_POST['proportion'] = '0.'.$_POST['bili'];
            $_POST['status'] = '1';
            $_POST['create_time'] = date('Y-m-d H:i:s',time());
			$result = $this->common_user_model->add($_POST);
			if ($result) {
                //记录日志
                LogController::log_record($result,1);
				$this->success('添加医生信息成功！');
			} else {
				$this->error('添加医生信息失败！');
			}
		} else {
			$this->display();
		}
	}
	//编辑医生信息
	function edit() {
		if ( IS_POST ) {
			$id = (int)$_POST['id'];
            $_POST['proportion'] = '0.'.$_POST['bili'];
            $_POST['update_time'] = date('Y-m-d H:i:s',time());
			$result = $this->common_user_model->where(array('id' => $id))->save($_POST);
			if ($result) {
                //记录日志
                LogController::log_record($id,2);
				$this->success('编辑医生信息成功！');
			} else {
				$this->error('编辑医生信息失败！');
			}
		} else {
			$id = intval( I( 'get.id' ) );
			$doctor = $this->common_user_model->find($id);
            $doctor['bili'] = $doctor['proportion']*100;
			$this->assign($doctor);
			$this->display();
		}
	}
    //删除医生信息
    function delete() {
        if ( isset( $_POST['ids'] ) ) {//批量逻辑删除
            $ids = implode( ',', $_POST['ids'] );
            $data['del_flg'] = 1;
            $data['update_time'] = date('Y-m-d H:i:s',time());
            if ( $this->common_user_model->where( "id in ($ids)" )->save( $data ) !== false ) {
                //记录日志
                LogController::log_record($ids,3);
                $this->success('删除成功');
            } else {
                $this->error('删除失败');
            }
        } else if ( isset( $_GET['object'] ) && $_GET['restore'] ) {//恢复数据
            $object = $_GET['object'];
            $log_id = $_GET['id'];
            $data['del_flg'] = 0;
            $data['update_time'] = date('Y-m-d H:i:s',time());
            if ( $this->common_user_model->where( "id in ($object)" )->save( $data ) !== false ) {
                //记录日志
                LogController::modify_log_type($log_id, 4);
                $this->success('恢复成功');
            } else {
                $this->error('恢复失败');
            }
        } else if ( isset( $_GET['id'] ) && $_GET['complete_delete'] ) {//彻底物理删除
            $object = $_GET['object'];
            $log_id = $_GET['id'];
            if ( $this->common_user_model->where( "id in ($object)" )->delete() !== false ) {
                //记录日志
                LogController::modify_log_type($log_id, 5);
                $this->success('彻底删除成功');
            } else {
                $this->error('彻底删除失败');
            }
        } else {//单个逻辑删除
            $id = intval( I( 'get.id' ) );
            $data['del_flg'] = 1;
            $data['update_time'] = date('Y-m-d H:i:s',time());
            if ( $this->common_user_model->where(array('id' => $id))->save($data) !== false ) {
                //记录日志
                LogController::log_record($id,3);
                $this->success('删除成功');
            } else {
                $this->error('删除失败');
            }
        }
    }
    //导入医生信息
    function upload() {
        if ( IS_POST ) {
            $uploadConfig = array(
                'FILE_UPLOAD_TYPE' => sp_is_sae() ? 'Sae' : 'Local',
                'rootPath' => './'.C( 'UPLOADPATH' ),
                'savePath' => './excel/doctor/',
                'saveName' => array( 'uniqid', '' ),
                'exts' => array( 'xls', 'xlsx' ),
                'autoSub' => false
            );
            $upload = new \Think\Upload( $uploadConfig );
            $info = $upload->upload();
            $file = './'.C( 'UPLOADPATH' ).$info['doctor']['savepath'].$info['doctor']['savename'];

            require_once 'today/excel/PHPExcel.php';
            require_once 'today/excel/PHPExcel/IOFactory.php';
            require_once 'today/excel/PHPExcel/Reader/Excel5.php';
            require_once 'today/excel/PHPExcel/Reader/Excel2007.php';

            //医生信息读取
            $reader = \PHPExcel_IOFactory::createReader( end( explode( '.', $file ) ) == 'xls' ? 'Excel5' : 'Excel2007' );
            $obj = $reader->load( $file );
            $sheet = $obj->getSheet(0);
            $rowCount = $sheet->getHighestRow();
            $realRowCount = 0;
            $importCount = 0;
            $doctor_info_add = array();
            $time = date('Y-m-d H:i:s',time());
            for ( $i = 2; $i <= $rowCount; $i++ ) {
                $practice_number = $sheet->getCell( 'A'.$i )->getValue();
                $name = $sheet->getCell( 'B'.$i )->getValue();
                $sex = $sheet->getCell( 'C'.$i )->getValue();
                $age = $sheet->getCell( 'D'.$i )->getValue();
                $money = $sheet->getCell( 'E'.$i )->getValue();
                $hospital = $sheet->getCell( 'F'.$i )->getValue();
                $office = $sheet->getCell( 'G'.$i )->getValue();
                $tag = $sheet->getCell( 'H'.$i )->getValue();
                $phone = $sheet->getCell( 'I'.$i )->getValue();
                $area = $sheet->getCell( 'J'.$i )->getValue();
                $proportion = $sheet->getCell( 'K'.$i )->getValue();
                $speciality = $sheet->getCell( 'L'.$i )->getValue();
                $realRowCount++;
                $importCount++;
                $doctor_info_add[] = array(
                    "practice_number" => $practice_number, "name" => $name, "sex" => $sex, "age" => $age, "money" => $money, "hospital" => $hospital, "office" => $office,
                    "tag" => $tag, "area" => $area, "speciality" => $speciality, "phone" => $phone, "proportion" => $proportion, "create_time" => $time
                );
            }
            foreach ($doctor_info_add as $table_doctor) {
                $this->common_user_model->add($table_doctor);
            }
            @unlink( $file );
            $this->success( '成功导入'.$importCount.'条医生记录', U( 'doctor/index' ) );
        } else {
            $this->display();
        }
    }
    //医生推荐
    function recommend() {
        $where = array();
        if(isset($_POST['ids'])){
            $ids = implode( ',', $_POST['ids'] );
            $recommend = intval( I( 'get.recommend' ) );
            if($recommend == 2){
                //判断是否设置已满
                $where['recommend'] = array('eq',2);
                $existed = $this->common_user_model->where($where)->count();
                if ($existed>=2){
                    $this->error('主推医生已满，只能设置两个主推医生');
                }
                $check = 2-$existed;
                if (count($_POST['ids'])>$check) $this->error('您只能再设置'.$check.'个主推医生');
                //判断是否已设置
                $where['id'] = array('in',$ids);
                $expired = $this->common_user_model->where($where)->count();
                if (!empty($expired)){
                    $this->error('选择医生已设置主推，请选择其他医生');
                }
            }
            if ($recommend == 1){
                //判断是否已设置
                $where['id'] = array('in',$ids);
                $where['recommend'] = array('eq',1);
                $expired = $this->common_user_model->where($where)->count();
                if (!empty($expired)){
                    $this->error('选择医生已设置推荐，请选择其他医生');
                }
            }
            $data['recommend'] = $recommend;
            if ( $this->common_user_model->where( "id in ($ids)" )->save( $data ) !== false ) {
                //记录日志
                LogController::log_record($ids,2);
                $this->success('设置成功');
            } else {
                $this->error('设置失败');
            }
        } else {
            $id = intval( I( 'get.id' ) );
            $recommend = intval( I( 'get.recommend' ) );
            if($recommend == 2){
                //判断是否设置已满
                $where['recommend'] = array('eq',2);
                $existed = $this->common_user_model->where($where)->count();
                if ($existed>=2){
                    $this->error('主推医生已满，只能设置两个主推医生');
                }
                //判断是否已设置
                $where['id'] = array('eq',$id);
                $expired = $this->common_user_model->where($where)->count();
                if (!empty($expired)){
                    $this->error('此医生已设置主推，请选择其他医生');
                }
            }
            if ($recommend == 1){
                //判断是否已设置
                $where['id'] = array('eq',$id);
                $where['recommend'] = array('eq',1);
                $expired = $this->common_user_model->where($where)->count();
                if (!empty($expired)){
                    $this->error('选择医生已设置推荐，请选择其他医生');
                }
            }
            $data['recommend'] = $recommend;
            if ( $this->common_user_model->where(array('id' => $id))->save($data) !== false ) {
                //记录日志
                LogController::log_record($id,2);
                $this->success('设置成功');
            } else {
                $this->error('设置失败');
            }
        }
    }
    public function getqrcode(){
        $qrcode = $this->common_qrcode_model->getField('qrcode');
        if (empty($qrcode)){
            //获取图片信息
            require_once 'today/Wechat.php';
            $wechat = new \Wechat( $this );
            $header = 'push/image/wx_header.jpg';
            $qr_url = $wechat->getUserQRcode('0');
            $qrcode_info = $wechat->downloadWeixinFile('',$qr_url);
            //合并生成带logo的二维码
            require_once 'today/QRCode.php';
            $myqrcode = new \MyQRCode( $this );
            $qrcode = $myqrcode->mergeLogoCode($qrcode_info['body'],file_get_contents($header));
            $data = array();
            $data['qrcode'] = $qrcode;
            $this->common_qrcode_model->add($data);
        }
        $this->ajaxReturn($qrcode);
    }
}