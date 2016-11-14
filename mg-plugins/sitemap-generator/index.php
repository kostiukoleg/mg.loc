<?php

/*
  Plugin Name: Генератор карты сайта
  Description: Плагин создает карту сайта в формате XML и сохраняет ее в корне сайта для увеличения скорости индексации.
  Author: Avdeev Mark
  Version: 1.0.6
 */

new SitemapGenerator;

class SitemapGenerator {

  private static $lang = array(); // массив с переводом плагина 
  private static $pluginName = ''; // название плагина (соответствует названию папки)
  private static $path = ''; //путь до файлов плагина 

  public function __construct() {
    
    mgActivateThisPlugin(__FILE__, array(__CLASS__, 'activate')); //Инициализация  метода выполняющегося при активации  
    mgAddAction(__FILE__, array(__CLASS__, 'pageSettingsPlugin')); //Инициализация  метода выполняющегося при нажатии на кнопку настроек плагина     
    
    self::$pluginName = PM::getFolderPlugin(__FILE__);
    self::$lang = PM::plugLocales(self::$pluginName);    
    include('mg-admin/locales/'.MG::getSetting('languageLocale').'.php');
    $lang =  array_merge($lang,self::$lang);
    self::$lang = $lang;
    self::$path = PLUGIN_DIR.self::$pluginName;
            
  }

  
  /**
   * Метод выполняющийся при активации палагина 
   */
  static function activate() {
 
  }

  
  /**
   * Метод выполняющийся перед генераццией страницы настроек плагина
   */
  static function preparePageSettings() {
    echo '
      <link rel="stylesheet" href="'.SITE.'/'.self::$path.'/css/style.css" type="text/css" />  
      <script type="text/javascript">     
        includeJS("'.SITE.'/'.self::$path.'/js/script.js");  
      </script> 
    ';
  }
   

  /**
   * Выводит страницу настроек плагина в админке
   */
  static function pageSettingsPlugin() {
    $lang = self::$lang;
    $pluginName = self::$pluginName;
     
    $filename = 'sitemap.xml'; 
    $msg = '<span style="color:rgb(236, 22, 22)">Файл sitemap.xml не создан!</span>';
    if (file_exists($filename)) { 
      $msg = "В последний раз файл <span style='color:green'>$filename</span> был изменен: <span class='date-site-map' style='color:blue'>" . MG::dateConvert(date ("d.m.Y", filemtime($filename)), true ).' г.</span>'; 
    } 
    
    self::preparePageSettings();
    include('pageplugin.php');
  }

}