<?php

/*
  Plugin Name: Вопрос-ответ
  Description: Плагин часто задаваемых вопросов. После подключения плагина становится доступной страница [sitename]/faq.php , на которой отображается список вопросов.
  Author: Дарья Чуркина Avdeev Mark
  Version: 1.0.1
 */

new Faq;

class Faq {

  private static $lang = array(); // массив с переводом плагина 
  private static $pluginName = ''; // название плагина (соответствует названию папки)
  private static $path = ''; //путь до файлов плагина 

  public function __construct() {

    mgActivateThisPlugin(__FILE__, array(__CLASS__, 'activate')); //Инициализация  метода выполняющегося при активации  
    mgAddAction(__FILE__, array(__CLASS__, 'pageSettingsPlugin')); //Инициализация  метода выполняющегося при нажатии на кнопку настроект плагина  
    // mgAddShortcode('faq', array(__CLASS__, 'handleShortCode')); // Инициализация шорткода [faq] - доступен в любом HTML коде движка.    

    self::$pluginName = PM::getFolderPlugin(__FILE__);
    self::$lang = PM::plugLocales(self::$pluginName);
    self::$path = PLUGIN_DIR.self::$pluginName;


    if (!URL::isSection('mg-admin')) { // подключаем CSS плагина для всех страниц, кроме админки
      mgAddMeta('<link rel="stylesheet" href="'.SITE.'/'.self::$path.'/css/style.css" type="text/css" />');
    }

    mgAddMeta('<script type="text/javascript" src="'.SITE.'/'.self::$path.'/js/faq.js"></script>');
  }

  /**
   * Метод выполняющийся при активации палагина 
   */
  static function activate() {
    MG::setOption(array('option' => 'countPrintRowsQuest', 'value' => 10));

    // Файл для вывода результата копируется в mg-pages

    $file = PLUGIN_DIR.'faq/viewfaq.php';
    $newfile = 'faq.php';
    if (!file_exists(PAGE_DIR.$newfile)) {
      copy($file, PAGE_DIR.$newfile);
    }
    self::createDateBase();
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
   * Создает таблицу плагина в БД
   */
  static function createDateBase() {
    DB::query("
     CREATE TABLE IF NOT EXISTS `".PREFIX.self::$pluginName."` (     
      `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Порядковый номер записи',     
      `question` text NOT NULL COMMENT 'Вопрос',
      `answer` text NOT NULL COMMENT 'Ответ',      
      `sort` int(11) NOT NULL COMMENT 'Порядок',
       PRIMARY KEY (`id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;");
  }

  /**
   * Выводит страницу настроек плагина в админке
   */
  static function pageSettingsPlugin() {
    $lang = self::$lang;
    $pluginName = self::$pluginName;
    $countPrintRowsQuest = MG::getSetting('countPrintRowsQuest');
    $res = self::getEntity($countPrintRowsQuest);
    $entity = $res['entity'];
    $pagination = $res['pagination'];
    self::preparePageSettings();
    include('pageplugin.php');
  }

  /**
   * Получает из БД записи
   */
  static function getEntity($count = 1) {
    $result = array();
    $sql = "SELECT * FROM `".PREFIX.self::$pluginName."` ORDER BY sort DESC";
    if ($_POST["page"]) {
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
   * Получает из БД все записи
   */
  static function getAllEntity() {
    $array = array();
    $sql = "SELECT * FROM `".PREFIX.self::$pluginName."` ORDER BY sort DESC";
    $result = DB::query($sql);
    while ($row = DB::fetchAssoc($result)) {
      $array[] = $row;
    }
    return $array;
  }

  /**
   * Функция вывода на экран всех вопросов и ответов
   */
  static function handleShortCode() {
    $entities = self::getAllEntity();
    $core = "";
    foreach ($entities as $rows) {
      $quest = $rows['question'];
      $ans = $rows['answer'];
      $id = $rows['id'];
      $core .= "<div class='faq-item'>
        <div class='question' data-question-id='".$id."'><a href = '#".$id."'>".$quest."</a></div> 
				  <div class='answer' data-answer-id='".$id."' style='display:none'><p >".$ans."</p></div>
            </div>
		    ";
    }
    if ($core == "") {
      $core = "<p> Пока вопросов нет </p>";
    }
    $html = " 
    <div class='wrapper-faq'>
      <div class='header-faq'>
        <h1 class='title-faq'>
          Часто задаваемые вопросы
        </h1>
      </div>
      <div class='content-faq'>".$core."
    </div>
    </div>";
    return $html;
  }

}
