<?php

/**
 * Класс Pactioner наследник стандарного Actioner
 * Предназначен для выполнения действий,  AJAX запросов плагина 
 *
 * @author Osipov Ivan
 */
class Pactioner extends Actioner {

  private static $pluginName = 'mg-io-excel';
  
  /*
   * Очистка каталога
   */
  public function clearCatalog(){
    $this->messageSucces = $this->lang['CLEAR_CATALOG_SUCCESS'];
    
    DB::query('TRUNCATE TABLE `'.PREFIX.'cache`');
    DB::query('TRUNCATE TABLE `'.PREFIX.'product_variant`');
    DB::query('TRUNCATE TABLE `'.PREFIX.'product`');
    DB::query('TRUNCATE TABLE `'.PREFIX.'product_user_property`');
    DB::query('TRUNCATE TABLE `'.PREFIX.'category`');
    DB::query('TRUNCATE TABLE `'.PREFIX.'category_user_property`');
    DB::query('TRUNCATE TABLE `'.PREFIX.'property`');
    
    return true;
  }
  
  /*
   * Загрузка файла импорта
   */
  public function uploadFileToImport(){
    USER::AccessOnly('1,4', 'exit()'); 
     
    $tempData = $this->addImportCatalogFile();
    $arFile = explode('.', $tempData['actualImageName']);
    $this->data = array('file_ext' => array_pop($arFile));
    
    if($tempData['status'] == 'success'){
      mgIOExcel::setCompliance($tempData['actualImageName'], $_POST['importType']);
      $this->messageSucces = $tempData['msg'];
      return true;
    }else{
      $this->status = $tempData['status'];
      $this->messageError = $tempData['msg'];
      return false;
    }	
  }
  
  /**
   * Загружает  файл для импорта каталога
   * @param $filename - путь к файлу на сервере
   * @return boolean
   */
  public function addImportCatalogFile() {
    USER::AccessOnly('1,4', 'exit()');
    
    $validFormats = array('xls', 'xlsx');
    $realDocumentRoot = str_replace(DIRECTORY_SEPARATOR.'mg-plugins'.DIRECTORY_SEPARATOR.self::$pluginName, '', dirname(__FILE__));  
    
    if(isset($_POST) && 'POST' == $_SERVER['REQUEST_METHOD']){
      
      if(!empty($_FILES['upload_data_file'])){
        $file_array = $_FILES['upload_data_file'];
      }
      
      $name = $file_array['name'];
      $size = $file_array['size'];
      
      if(strlen($name)){
        $fullName = explode('.', $name);
        $ext = array_pop($fullName);
        $file = $realDocumentRoot.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'import.'.$ext;
        if(in_array(strtolower($ext), $validFormats)){
          if($size < (1024 * 10 * 1024) && !empty($file_array['tmp_name'])){ //$file_array['tmp_name'] будет пустым если размер загруженного файла превышает размер установленный параметром upload_max_filesize в php.ini
            copy($file_array['tmp_name'], $file);
            unlink($file_array['tmp_name']);
            return array('msg' => 'Файлы подготовлены', 'status' => 'success', 'actualImageName' => $file);
          }else{
            return array('msg' => $this->lang['ACT_FILE_NOT_UPLOAD1'], 'status' => 'error');
          }
        }else{
          return array('msg' => $this->lang['ACT_FILE_NOT_UPLOAD2'], 'status' => 'error');
        }
      }else{
        return array('msg' => $this->lang['ACT_FILE_NOT_UPLOAD3'], 'status' => 'error');
      }
    }
    return true;
  }
  
  /*
   * Импорт каталога
   */
  public function importCatalog(){
    $this->messageSucces = $this->lang['SUCCESS_IMPORT'];
    $this->messageError = $this->lang['IMPORT_ERROR'];
    $this->data['message'] = '';
    
    $arParams = $_POST['data'];
    
    $fullName = explode('.', $arParams['upload_data_file']);
    $extention = array_pop($fullName);
    
    $realDocumentRoot = str_replace(DIRECTORY_SEPARATOR.'mg-plugins'.DIRECTORY_SEPARATOR.PM::getFolderPlugin(__FILE__), '', dirname(__FILE__));  
    $file = $realDocumentRoot.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'import.'.$extention;
    
    if(!file_exists($file)){
      $this->messageError = $this->lang['NOT_UPLOAD_FILE'];
      return false;
    }
    
    $importType = $arParams['importType'];
    $schemeType = ($arParams['importScheme'] != 'default') ? 'last' : 'default';
    
    $data = mgIOExcel::importFromExcel($file, $schemeType, $importType);
    
    if(!$data['importSuccess']){
      
      if(!empty($data['error_message'])){
        $this->messageError = $data['error_message'];
        return false;
      }
        
      $this->messageSucces = $this->lang['SUCCESS_STEP'].' '.$data['nextRow'].' '.$this->lang['SUCCESS_STEP_LINES'];
      $this->data['nextRow'] = $data['nextRow'];
      return true;
    }
    
    unlink($file);
    $this->data['importSuccess'] = true;
    return true;
  }
  
  /*
   * Экспорт каталога
   */
  public function exportCatalog(){
    $this->messageSucces = $this->lang['SUCCESS_EXPORT'];
    $this->messageError = $this->lang['EXPORT_ERROR'];
    $this->data['message'] = '';
    
    $nextRow = $_POST['nextRow'];
    $step = empty($_POST['step']) ? 1 : $_POST['step'];
    
    $arParams = array(
      'only_active'  => (empty($_POST['data']['only_active']) || $_POST['data']['only_active'] == 'false') ? false : true,
      'only_in_count' => (empty($_POST['data']['only_in_count']) || $_POST['data']['only_in_count'] == 'false') ? false : true,
    );
    
    $categoryIds = (empty($_POST['data']['export_category_list']) || $_POST['data']['export_category_list'] == 'null') ? 0 : $_POST['data']['export_category_list'];
    
    $data = mgIOExcel::exportToExcel($nextRow, $step, $arParams, $categoryIds);
    
    if(!$data['exportSuccess']){
      
      if(!empty($data['error_message'])){
        $this->messageError = $data['error_message'];
        return false;
      }
        
      $this->messageSucces = $this->lang['SUCCESS_STEP'].' '.$data['percent'].'%';
      $this->data['nextRow'] = $data['nextRow'];
      $this->data['nextStep'] = $data['nextStep'];
      return true;
    }
    
    $this->data = $data;
    return true;
  }
  
  /*
   * Записываем соответствие столбцов нашего файла к нужной структуре в базу.
   */
  public function setCompliance(){
    $this->messageSucces = $this->lang['SUCCESS_SCHEME_INSTALL'];
    $this->messageError = $this->lang['FAIL_SCHEME_INSTALL'];
    $complianceArray = array();
    
    foreach($_POST['data'] as $key=>$index){
      $id = substr($key, 8);
      $complianceArray[$id] = $index;
    }    
    
    MG::setOption(array('option' => self::$pluginName.'-last'.$_POST['importType'].'ColComp', 'value' => addslashes(serialize($complianceArray))));
    return true;
  }
  
  public function getCompliance(){
    $arParams = $_POST['data'];
    $fullName = explode('.', $arParams['upload_data_file']);
    $extention = array_pop($fullName);
    
    $realDocumentRoot = str_replace(DIRECTORY_SEPARATOR.'mg-plugins'.DIRECTORY_SEPARATOR.PM::getFolderPlugin(__FILE__), '', dirname(__FILE__));  
    $file = $realDocumentRoot.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'import.'.$extention;
    
    $this->data['compliance'] = mgIOExcel::getCompliance($_POST['type'], $arParams['importType']);
    $this->data['maskArray'] = mgIOExcel::getMaskArray($arParams['importType']);
    $this->data['titleList'] = mgIOExcel::getTitleList($file);
    return true;
  }
  
  public function isSetCompliance(){
    $this->data['isSet'] = false;
    $importType = $_POST['importType'];
    $cmp = MG::getOption(self::$pluginName.'-last'.$importType.'ColComp');
    
    if($cmp){
      $this->data['isSet'] = true;
    }
    
    return true;
  }

}