<?php
/**
 * 数据导入
 */
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class UploadInfoController extends AdminbaseController {

    private $common_user_model;
    private $common_office_model;
    private $common_card_model;
    private $common_tag_model;
    private $common_health_model;
	
	function _initialize() {
		parent::_initialize();

        $this->common_user_model = D( 'Common_user' );
        $this->common_office_model = D( 'Common_office' );
        $this->common_card_model = D( 'Common_card' );
        $this->common_tag_model = D( 'Common_tag' );
        $this->common_health_model = D( 'Common_health' );
	}
	//数据导入列表
	function index() {
		$this->display();
	}
    //导入用户信息
    function upload_user() {
        if ( IS_POST ) {
            $uploadConfig = array(
                'FILE_UPLOAD_TYPE' => sp_is_sae() ? 'Sae' : 'Local',
                'rootPath' => './'.C( 'UPLOADPATH' ),
                'savePath' => './excel/user/',
                'saveName' => array( 'uniqid', '' ),
                'exts' => array( 'xls', 'xlsx' ),
                'autoSub' => false
            );
            vendor('PHPExcel.PHPExcel');
            $upload = new \Think\Upload( $uploadConfig );
            $info = $upload->upload();
            $file = './'.C( 'UPLOADPATH' ).$info['file_name']['savepath'].$info['file_name']['savename'];
            if(!file_exists($file)){
                die('文件不存在');
            }
            //获取文件类型
            $file_suffix = pathinfo($file)['extension'];
            //设置模板根据不同的excel版本
            $excel_temple = array('xls'=>'Excel5','xlsx'=>'Excel2007');
            $objReader = \PHPExcel_IOFactory::createReader($excel_temple[$file_suffix]);//配置成2003版本，因为office版本可以向下兼容
            $objPHPExcel = $objReader->load($file,$encode='utf-8');//$file 为解读的excel文件
            $sheet = $objPHPExcel->getSheet(0);
            $rowCount = $sheet->getHighestRow();
            $realRowCount = 0;
            $importCount = 0;
            $user_info_add = array();
            for ( $i = 2; $i <= $rowCount; $i++ ) {
                $phone = $sheet->getCell( 'A'.$i )->getValue();
                $create_time = $sheet->getCell( 'B'.$i )->getFormattedValue();
                $m_card_id = $sheet->getCell( 'C'.$i )->getValue();
                $i_card = $sheet->getCell( 'D'.$i )->getValue();
                $name = $sheet->getCell( 'E'.$i )->getValue();
                $open_id = $sheet->getCell( 'F'.$i )->getValue();
                $birthday = $sheet->getCell( 'G'.$i )->getValue();
                $sex = $sheet->getCell( 'H'.$i )->getValue();
                $nation = $sheet->getCell( 'I'.$i )->getValue();
                $weight = $sheet->getCell( 'J'.$i )->getValue();
                $height = $sheet->getCell( 'K'.$i )->getValue();
                $realRowCount++;
                $importCount++;
                $user_info_add[] = array(
                    "phone" => $phone, "create_time" => $create_time, "m_card_id" => $m_card_id, "i_card" => $i_card, "name" => $name, "open_id" => $open_id, "birthday" => $birthday, "sex" => $sex, "nation" => $nation, "weight" => $weight, "height" => $height
                );
            }
            foreach ($user_info_add as $table_user) {
                $this->common_user_model->add($table_user);
            }
            @unlink( $file );
            $this->success( '成功导入'.$importCount.'条用户记录', U( 'uploadInfo/index' ) );
        } else {
            $this->display();
        }
    }
    //导入医生信息
    function upload_doctor() {
        if ( IS_POST ) {
            $uploadConfig = array(
                'FILE_UPLOAD_TYPE' => sp_is_sae() ? 'Sae' : 'Local',
                'rootPath' => './'.C( 'UPLOADPATH' ),
                'savePath' => './excel/doctor/',
                'saveName' => array( 'uniqid', '' ),
                'exts' => array( 'xls', 'xlsx' ),
                'autoSub' => false
            );
            vendor('PHPExcel.PHPExcel');
            $upload = new \Think\Upload( $uploadConfig );
            $info = $upload->upload();
            $file = './'.C( 'UPLOADPATH' ).$info['file_name']['savepath'].$info['file_name']['savename'];
            if(!file_exists($file)){
                die('文件不存在');
            }
            //获取文件类型
            $file_suffix = pathinfo($file)['extension'];
            //设置模板根据不同的excel版本
            $excel_temple = array('xls'=>'Excel5','xlsx'=>'Excel2007');
            $objReader = \PHPExcel_IOFactory::createReader($excel_temple[$file_suffix]);//配置成2003版本，因为office版本可以向下兼容
            $objPHPExcel = $objReader->load($file,$encode='utf-8');//$file 为解读的excel文件
            $sheet = $objPHPExcel->getSheet(0);
            $rowCount = $sheet->getHighestRow();
            $realRowCount = 0;
            $importCount = 0;
            $doctor_info_add = array();
            $time = date('Y-m-d H:i:s',time());
            for ( $i = 2; $i <= $rowCount; $i++ ) {
                $name = $sheet->getCell( 'A'.$i )->getValue();
                $photo = $sheet->getCell( 'B'.$i )->getValue();
                $hospital = $sheet->getCell( 'C'.$i )->getValue();
                $office = $sheet->getCell( 'D'.$i )->getValue();
                $tag = $sheet->getCell( 'E'.$i )->getValue();
                $position = $sheet->getCell( 'F'.$i )->getValue();
                $phone = $sheet->getCell( 'G'.$i )->getValue();
                $introduce = $sheet->getCell( 'H'.$i )->getValue();
                $duty = $sheet->getCell( 'I'.$i )->getValue();
                $realRowCount++;
                $importCount++;
                $doctor_info_add[] = array(
                    "name" => $name, "photo" => $photo, "hospital" => $hospital, "office" => $office, "tag" => $tag, "position" => $position, "phone" => $phone,
                    "introduce" => $introduce, "duty" => $duty, "type" => 1, "create_time" => $time
                );
            }
            foreach ($doctor_info_add as $table_doctor) {
                $this->common_user_model->add($table_doctor);
            }
            @unlink( $file );
            $this->success( '成功导入'.$importCount.'条医生记录', U( 'uploadInfo/index' ) );
        } else {
            $this->display();
        }
    }
    //导入科室信息
    function upload_office() {
        if ( IS_POST ) {
            $uploadConfig = array(
                'FILE_UPLOAD_TYPE' => sp_is_sae() ? 'Sae' : 'Local',
                'rootPath' => './'.C( 'UPLOADPATH' ),
                'savePath' => './excel/office/',
                'saveName' => array( 'uniqid', '' ),
                'exts' => array( 'xls', 'xlsx' ),
                'autoSub' => false
            );
            vendor('PHPExcel.PHPExcel');
            $upload = new \Think\Upload( $uploadConfig );
            $info = $upload->upload();
            $file = './'.C( 'UPLOADPATH' ).$info['file_name']['savepath'].$info['file_name']['savename'];
            if(!file_exists($file)){
                die('文件不存在');
            }
            //获取文件类型
            $file_suffix = pathinfo($file)['extension'];
            //设置模板根据不同的excel版本
            $excel_temple = array('xls'=>'Excel5','xlsx'=>'Excel2007');
            $objReader = \PHPExcel_IOFactory::createReader($excel_temple[$file_suffix]);//配置成2003版本，因为office版本可以向下兼容
            $objPHPExcel = $objReader->load($file,$encode='utf-8');//$file 为解读的excel文件
            $sheet = $objPHPExcel->getSheet(0);
            $rowCount = $sheet->getHighestRow();
            $realRowCount = 0;
            $importCount = 0;
            $office_info_add = array();
            $time = date('Y-m-d H:i:s',time());
            for ( $i = 2; $i <= $rowCount; $i++ ) {
                $name = $sheet->getCell( 'A'.$i )->getValue();
                $realRowCount++;
                $importCount++;
                $office_info_add[] = array(
                    "name" => $name, "create_time" => $time
                );
            }
            foreach ($office_info_add as $table_office) {
                $this->common_office_model->add($table_office);
            }
            @unlink( $file );
            $this->success( '成功导入'.$importCount.'条科室记录', U( 'uploadInfo/index' ) );
        } else {
            $this->display();
        }
    }
    //导入具体科室
    function upload_tag() {
        if ( IS_POST ) {
            $uploadConfig = array(
                'FILE_UPLOAD_TYPE' => sp_is_sae() ? 'Sae' : 'Local',
                'rootPath' => './'.C( 'UPLOADPATH' ),
                'savePath' => './excel/tag/',
                'saveName' => array( 'uniqid', '' ),
                'exts' => array( 'xls', 'xlsx' ),
                'autoSub' => false
            );
            vendor('PHPExcel.PHPExcel');
            $upload = new \Think\Upload( $uploadConfig );
            $info = $upload->upload();
            $file = './'.C( 'UPLOADPATH' ).$info['file_name']['savepath'].$info['file_name']['savename'];
            if(!file_exists($file)){
                die('文件不存在');
            }
            //获取文件类型
            $file_suffix = pathinfo($file)['extension'];
            //设置模板根据不同的excel版本
            $excel_temple = array('xls'=>'Excel5','xlsx'=>'Excel2007');
            $objReader = \PHPExcel_IOFactory::createReader($excel_temple[$file_suffix]);//配置成2003版本，因为office版本可以向下兼容
            $objPHPExcel = $objReader->load($file,$encode='utf-8');//$file 为解读的excel文件
            $sheet = $objPHPExcel->getSheet(0);
            $rowCount = $sheet->getHighestRow();
            $realRowCount = 0;
            $importCount = 0;
            $tag_info_add = array();
            $time = date('Y-m-d H:i:s',time());
            for ( $i = 2; $i <= $rowCount; $i++ ) {
                $name = $sheet->getCell( 'A'.$i )->getValue();
                $office_id = $sheet->getCell( 'B'.$i )->getValue();

                $realRowCount++;
                $importCount++;
                $tag_info_add[] = array(
                    "office_id" => $office_id, "name" => $name, "create_time" => $time
                );
            }
            foreach ($tag_info_add as $table_tag) {
                $this->common_tag_model->add($table_tag);
            }
            @unlink( $file );
            $this->success( '成功导入'.$importCount.'条具体科室记录', U( 'uploadInfo/index' ) );
        } else {
            $this->display();
        }
    }
    //导入健康状况
    function upload_health() {
        if ( IS_POST ) {
            $uploadConfig = array(
                'FILE_UPLOAD_TYPE' => sp_is_sae() ? 'Sae' : 'Local',
                'rootPath' => './'.C( 'UPLOADPATH' ),
                'savePath' => './excel/health/',
                'saveName' => array( 'uniqid', '' ),
                'exts' => array( 'xls', 'xlsx' ),
                'autoSub' => false
            );
            vendor('PHPExcel.PHPExcel');
            $upload = new \Think\Upload( $uploadConfig );
            $info = $upload->upload();
            $file = './'.C( 'UPLOADPATH' ).$info['file_name']['savepath'].$info['file_name']['savename'];
            if(!file_exists($file)){
                die('文件不存在');
            }
            //获取文件类型
            $file_suffix = pathinfo($file)['extension'];
            //设置模板根据不同的excel版本
            $excel_temple = array('xls'=>'Excel5','xlsx'=>'Excel2007');
            $objReader = \PHPExcel_IOFactory::createReader($excel_temple[$file_suffix]);//配置成2003版本，因为office版本可以向下兼容
            $objPHPExcel = $objReader->load($file,$encode='utf-8');//$file 为解读的excel文件
            $sheet = $objPHPExcel->getSheet(0);
            $rowCount = $sheet->getHighestRow();
            $realRowCount = 0;
            $importCount = 0;
            $health_info_add = array();
            $time = date('Y-m-d H:i:s',time());
            for ( $i = 2; $i <= $rowCount; $i++ ) {
                $name = $sheet->getCell( 'A'.$i )->getValue();
                $up_id = $sheet->getCell( 'B'.$i )->getValue();
                $realRowCount++;
                $importCount++;
                $health_info_add[] = array(
                    "up_id" => $up_id, "name" => $name, "create_time" => $time
                );
            }
            foreach ($health_info_add as $table_health) {
                $this->common_health_model->add($table_health);
            }
            @unlink( $file );
            $this->success( '成功导入'.$importCount.'条健康状况记录', U( 'uploadInfo/index' ) );
        } else {
            $this->display();
        }
    }
    //导入会员卡信息
    function upload_card() {
        if ( IS_POST ) {
            $uploadConfig = array(
                'FILE_UPLOAD_TYPE' => sp_is_sae() ? 'Sae' : 'Local',
                'rootPath' => './'.C( 'UPLOADPATH' ),
                'savePath' => './excel/m_card/',
                'saveName' => array( 'uniqid', '' ),
                'exts' => array( 'xls', 'xlsx' ),
                'autoSub' => false
            );
            vendor('PHPExcel.PHPExcel');
            $upload = new \Think\Upload( $uploadConfig );
            $info = $upload->upload();
            $file = './'.C( 'UPLOADPATH' ).$info['file_name']['savepath'].$info['file_name']['savename'];
            if(!file_exists($file)){
                die('文件不存在');
            }
            //获取文件类型
            $file_suffix = pathinfo($file)['extension'];
            //设置模板根据不同的excel版本
            $excel_temple = array('xls'=>'Excel5','xlsx'=>'Excel2007');
            $objReader = \PHPExcel_IOFactory::createReader($excel_temple[$file_suffix]);//配置成2003版本，因为office版本可以向下兼容
            $objPHPExcel = $objReader->load($file,$encode='utf-8');//$file 为解读的excel文件
            $sheet = $objPHPExcel->getSheet(0);
            $rowCount = $sheet->getHighestRow();
            $realRowCount = 0;
            $importCount = 0;
            $time = date('Y-m-d H:i:s',time());
            $m_card_add = array();
            for ( $i = 2; $i <= $rowCount; $i++ ) {
                $card_number = $sheet->getCell( 'A'.$i )->getValue();
                $card_pwd = $sheet->getCell( 'B'.$i )->getValue();
                $realRowCount++;
                $importCount++;
                $m_card_add[] = array(
                    "card_number" => $card_number, "card_pwd" => $card_pwd, "card_time" => "1年", "create_time" => $time
                );
            }
            foreach ($m_card_add as $table_card) {
                $this->common_card_model->add($table_card);
            }
            @unlink( $file );
            $this->success( '成功导入'.$importCount.'条会员卡记录', U( 'uploadInfo/index' ) );
        } else {
            $this->display();
        }
    }
}