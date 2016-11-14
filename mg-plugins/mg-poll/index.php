<?php

/*
  Plugin Name: Опросы
  Description: После установки, скопируйте шорткод [mg-poll id='номер опроса'] и вставьте его в то место, где Вы хотите видеть опрос. Если Вы хотите вывести рядом несколько опросов, то укажите в кавычках несколько номеров, например [mg-poll id='1,2,3'].
  Author: Osipov Ivan
  Version: 1.0.0
 */

new MgPoll;

class MgPoll{

  private static $lang = array(); // массив с переводом плагина 
  private static $pluginName = ''; // название плагина (соответствует названию папки)
  private static $path = ''; //путь до файлов плагина 
  private static $arOptions = array();

  public function __construct(){
    mgActivateThisPlugin(__FILE__, array(__CLASS__, 'activate')); //Инициализация  метода выполняющегося при активации  
    mgAddAction(__FILE__, array(__CLASS__, 'pageSettingsPlugin')); //Инициализация  метода выполняющегося при нажатии на кнопку настроек плагина  
    mgAddShortcode('mg-poll', array(__CLASS__, 'handlePollView')); // Инициализация шорткода [mg-poll] - доступен в любом HTML коде движка. 

    self::$pluginName = PM::getFolderPlugin(__FILE__);
    self::$lang = PM::plugLocales(self::$pluginName);
    self::$path = PLUGIN_DIR.self::$pluginName;
    
  }

  /**
   * Метод выполняющийся при активации палагина 
   */
  static function activate(){
    USER::AccessOnly('1,4','exit()');
    self::createTables();
    self::setDefaultOptions();
  }
  
  /**
   * Метод выполняющийся перед генераццией страницы настроек плагина
   */
  static function preparePageSettings(){
    USER::AccessOnly('1,4','exit()');
    echo '   
      <link rel="stylesheet" href="'.SITE.'/'.self::$path.'/css/style.css" type="text/css" /> 
        <link rel="stylesheet" href="'.SITE.'/'.self::$path.'/css/timepicker.min.css" type="text/css" />
      <script type="text/javascript">
        includeJS("'.SITE.'/'.self::$path.'/js/admin.js");  
        includeJS("'.SITE.'/'.self::$path.'/js/jquery-ui-timepicker-addon.js");
      </script> 
    ';
  }

  /**
   * Создает таблицы плагина в БД
   */
  private static function createTables(){
    
    /*Таблица вопросов*/
    DB::query('
      CREATE TABLE IF NOT EXISTS `'.PREFIX.'poll_question` (
        `id` INT(11) AUTO_INCREMENT NOT NULL,
        `question` VARCHAR(255) NOT NULL,
        `date_active_from` TIMESTAMP NOT NULL,
        `date_active_to` TIMESTAMP NOT NULL,
        `activity` TINYINT(1) NOT NULL DEFAULT 1,
        PRIMARY KEY(`id`)
      ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;');
    
    /*Таблица ответов*/
    DB::query('
      CREATE TABLE IF NOT EXISTS `'.PREFIX.'poll_answer` (
        `id` INT(11) AUTO_INCREMENT NOT NULL,
        `question_id` INT(11) NOT NULL,
        `answer` VARCHAR(255) NOT NULL,
        `votes` INT(11) DEFAULT 0,
        PRIMARY KEY(`id`)
      ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;');
    
  }
  
  /**
   * Устанавливаем дефолтные настройки
   */
  private static function setDefaultOptions(){
    USER::AccessOnly('1,4','exit()');
    
//    if(MG::getSetting(self::$pluginName.'-option') == null){
//      $arPluginParams = array(
//        'theme' => '04',
//        'services' => 'vkontakte, odnoklassniki, facebook, twitter, google, moimir',
//        'size' => 'big',
//        'shape' => 'square',
//        'multiline' => false,
//        'orientation' => 'horizontal',
//        'counter' => true,
//        'use-background' => true,
//        'background' => '#ebebeb',
//      );
//      MG::setOption(array('option' => self::$pluginName.'-option', 'value' => addslashes(serialize($arPluginParams))));
//    }  
    
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
    self::$arOptions = $options;

    $entity = self::getQuestionList();
    
    self::preparePageSettings(); 
    include('pageplugin.php');
  }
  
  private static function getQuestionList($count = 20){
    $result = array();
    
    if ($_REQUEST["page"]){
      $page = $_REQUEST["page"]; //если был произведен запрос другой страницы, то присваиваем переменной новый индекс
    }
    
    /*
    $sql = '
      SELECT q.*, SUM(a.votes) 
      FROM `'.PREFIX.'poll_question` q
      LEFT JOIN `'.PREFIX.'poll_answer` a';
     */
    
    $sql = '
      SELECT `q`.*, SUM(`a`.`votes`) as `votes_count`
      FROM `'.PREFIX.'poll_question` `q`
        LEFT JOIN `'.PREFIX.'poll_answer` `a`
          ON `q`.`id` = `a`.`question_id`
      GROUP BY `q`.`id`
      ORDER BY `q`.`id` DESC';
    
    $navigator = new Navigator($sql, $page, $count); //определяем класс
    $result = $navigator->getRowsSql();
    
    return $result;
  }
  
  private static function getQuestionById($id){
    $result = array();
    
    $sql = '
      SELECT `id`, `question` 
      FROM `'.PREFIX.'poll_question` 
      WHERE `id` = '.DB::quote($id, true);
    $dbRes = DB::query($sql);
    
    if($question = DB::fetchAssoc($dbRes)){
      $result = $question;
      $result['answers'] = array();
      
      $sql = '
        SELECT * 
        FROM `'.PREFIX.'poll_answer` 
        WHERE `question_id` = '.DB::quote($result['id'], true).' 
        ORDER BY `id` ASC';
      $dbAnswers = DB::query($sql);
      $votes = 0;
      
      while($answer = DB::fetchAssoc($dbAnswers)){
        $result['answers'][] = $answer;
        $votes += $answer['votes'];
      }
      
      $result['votes'] = $votes;
    }
    
    return $result;
  }
  
  public static function handlePollView($args){
    $result = '';    
    
    $arIds = explode(',', $args['id']);
    
    $ds = DIRECTORY_SEPARATOR;
    $templateName = MG::getSetting('templateName');
    $realDocumentRoot = str_replace($ds.'mg-plugins'.$ds.self::$pluginName, '', dirname(__FILE__));
    $templateFolder = $realDocumentRoot.$ds.'mg-templates'.$ds.$templateName
                      .$ds.'mg-plugins'.$ds.self::$pluginName.$ds;
    $pluginsFolder = $realDocumentRoot.$ds.PLUGIN_DIR.self::$pluginName.$ds;
    
    ob_start();
    
    if(file_exists($templateFolder.'js/script.js')){
      $result .= '<script src="'.SITE.'/mg-templates/'.$templateName.'/'.PLUGIN_DIR.self::$pluginName.'/js/script.js"></script>';
    }else{
      $result .= '<script src="'.SITE.'/'.self::$path.'/js/script.js"></script>';
    }

    if(file_exists($templateFolder.'css/style.css')){
      $result .= '<link rel="stylesheet" href="'.SITE.'/mg-templates/'.$templateName.'/'.PLUGIN_DIR.self::$pluginName.'/css/style.css" type="text/css" />';
    }else{
      $result .= '<link rel="stylesheet" href="'.SITE.'/'.self::$path.'/css/style.css" type="text/css" />';
    }
    
    foreach($arIds as $id){
      $data = self::getQuestionById($id);
      
      if($_COOKIE['MG_POLL_QUESTION_'.$id]){
        if(file_exists($templateFolder.'poll-result.php')){
          include($templateFolder.$ds.'poll-result.php');
        }else{
          include($pluginsFolder.'views'.$ds.'poll-result.php');
        }
      }else{
        if(file_exists($templateFolder.'poll-question.php')){
          include($templateFolder.'poll-question.php');
        }else{
          include($pluginsFolder.'views'.$ds.'poll-question.php');
        }
      }
    }
    
    $result .= ob_get_contents();
    
    ob_end_clean();
    
    return $result;
  }
  
}