<?php

/*
  Plugin Name: Скидочные промокоды
  Description: Плагин позволяет сделать скидку с помощью ввода специального промокода. Разместите следующий шорткод в том месте где ужно ввести промокод [promo-code].
  Author: Avdeev Mark
  Version: 2.2.3
 */

new PromoCode;

class PromoCode {

  private static $lang = array(); // массив с переводом плагина 
  private static $pluginName = ''; // название плагина (соответствует названию папки)
  private static $path = ''; //путь до файлов плагина 
  public static $percent = false;
  public function __construct() {

    mgActivateThisPlugin(__FILE__, array(__CLASS__, 'activate')); //Инициализация  метода выполняющегося при активации  
    mgAddAction(__FILE__, array(__CLASS__, 'pageSettingsPlugin')); //Инициализация  метода выполняющегося при нажатии на кнопку настроект плагина  
    mgAddShortcode('promo-code', array(__CLASS__, 'handleShortCode')); // Инициализация шорткода [promo-code] - доступен в любом HTML коде движка.       
    mgAddAction('models_cart_applycoupon', array(__CLASS__, 'applyCoupon'), 1);
    
    self::$pluginName = PM::getFolderPlugin(__FILE__);
    self::$lang = PM::plugLocales(self::$pluginName);    
    include('mg-admin/locales/'.MG::getSetting('languageLocale').'.php');
    $lang =  array_merge($lang,self::$lang);
    self::$lang = $lang;
    self::$path = PLUGIN_DIR.self::$pluginName;

    if (!URL::isSection('mg-admin')) { // подключаем CSS плагина для всех страниц, кроме админки
      mgAddMeta('<link rel="stylesheet" href="'.SITE.'/'.self::$path.'/css/style.css" type="text/css" />');
    }
    
   // mgAddMeta('<link rel="stylesheet" href="'.SITE.'/mg-admin/design/css/jquery-ui.css">');    
   // mgAddMeta('<script type="text/javascript" src="'.SITE.'/mg-core/script/jquery-ui-1.10.3.custom.min.js"></script>');         
  }

  
  /**
   * Метод выполняющийся при активации палагина 
   */
  static function activate() {
    self::createDateBase();
  }

  
  /**
   * Метод выполняющийся перед генераццией страницы настроек плагина
   */
  static function preparePageSettings() {
    echo '
      <link rel="stylesheet" href="'.SITE.'/mg-admin/design/css/jquery-ui.css">
      <link rel="stylesheet" href="'.SITE.'/'.self::$path.'/css/style.css" type="text/css" />     
      <link rel="stylesheet" href="'.SITE.'/'.self::$path.'/css/style.css" type="text/css" />  
      <script type="text/javascript">     
        includeJS("'.SITE.'/'.self::$path.'/js/script.js");  
      </script> 
    ';
  }
   
  
  /**
   * Создает таблицу плагина в БД
   */
  static function createDateBase() {
    // Запрос для проверки, был ли плагин установлен ранее.
    $exist=false;
    $result = DB::query('SHOW TABLES');
    while($row = DB::fetchArray($result)){     
      if( PREFIX.self::$pluginName==$row[0]){
        $exist=true;
      };
    }   
    
    DB::query("
     CREATE TABLE IF NOT EXISTS `".PREFIX.self::$pluginName."` (
       `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Порядковый номер',      
       `add_datetime` DATETIME NOT NULL COMMENT 'Дата добавления',
       `from_datetime` DATETIME NOT NULL COMMENT 'Нижняя граница',
       `to_datetime` DATETIME NOT NULL COMMENT 'Верхняя граница',
	     `code` text NOT NULL COMMENT 'Код',
       `percent` int(11) NOT NULL COMMENT '% скидка',
       `desc` text NOT NULL COMMENT 'Описание', 
       `sort` int(11) NOT NULL COMMENT 'Порядок',
       `invisible` int(1) NOT NULL COMMENT 'Видимость',
       PRIMARY KEY (`id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;");
        
    // Если плагин впервые активирован, то задаются настройки по умолчанию 
    if (!$exist) {    
      DB::query("        
       INSERT INTO `".PREFIX.self::$pluginName."` 
         (`id`, `add_datetime`, `from_datetime`, `to_datetime`, 
         `code`, `percent`, `desc`, `sort`, `invisible`)
       VALUES
         (1, '".date("Y-m-d")."', '".date("Y-m-d")."','".date("Y-m-d", time()+60*60*24*7)."',
         'DEFAULT-DISCONT', '50', 'Скидка по умолчанию', '1', '1');
      ");    
    }
  }

  /**
   * Выводит страницу настроек плагина в админке
   */
  static function pageSettingsPlugin() {
    $lang = self::$lang;
    $pluginName = self::$pluginName;
    
    //фильтры 
     $property = array(
      'code' => array(
        'type' => 'text',
        'label' => 'Промокод',
        'value' => !empty($_POST['id']) ? $_POST['id'] : null,
      ),

      'invisible' => array(
        'type' => 'select',
        'option' =>array('null'=>'Выберите','1'=>'Активные', 0=>'Неактивые'),
        'selected' => (!empty($_POST['invisible'])||$_POST['invisible']==='0')?$_POST['invisible']:'null', // Выбранный пункт (сравнивается по значению)
        'label' => 'Статус'
      ),

      'sorter' => array(
        'type' => 'hidden', //текстовый инпут
        'label' => 'сортировка по полю',
        'value' => !empty($_POST['sorter'])?$_POST['sorter']:null,
      ),
    );

    if(isset($_POST['applyFilter'])){
      $property['applyFilter'] = array(
        'type' => 'hidden', //текстовый инпут
        'label' => 'флаг примения фильтров',
        'value' => 1,
      );
    }

  
    $filter = new Filter($property);

    $arr = array(
        'code'=> !empty($_POST['code']) ? $_POST['code'] : null,
        'invisible'=> (!empty($_POST['invisible'])||$_POST['invisible']==='0')?$_POST['invisible']:'null',
    );
    
    $userFilter = $filter->getFilterSql($arr, explode('|',$_POST['sorter']));
 
    $sorterData = explode('|',$_POST['sorter']);

    if($sorterData[1]>0){
      $sorterData[3] = 'desc';          
    } else{
      $sorterData[3] = 'asc';   
    }

    $page=!empty($_POST["page"])?$_POST["page"]:0;//если был произведен запрос другой страницы, то присваиваем переменной новый индекс
    $countPrintRowsBackRing = MG::getSetting('countPrintRowsPromoCode') ? MG::getSetting('countPrintRowsPromoCode') :10 ;

    if(empty($_POST['sorter'])){
        if(empty($userFilter)){ $userFilter .= ' 1=1 ';}
        $userFilter .= "  ORDER BY `add_datetime` DESC";
    }
 
    $sql = "
      SELECT * FROM `".PREFIX.self::$pluginName."`
      WHERE ".$userFilter."
    ";

    $navigator = new Navigator($sql, $page, $countPrintRowsBackRing); //определяем класс
    $entity = $navigator->getRowsSql();
    $pagination = $navigator->getPager('forAjax');   
    $filter = $filter->getHtmlFilter();
    //фильтры конец        
 
    self::preparePageSettings();
    include('pageplugin.php');
  }

  
  /**
   * Получает из БД записи
   */
  static function getEntity($count=1) {
    $result = array();
    $sql ="SELECT * FROM `".PREFIX.self::$pluginName."` ORDER BY sort ASC";
    if ($_POST["page"]){
      $page = $_POST["page"]; //если был произведен запрос другой страницы, то присваиваем переменной новый индекс
    }
    $navigator = new Navigator($sql, $page, $count); //определяем класс
    $entity = $navigator->getRowsSql();
    $pagination = $navigator->getPager('forAjax');
    $result = array(
      'entity' => $entity,
      'pagination' => $pagination
    );
    return $result;
  }

   /**
   * Получает количество активных записей
   */
  static function getEntityActive() { 
    $exist=false;
    $result = DB::query('SHOW TABLES');
    while($row = DB::fetchArray($result)){     
      if( PREFIX.self::$pluginName==$row[0]){
        $exist=true;
      };
    }     
   
    if ($exist){
      $sql ="SELECT count(id) as count FROM `".
        PREFIX.self::$pluginName."` WHERE invisible = 1 ORDER BY sort ASC";
      $res = DB::query($sql);
      if($count = DB::fetchAssoc($res)){
        return $count['count'];
      }    
    }
    return 0;
  }
  
  
  /**
   * Обработчик шотркода вида [promo-code] 
   * выполняется когда при генерации страницы встречается [promo-code] 
   */
  static function handleShortCode() {    
    $html = '
    <form action="'.SITE.'/cart" method="post" class="promo-form">
      <span>Промокод:</span>
      <input type="text" class="input-coupon" name="couponCode"  value = "'.(!empty($_SESSION['couponCode'])? $_SESSION['couponCode'] : '' ).'"/>
      <button type="submit" class="default-btn" name="coupon" value="Применить код">Применить код</button>
    </form>
    ';      
     if (!self::checkCoupon($_SESSION['couponCode'])&&!empty($_SESSION['couponCode'])) {
      $html .= '<div id="msg-about-code">Данный код не действителен.</div>';
     }
    return $html;
  }
  /**
	* Проверяет действиетльный ли данный код
	*/
  static public function checkCoupon($code){  
 
    $sql ="SELECT * FROM `".
      PREFIX.self::$pluginName."` WHERE 
      `code` = ".DB::quote($code)."
       AND`invisible` = 1 
       AND now() >= `from_datetime`
       AND now() <= `to_datetime`     
    ";
    
    $res = DB::query($sql);
    if($count = DB::fetchAssoc($res)){
      return true;
    }    
    else {
      return false;
    }
  }

  /**
   * Применяет купонную скидку по промокоду
   */
  static public function applyCoupon($args) {
    $code = $args['args'][0];
    $price = $args['args'][1];
    $product = $args['args'][2];
    $percent = 0;
    if ($code) {
      if (!self::$percent) {
        $sql = "SELECT `percent` FROM `".
          PREFIX.self::$pluginName."` WHERE 
      `code` = ".DB::quote($code)."
       AND`invisible` = 1 
       AND now() >= `from_datetime`
       AND now() <= `to_datetime`     
    ";

        $res = DB::query($sql);
        if ($count = DB::fetchAssoc($res)) {
          $percent = $count['percent'];             
        }
        self::$percent = $percent;
      } else {
        $percent = self::$percent;
      }
      $args['result'] = $price - $price * $percent / 100;
      return round($args['result'], 2);
    }
    return $args['result'];
  }

}