<?php

class Controllers_Mgadmin extends BaseController {

  function __construct() {
    MG::disableTemplate();
    $model = new Models_Order;
    MG::addInformer(array('count' => $model->getNewOrdersCount(), 'class' => 'message-wrap', 'classIcon' => 'product-small-icon', 'isPlugin' => false, 'section' => 'orders', 'priority' => 80));
    if ('1' == User::getThis()->role) {
      MG::addInformer(array('count' => '', 'class' => 'message-wrap', 'classIcon' => 'statistic-icon', 'isPlugin' => false, 'section' => 'statistics', 'priority' => 10));
    }

    if (URL::get('csv')) {      
      USER::AccessOnly('1,4','exit()');
      $model = new Models_Catalog;
      $model->exportToCsv();
    }
    if (URL::get('examplecsv')) {
      $model = new Models_Catalog;
      $model->getExampleCSV();
    }
    if (URL::get('examplecategorycsv')) {
      $model = new Models_Catalog;
      $model->getExampleCategoryCSV();
    }
    if (URL::get('examplecsvupdate')) {
      $model = new Models_Catalog;
      $model->getExampleCsvUpdate();
    }
    if (URL::get('category_csv')) {      
      USER::AccessOnly('1,4','exit()');
      MG::get('category')->exportToCsv();
    }

    if (URL::get('yml')) {
      USER::AccessOnly('1,4','exit()');
      if (LIBXML_VERSION && extension_loaded('xmlwriter')) {
        $model = new YML;      
        if(URL::get('filename')){
          if(!$model->downloadYml(URL::get('filename'))){
              $response = array(
                'data' => array(),
                'status' => 'error',
                'msg' => '  ',
              );
              echo json_encode($response);            
          };
        }else{    
          $model->exportToYml();        
        }
      } else {
        $response = array(
          'data' => array(),
          'status' => 'error',
          'msg' => '  PHP : xmlwriter',
        );
        echo json_encode($response);
      }
    }
    if (URL::get('csvuser')) {
      USER::AccessOnly('1,4','exit()');
      USER::exportToCsvUser();
    }
    if ($orderId = URL::get('getOrderPdf')) {
      $model = new Models_Order;
      $model->getPdfOrder($orderId, URL::get('layout'));
    }

    if ($orderId = URL::get('getExportCSV')) {
      USER::AccessOnly('1,4','exit()');
      $model = new Models_Order;
      $model->getExportCSV($orderId);
    }
    if (URL::get('csvorder')) {
      USER::AccessOnly('1,4,3','exit()');
      $model = new Models_Order();
      $model->exportToCsvOrder();
    }
    if (URL::get('csvorderfull')) {
      USER::AccessOnly('1,4,3','exit()');
      $model = new Models_Order();
      $model->exportToCsvOrder(false, true);
    }
    $loginAttempt = (int) LOGIN_ATTEMPT?LOGIN_ATTEMPT:5;
    unset($_POST['capcha']);
    if (($_SESSION['loginAttempt'] >= 2 )&& ($_SESSION['loginAttempt'] < $loginAttempt)) {
      if (!empty($_POST['email'])||!empty($_POST['pass'])||!empty($_POST['capcha'])) {
        $msgError = '<span class="msgError">'.
        '    !   .'.
        '   : '.($loginAttempt - $_SESSION['loginAttempt']).'</span>';
      }
      $checkCapcha = '<div class="checkCapcha">
        <img style="margin-top: 5px; border: 1px solid gray;" src = "'.SITE.'/'.'captcha.html" width="140" height="36">
        <div>   :<span class="red-star">*</span> </div>
        <input type="text" name="capcha" class="captcha"></div>';
    } elseif (($_SESSION['loginAttempt'] >= $loginAttempt)){  
      $msgError = '<span class="msgError">'.
            '     '.
            '  15 .        .</span>';
    }
    $this->data = array(
      'staticMenu' => MG::getSetting('staticMenu'),
      'themeBackground' => MG::getSetting('themeBackground'),
      'themeColor' => MG::getSetting('themeColor'),
      'languageLocale' => MG::getSetting('languageLocale'),
      'informerPanel' => MG::createInformerPanel(),
      'msgError' => $msgError ? $msgError : '',
      'checkCapcha' => $checkCapcha ? $checkCapcha : ''
    );
    if(MG::getSetting('autoGeneration')=='true') {
      $filename = 'sitemap.xml';      
      $create = true;
      if (file_exists($filename)) { 
        $siteMaptime =  filemtime($filename); 
        $days = MG::getSetting('generateEvery') *24*60*60;
        
        if (time() - $siteMaptime >= $days) {
          $create = true;
        } else {
          $create = false;
        }        
      }
      if ($create) {
        Seo::autoGenerateSitemap();
      }
    }    
    $this->pluginsList = PM::getPluginsInfo();
    $this->lang = MG::get('lang');
    
    //!$checkLibs = MG::libExists()
//    if (true) {
     // ecли нет класса updata или контрольная сумма файла не совпадает, то 
     // удаление config и флаг в бд о том, что версия нелицензионная
      //$fileCont = file_get_contents(URL::getDocumentRoot().'mg-core/lib/updata.php');
      //$fileCont = str_replace(array("", "", "", "", "",), "", $fileCont);
      //$fileCont = iconv("Windows-1251","UTF-8",$fileCont);
      //!class_exists('Updata')||!(method_exists(Updata, 'updataSystem'))||(dechex(crc32($fileCont))!='a6e3d438'&&dechex(crc32($fileCont))!='707bfd3'&&dechex(crc32($fileCont))!='ae5e85a4'&&dechex(crc32($fileCont))!='ce78ce4d')
//      if (true) {
//        $url = UPDATE_SERVER.'/updataserver';
//        $post = 'invalid=1'.
//          '&sName='.$_SERVER['SERVER_NAME'];
//        $ch = curl_init();
//        curl_setopt($ch, CURLOPT_URL, $url);
//        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
//        curl_setopt($ch, CURLOPT_HEADER, false);
//        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//        curl_setopt($ch, CURLOPT_POST, true);
//        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
//        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
//        $res = curl_exec($ch);
//        curl_close($ch);
//        $data = json_decode($res, true);
//        !$data['remove'] == '1'
//        if (false) {
//          unlink('config.ini');
//          $this->fakeKey = 'Движок не функционирует из-за нарушения защитных файлов - публичная часть будет недоступна.' ;
//          if (!MG::getSetting('trialVersionStart')) {
//            DB::query('INSERT INTO `'.PREFIX.'setting` (`id`, `option`, `value`, `active`, `name`) VALUES (NULL, "trialVersionStart", "true1", "N", "")'); 
//          }
//          if (!MG::getSetting('trialVersion')) {
//            $sql = 'INSERT INTO `'.PREFIX.'setting` (`id`, `option`, `value`, `active`, `name`) '
//              . 'VALUES (NULL, "trialVersion","Движок не функционирует из-за нарушения защитных файлов - публичная часть будет недоступна.", "N", "")';
//            DB::query($sql); 
//          } else {
//            DB::query('UPDATE `'.PREFIX.'setting` SET '
//              . '`value` = "Движок не функционирует из-за нарушения защитных файлов - публичная часть будет недоступна." WHERE `option`= "trialVersion"'); 
//          }
//          return false;
//        }      
//      }    
//
//      MG::getSetting('trialVersionStart')=='true1'
//      if (false) {
//        DB::query('DELETE FROM `'.PREFIX.'setting` WHERE `option`= "trialVersionStart"');
//        DB::query('DELETE FROM `'.PREFIX.'setting` WHERE `option`= "trialVersion"');
//      }
//      
      //require_once(URL::getDocumentRoot()."mg-core/lib/updata.php");
    
      //$newVer = Updata::checkUpdata(false, true);
      
      //$this->newVersion = $newVer['lastVersion'];
      //$this->fakeKey = MG::getSetting('trialVersion') ? MG::getSetting('trialVersion') : '' ;
    //}
  }
}