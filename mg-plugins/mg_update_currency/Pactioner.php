<?php

/**
 * Класс Pactioner наследник стандарного Actioner
 * Предназначен для выполнения действий,  AJAX запросов плагина 
 *
 * @author Osipov Ivan
 */
class Pactioner extends Actioner {

  static $pluginName = 'mg_update_currency';

  /**
   * Сохраняет  опции плагина
   * @return boolean
   */
  public function saveBaseOption() {
    USER::AccessOnly('1,4','exit()');
    $this->messageSucces = $this->lang['SAVE_BASE'];
    $this->messageError = $this->lang['NOT_SAVE_BASE'];
    if (!empty($_POST['data'])) {
      MG::setOption(array('option' => self::$pluginName.'-option', 'value' => addslashes(serialize($_POST['data']))));
    }   
    return true;
  }
  
  public function updateCurrency(){
    USER::AccessOnly('1,4','exit()');
    $this->messageSucces = $this->lang['CURRENCY_UPDATED'];
    $this->messageError = $this->lang['CURRENCY_NOT_UPDATED'];
    
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
    
    $fileName = dirname(__FILE__).DIRECTORY_SEPARATOR."last_update.txt";
    
    if(file_exists($fileName)){
      file_put_contents($fileName, date("d.m.Y H:i:s")); 
    }
    
    return true;
  }
  
  public function updatePrices(){
//    $step = intval($_POST['step']);
//    $nextstep = updateCurrency::autoUpdateProductPrice($step, true, false, true);
//    
//    if($nextstep){
//      $this->data['status'] = 'process';
//      $this->data['step'] = $nextstep;
//      $this->messageSucces = $this->lang['UPDATE_PRICES_PROCESS'];
//      return true;
//    }
    
    $fileName = dirname(__FILE__).DIRECTORY_SEPARATOR."last_update.txt";
    file_put_contents($fileName, date("d.m.Y H:i:s"));
    $product = new Models_Product();
    $product->updatePriceCourse(MG::getSetting('currencyShopIso'));
    
    $this->messageSucces = $this->lang['UPDATE_PRICES_SUCCESS'];
    $this->data['status'] = 'success';
    
    return true;
  }

}