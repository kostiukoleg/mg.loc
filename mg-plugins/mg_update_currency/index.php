<?php

/*
  Plugin Name: Синхронизация курса с ЦБ
  Description: Плагин позволяет синхронизировать курсы валют с сайтом cbr.ru.<br />В плагине есть возможность настройки автоматической, периодической синхронизации. Также плагин позволяет установить наценку на курс.
  Author: Osipov Ivan
  Version: 1.3.0
*/

new updateCurrency;

class updateCurrency{

  private static $lang = array(); // массив с переводом плагина 
  private static $pluginName = ''; // название плагина (соответствует названию папки)
  private static $path = ''; //путь до файлов плагина 
  private static $arOptions = array();
  private static $maxExecTime = null;
  private static $startTime = null;

  public function __construct(){
    mgActivateThisPlugin(__FILE__, array(__CLASS__, 'activate')); //Инициализация  метода выполняющегося при активации  
    mgAddAction(__FILE__, array(__CLASS__, 'pageSettingsPlugin')); //Инициализация  метода выполняющегося при нажатии на кнопку настроек плагина  
//    mgAddAction('mg_gethtmlcontent', array(__CLASS__, 'autoUpdateProductPrice'), 1, 1);
    mgAddAction('mg_start', array(__CLASS__, 'autoUpdateHandle'));

    self::$pluginName = PM::getFolderPlugin(__FILE__);
    self::$lang = PM::plugLocales(self::$pluginName);
    self::$path = PLUGIN_DIR.self::$pluginName;
  }
  
  static function autoUpdateProductPrice($step=0, $update=false, $cron=false, $first=false){
    $result = $step['result'];
    $item2step = 100;
    
    $first = (!empty($_REQUEST['first']) || $first) ? true : false;
    
    if($first){
      self::$maxExecTime = min(30, @ini_get("max_execution_time"));
      if(empty(self::$maxExecTime)){
        self::$maxExecTime = 30;
      }
      self::$startTime = microtime(true);
    }
    
    if(URL::isSection('auto-update-currency') || $update){  
      $cron = (!empty($_REQUEST['cron']) || $cron) ? true : false;
      
      if(!$cron && !$update){
        exit();
      }
      
      $currency = MG::getSetting('currencyShopIso');
      $currencyRate = MG::getSetting('currencyRate');
      $currencyShopIso = MG::getSetting('currencyShopIso');  
      
      if(empty($step) || is_array($step)){
        $step = (empty($_REQUEST['step'])) ? 0 : intval($_REQUEST['step']);
      }
      // вычислим соотношение валют имеющихся в базе товаров к выбранной для замены
      // вычисление производится на основе имеющихся данных по отношению в  валюте магазина   
      
      DB::query('
       UPDATE `'.PREFIX.'product` SET 
         `currency_iso` = '.DB::quote($currencyShopIso).'
       WHERE `currency_iso` = "" ');

      $rate = $currencyRate[$iso];  
      foreach ($currencyRate as $key => $value){     
        if(!empty($rate)){
          $currencyRate[$key] = $value / $rate;                 
        }        
      }
      $currencyRate[$iso] = 1;
      
      $dbProdRes = DB::query('
        SELECT `id` 
        FROM `'.PREFIX.'product` 
        LIMIT '.($step*$item2step).','.$item2step);
      
      $dbProdVarRes = DB::query('
        SELECT `id` 
        FROM `'.PREFIX.'product_variant` 
        LIMIT '.($step*$item2step).','.$item2step);
      
      for($i=0;$i<$item2step;$i++){
        $prodRes = DB::fetchAssoc($dbProdRes);
        $prodVarRes = DB::fetchAssoc($dbProdVarRes);
        $prodIds[] = $prodRes['id'];
        $prodVarIds[] = $prodVarRes['id'];
      }
      
      $array_empty = array(null);
      $prodIds = array_diff($prodIds, $array_empty);
      $prodVarIds = array_diff($prodVarIds, $array_empty);

      foreach ($currencyRate as $key => $rate){
        if(!empty($prodIds)){
          $productUpdate = DB::query('UPDATE `'.PREFIX.'product` 
            SET `price_course`= ROUND(`price`*'.DB::quote((float)$rate,TRUE).',2)          
          WHERE currency_iso = '.DB::quote($key).' 
            AND id IN
           ('.implode(',', $prodIds).')');
        }

        if(!empty($prodVarIds)){
          $productVariantUpdate = DB::query('
          UPDATE `'.PREFIX.'product_variant` 
            SET `price_course`= ROUND(`price`*'.DB::quote((float)$rate,TRUE).',2)         
          WHERE currency_iso = '.DB::quote($key).'
            AND id IN ('.implode(',', $prodVarIds).')');
        }
      }
      
      $step++;
      
      if(empty($prodIds) && empty($prodVarIds)){
        if($cron){
          $backurl = $_REQUEST['backurl'];
          if(!empty($backurl)){
            header('Location: '.$backurl);
          }
          exit();
        }
        return false;
      }else{
        
        if(microtime(true)-self::$startTime < self::$maxExecTime-3){ 
          $step = self::autoUpdateProductPrice($step, true, $cron);
        }
        
        if($cron){
          $backurl = $_REQUEST['backurl'];
          $section = URL::getLastSection();
          
          if($section != 'auto-update-currency' && $section != 'auto-update-currency/' && empty($backurl)){
            $backurl = '&backurl='.URL::getUrl();
          }
          
          $backurl = '&backurl='.$backurl;
          header('Location: '.SITE.'/auto-update-currency?step='.$step.'&first=y&cron=y'.$backurl);
          exit();
        }
        return $step;
      }
    }
    
    return $result;
  }

  /**
   * Метод выполняющийся при активации палагина 
   */
  static function activate(){
    USER::AccessOnly('1,4','exit()');
    self::createDataBase();
  }
  
  /**
   * Метод выполняющийся перед генераццией страницы настроек плагина
   */
  static function preparePageSettings(){
    USER::AccessOnly('1,4','exit()');
    echo '   
      <link rel="stylesheet" href="'.SITE.'/'.self::$path.'/css/style.css" type="text/css" /> 
      <script type="text/javascript">
        includeJS("'.SITE.'/'.self::$path.'/js/script.js");  
      </script> 
    ';
  }

  /**
   * Создает таблицу плагина в БД
   */
  static function createDataBase(){
    USER::AccessOnly('1,4','exit()');
    
    if(MG::getSetting(self::$pluginName.'-option') == null){
      $arPluginParams = array(
        'margin' => '',
        'use_margin' => 'false',
        'use_auto_update' => 'true',
        'auto_update_price' => 'true',
        'auto_update_time' => 24,
      );
      MG::setOption(array('option' => self::$pluginName.'-option', 'value' => addslashes(serialize($arPluginParams))));
    }  
    
  }

  /**
   * Выводит страницу настроек плагина в админке
   */
  static function pageSettingsPlugin(){
    USER::AccessOnly('1,4','exit()');

    $lang = self::$lang;
    $pluginName = self::$pluginName;
    $option = MG::getSetting($pluginName.'-option');
    $option = stripslashes($option);
    $options = unserialize($option);
    
    $fileName = dirname(__FILE__).DIRECTORY_SEPARATOR."last_update.txt";
    
    if(file_exists($fileName)){
      $lastCurrencyUpdate = file_get_contents($fileName);
      $lastCurrencyUpdate = (strlen($lastCurrencyUpdate)>0)?$lastCurrencyUpdate:$lang['NOT_UPDATE'];
    }
    
    self::preparePageSettings(); 
    include('pageplugin.php');
  }
  
  /*
   * Загружаем файл
   */
  public static function getFileByUrl($url){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    $result = curl_exec($ch);
    curl_close($ch);
    
    return $result;
  }
  
  static function updateCurrency(){ 
    include 'xml2array.php';
    
    $xmlData = updateCurrency::getFileByUrl('http://www.cbr.ru/scripts/XML_daily.asp');
    
    if(empty($xmlData)){
      return false;
    }
    
    $arCurses = xml2array($xmlData, 0);
    
    $currencyRate = MG::getSetting('currencyRate');
    $arCurrency = array_keys($currencyRate);
    $currency = MG::getSetting('currencyShopIso');
    $option = MG::getSetting(self::$pluginName.'-option');
    $option = stripslashes($option);
    $options = unserialize($option);
    
    $newRate[$currency] = $currencyRate[$currency];
    
    foreach($arCurses['ValCurs']['Valute'] as $valute){
      if(in_array($valute['CharCode'], $arCurrency)){
        $newRate[$valute['CharCode']] = doubleval(str_replace(",", ".", $valute['Value']))/$valute['Nominal'];
      }
    }
    
    $currencyRate['RUR'] = 1/$newRate[$currency];
    $currencyRate[$currency] = 1;
    $currencyRateRur = doubleval($currencyRate['RUR']);
    $margin = 0;
    
    if($_POST['use_margin']!='false' && $options['use_margin']!='false'){
      if(isset($_POST['margin']) && intval($_POST['margin'])>0){
        $margin = intval($_POST['margin'])/100;
      }else{
        $margin = $options['margin']/100;
      }
    }
    
    foreach($newRate as $key=>$value){
      if(in_array($key, array('RUR', $currency))){
        continue;
      }
      
      $curRate = $value*$currencyRateRur;
      $currencyRate[$key] = $curRate+$curRate*$margin;
    }
    
    MG::setOption(array('option' => 'currencyRate', 'value' => addslashes(serialize($currencyRate))));
    
    return true;
  }
 
  static function autoUpdateHandle(){
    //не срабатывает в админке или если процесс уже запущен
    if(URL::isSection('mg-admin') || !empty($_SESSION['update_currency_process'])){
      return false;
    }
    
    $option = MG::getSetting(self::$pluginName.'-option');
    $option = stripslashes($option);
    $options = unserialize($option);
    
    if($options['use_auto_update'] == 'false'){
      return false;
    }
    
    $fileName = dirname(__FILE__).DIRECTORY_SEPARATOR."last_update.txt";
    
    if(file_exists($fileName)){
      $mtime = filemtime($fileName);
      $updateExpTime = $mtime+10*60; //вермя обновления задается в часах, приводим к секундам
      
      if(intval(date("G")) > 3 && time() < $updateExpTime){
        return false;
      }else{
        $_SESSION['update_currency_process'] = 1;
        
        if(updateCurrency::updateCurrency()){
          file_put_contents($fileName, date("d.m.Y H:i:s"));
          
          if(!empty($options['auto_update_price']) && $options['auto_update_price'] != 'false'){
            $product = new Models_Product();
            $product->updatePriceCourse(MG::getSetting('currencyShopIso'));
          }
        }
        
        unset($_SESSION['update_currency_process']);
      }
      
    }
    
  }
}