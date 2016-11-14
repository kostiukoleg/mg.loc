<?php

/**
 * Класс Urlrewrite - предназначен для работы с обработкой адресов ссылок.
 * Доступен из любой точки программы.
 * Реализован в виде синглтона, что исключает его дублирование.
 *
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Libraries
 */
class Urlrewrite{

  static private $_instance;
  static private $seoData;

  private function __construct(){
    self::$seoData = array();   
    
    if($arInfo = self::getUrlRewriteData()){
      $parseUrl = parse_url($arInfo['url']);
      $_SERVER['REQUEST_URI'] = $parseUrl['path'].'?'.$parseUrl['query'];
      $arQuery = array();
      parse_str($parseUrl['query'], $arQuery);
      $_REQUEST = array_merge($_REQUEST, $arQuery);
      $_GET = array_merge($_GET, $arQuery);

      self::$seoData = array(
        'titeCategory' => $arInfo['titeCategory'],
        'cat_desc' => $arInfo['cat_desc'],
        'meta_title' => $arInfo['meta_title'],
        'meta_keywords' => $arInfo['meta_keywords'],
        'meta_desc' => $arInfo['meta_desc'],
      );
    }
    
  }

  /**
   * Возвращает объект класса, предварительно создав его, если этого еще не было сделано.
   * 
   * @return Urlrewrite - объект класса Urlrewrite
   */
  public static function getInstance(){
    if(is_null(self::$_instance)){
      self::$_instance = new self();
    }

    return self::$_instance;
  }

  /**
   * Инициализирует единственный объект данного класса.
   */
  public static function init(){
    self::getInstance();
  }

  /**
   * Возвращает массив данных о текущей странице
   * @return array
   */
  public static function getSeoDataFotUrl(){
    return self::$seoData;
  }

  /**
   * Возвращает данные о перенаправлении из записи с переданным идентификатором,
   * или для текущей страницы, если идентификатор не передан
   * 
   * @param int $id
   * @return array
   */
  public static function getUrlRewriteData($id = 0){
    $urlRewriteData = array();
    $where = '';
    
    if(URL::getClearUri() == '/modificatoryInc'){
      return $urlRewriteData;
    }
    
    if($id > 0){
      $where = '`id` = '.DB::quote(intval($id), true);
    }else{
      $url = URL::getClearUri();
      $url = trim($url, "/");  

      $where = '`short_url` = '.DB::quote($url).'AND `activity` = 1';
    }

    $sql = "SELECT * "
            ."FROM `".PREFIX."url_rewrite` "
            ."WHERE ".$where;

    if($dbRes = DB::query($sql)){
      if($arFields = DB::fetchAssoc($dbRes)){              
        foreach($arFields as $key => $value){
          $urlRewriteData[$key] = htmlspecialchars_decode($value);
        }                
      }
    }

    return $urlRewriteData;
  }

  /**
   * Изменяет или добавляет запись о странице с применеными фильтрами
   * 
   * @param array $arFields
   * @return array
   */
  public static function setUrlRewrite($arFields){
    $maskField = array('titeCategory','cat_desc','meta_title','meta_keywords','meta_desc');
    
    foreach($arFields as $key => $value){
      if(in_array($key, $maskField)){
        $value = htmlspecialchars_decode($value);
        $arFields[$key] = htmlspecialchars($value);
      }
    }

    $parseUrl = parse_url($arFields['short_url']);
    $arFields['short_url'] = (empty($parseUrl['query'])) ? $parseUrl['path'] : $parseUrl['path'].'?'.$parseUrl['query'];
    
    $arFields['url'] = htmlspecialchars($arFields['url']);    
    $arFields['short_url'] = trim($arFields['short_url'], "/");
    $arFields['short_url'] = htmlspecialchars($arFields['short_url']);        

    if(!empty($arFields['id'])){
      $sql = "UPDATE `".PREFIX."url_rewrite` SET ".DB::buildPartQuery($arFields)
              ." WHERE `id` = ".DB::quote($arFields['id'], true);  
      
      DB::query($sql);
    }else{
      $sql = "INSERT INTO `".PREFIX."url_rewrite` SET ";
      
      if(DB::buildQuery($sql, $arFields)){
        $arFields['id'] = DB::insertId();
      }
    }

    return $arFields;
  }

  /**
   * Устанавливает активность записи о странице с примененными фильтрами
   * 
   * @param int $id
   * @param int $activity
   * @return boolean
   */
  public static function setActivity($id, $activity){
    $sql = "UPDATE `".PREFIX."url_rewrite` "
            ."SET `activity` = ".DB::quote($activity, true)." "
            ."WHERE `id` = ".DB::quote(intval($id), true);

    if(DB::query($sql)){
      return true;
    }

    return false;
  }
  
  /**
   * Удаляет запись о странице с примененными фильтрами
   * 
   * @param int $id
   * @return boolean
   */
  public static function deleteRewrite($id){
    $sql = "DELETE "
            ."FROM `".PREFIX."url_rewrite` "
            ."WHERE `id` = ".DB::quote(intval($id), true);
    
    if(DB::query($sql)){
      return true;
    }

    return false;
  }

}
