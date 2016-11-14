<?php

/*
  Plugin Name: Блог
  Description: Плагин позволяет выводить на сайте статьи, с возможностью делить их на категории. После активации станет доступна страница "/blog/".<br />С помощью коментария "&lt;!--end-preview--&gt;" можно делить текст на текст анонса и подробное описание.<br />[blog-categories] - вывод списка категорий<br />[blog-category id=%d] - вывод списка статей из категории с идентификатором "%d".<br />[blog-category code=%s] - вывод списка статей из категории с url "%s".<br />[blog-article id=%d] - вывод статьи с идентификатором "%d".<br />[blog-article code=%s] - вывод статьи с url "%s".
  Author: Osipov Ivan
  Version: 1.1.4
 */

new Blog;

class Blog {

  private static $lang = array(); // массив с переводом плагина 
  private static $pluginName = ''; // название плагина (соответствует названию папки)
  private static $path = ''; //путь до файлов плагина 
  private static $arOptions = array();

  public function __construct(){
    mgActivateThisPlugin(__FILE__, array(__CLASS__, 'activate')); //Инициализация  метода выполняющегося при активации  
    //mgDeactivateThisPlugin(__FILE__, array(__CLASS__, 'deactivate'));
    mgAddAction(__FILE__, array(__CLASS__, 'pageSettingsPlugin')); //Инициализация  метода выполняющегося при нажатии на кнопку настроек плагина  
    mgAddAction('mg_gethtmlcontent', array(__CLASS__, 'viewSection'), 1, 1);
    mgAddShortcode('blog-categories', array(__CLASS__, 'handleBlogCategoriesShortCode')); // Инициализация шорткода [blog-categories] - доступен в любом HTML коде движка. 
    mgAddShortcode('blog-category', array(__CLASS__, 'handleBlogCategoryShortCode'));
    mgAddShortcode('blog-article', array(__CLASS__, 'handleBlogArticleShortCode'));
    mgAddAction('mg_start', array(__CLASS__, 'blogFeed'));

    self::$pluginName = PM::getFolderPlugin(__FILE__);
    self::$lang = PM::plugLocales(self::$pluginName);
    self::$path = PLUGIN_DIR.self::$pluginName;

    if(!URL::isSection('mg-admin')){ // подключаем CSS плагина для всех страниц, кроме админки
      mgAddMeta('<link rel="stylesheet" href="'.SITE.'/'.self::$path.'/css/style.css" type="text/css" />');
    }
  }

  
  /**
   * Метод выполняющийся при активации палагина 
   */
  static function activate(){
    USER::AccessOnly('1,4','exit()');
    self::createDataBase();
  }
  
  /**
   * Метод выполняющийся при деактивации палагина 
   * Удаляет папку с файлами плагина из mg-pages
   */
  static function deactivate(){
    USER::AccessOnly('1,4','exit()');
    unlink(PAGE_DIR.self::$pluginName.'/articleList.php');
    unlink(PAGE_DIR.self::$pluginName.'/article.php');
    unlink(PAGE_DIR.self::$pluginName.'/categoryList.php');
    rmdir(PAGE_DIR.self::$pluginName);
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
        includeJS("'.SITE.'/'.self::$path.'/js/script.js");  
        includeJS("'.SITE.'/'.self::$path.'/js/jquery-ui-timepicker-addon.js");
      </script> 
    ';
  }

  
  /**
   * Создает таблицу плагина в БД
   */
  static function createDataBase(){
    USER::AccessOnly('1,4','exit()');
    
    //Создание таблицы для элементов(статьи/акции/события)
    DB::query("
      CREATE TABLE IF NOT EXISTS `".PREFIX.self::$pluginName."_items` (
        `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Порядковый номер записи',
        `title` varchar(255) NOT NULL COMMENT 'Заголовок',
        `image_url` varchar(255) NOT NULL COMMENT 'Изображение',
        `date_active_to` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT 'Дата окончания активности',
        `date_active_from` timestamp DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата создания/начала активности',
        `activity` tinyint(4) NOT NULL DEFAULT '1' COMMENT 'Активность элемента',
        `url` varchar(255) NOT NULL COMMENT 'Ссылка',
        `tags` varchar(255) COMMENT 'Тэги',
        `description` longtext NOT NULL COMMENT 'Содержание статьи',
        `meta_title` varchar(255) NOT NULL,
        `meta_keywords` varchar(255) NOT NULL,
        `meta_desc` text NOT NULL,
        PRIMARY KEY (`id`)
      ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;");
    
    //Создание таблицы для категорий элементов
    DB::query("
      CREATE TABLE IF NOT EXISTS `".PREFIX.self::$pluginName."_categories` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `title` varchar(255) NOT NULL,
        `url` varchar(255) NOT NULL COMMENT 'Ссылка на категорию',
        `image_url` varchar(255) NOT NULL COMMENT 'Изображение',
        `description` text NOT NULL COMMENT 'Описание категории',
        `sort` INT(11),
        `meta_title` varchar(255) NOT NULL,
        `meta_keywords` varchar(255) NOT NULL,
        `meta_desc` text NOT NULL,
        PRIMARY KEY (`id`)
      ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;");
    
    //Создание таблицы связи категорий и статей
    DB::query("
      CREATE TABLE IF NOT EXISTS `".PREFIX.self::$pluginName."_item2category` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `item_id` int(11) NOT NULL,
        `category_id` int(11) NOT NULL,
        PRIMARY KEY (`id`)
      ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;");

    $lang = self::$lang;
    
    if(MG::getSetting(self::$pluginName.'-option') == null){
      $arPluginParams = array(
        'root_category_title' => $lang['ROOT_CATEGORY_TITLE'],
        'root_category_description' => $lang['DEFAULT_ROOT_META_DESC'],
        'root_category_keywords' => $lang['DEFAULT_ROOT_META_KEYWORDS'],
        'page_count' => 3,
        'preview_length' => 200,
        'show_active' => 'true',
        'check_active_period' => 'true',
        'show_category_count' => 'false',
        'show_empty_categories' => 'false',
      );
      MG::setOption(array('option' => self::$pluginName.'-option', 'value' => addslashes(serialize($arPluginParams))));
      MG::setOption('countPrintRowsBlog', 10);
    }
    
    $curDir = getcwd();
     
    $realDocumentRoot = str_replace(DIRECTORY_SEPARATOR . 'mg-plugins' . DIRECTORY_SEPARATOR . self::$pluginName, '', dirname(__FILE__));
    $pathUpload = $realDocumentRoot.'/uploads/'.self::$pluginName.'/';
    if(!file_exists($pathUpload)){
      chdir($realDocumentRoot . "/uploads/");
      mkdir(self::$pluginName, 0755);
      chdir($realDocumentRoot . "/uploads/".self::$pluginName."/");
      mkdir("thumbs", 0755);
      chdir($curDir);
    }
    
    $pathView = PAGE_DIR.self::$pluginName.'/';
    if(!file_exists($pathView)){
      chdir(PAGE_DIR);
      mkdir(self::$pluginName, 0755);
      chdir($curDir);
    }
    
    $listView = self::$path.'/views/articleList.php';
    if (!file_exists(PAGE_DIR.self::$pluginName.'/articleList.php')){
      copy($listView, PAGE_DIR.self::$pluginName.'/articleList.php');
    }
    
    $articleView = self::$path.'/views/article.php';
    if (!file_exists(PAGE_DIR.self::$pluginName.'/article.php')){
      copy($articleView, PAGE_DIR.self::$pluginName.'/article.php');
    }
    
    $listCategoryView = self::$path.'/views/categoryList.php';
    if (!file_exists(PAGE_DIR.self::$pluginName.'/categoryList.php')){
      copy($listCategoryView, PAGE_DIR.self::$pluginName.'/categoryList.php');
    }
    
  }

  /**
   * Выводит страницу настроек плагина в админке
   */
  static function pageSettingsPlugin(){
    USER::AccessOnly('1,4','exit()');
    
    $lang = self::$lang;
    $pluginName = self::$pluginName;
    $option = MG::getSetting('blog-option');
    $option = stripslashes($option);
    $options = unserialize($option);
    self::$arOptions = $options;
    
    if(isset($_REQUEST['manageCats']) && intval($_REQUEST['manageCats']>0)){
      //Получаем список всех категорий
      $arCategories = self::getEntityCategory(array('full'=>true));
      
      self::preparePageSettings();
      include('manageCategories.php');
    }else{
      //получаем количество выводимых записей
      $countPrintRows = MG::getOption('countPrintRowsBlog');   

      $res = self::getEntity($countPrintRows);    
      $entity = $res['entity'];
      $pagination = $res['pagination'];  
      $itemCats = self::getEntityCategory();
      $itemCategories = $itemCats['categories'];

      if(intval($res['selectedCat'])){
        $itemCategories[$res['selectedCat']]['active'] = 1;
      }

      self::preparePageSettings(); 
      include('pageplugin.php');
    }
    
  }

  
  /**
   * Получает из БД записи
   * @param int $count
   * @param int $category
   * @param boolean $public
   * @return array
   */
  static function getEntity($count=100, $category=0, $public=false, $tag=''){
    $result = array();
    
    $sql ='
      SELECT i.id, i.title, i.tags, i.image_url, i.date_active_from, i.description, i.activity, c.title as cat_name, CONCAT_WS( "/", c.url, i.url ) path
      FROM `'.PREFIX.self::$pluginName.'_items` i
      LEFT JOIN `'.PREFIX.self::$pluginName.'_item2category` i2c ON i.id = i2c.item_id
      LEFT JOIN `'.PREFIX.self::$pluginName.'_categories` c ON i2c.category_id = c.id ';
    
    if($public){
      
      $options = self::$arOptions;
      $where = '';
      
      if($options['show_active'] && $options['show_active'] != 'false'){
        $where .= 'WHERE i.activity = 1 ';
      }
      
      if($options['check_active_period'] && $options['check_active_period'] != 'false'){
        if(strlen($where) > 0){
          $where .= '
            AND i.date_active_from <= NOW()
            AND (i.date_active_to = \'0000-00-00\' OR IFNULL(i.date_active_to, NOW()) >= NOW())';
        }else{
          $where .= '
            WHERE i.date_active_from <= NOW()
            AND (i.date_active_to = \'0000-00-00\' OR IFNULL(i.date_active_to, NOW()) >= NOW())';
        }
      }
      
      if(!empty($tag)){
        $where .= ' AND `tags` like \'%'.DB::quote($tag, true).'%\'';
      }
      
      $sql .= $where;
      
    }
    
    if((!empty($_REQUEST['category']) && $_REQUEST['category'] != "null") || intval($category)>0){
      
      if(intval($category)>0){
        $cat_id = $category;
      }else{
        $cat_id = $_REQUEST["category"];
      }
      
      if(strlen($where) > 0){
        $sql .= ' AND i2c.category_id = '.DB::quote($cat_id).' ';
      }else{
        $sql .= ' WHERE i2c.category_id = '.DB::quote($cat_id).' ';
      }
      
      $result['selectedCat'] = $cat_id;
    }
    
    $sql .= '
      GROUP BY i.id
      ORDER BY i.date_active_from DESC';
    
    if ($_REQUEST["page"]){
      $page = $_REQUEST["page"]; //если был произведен запрос другой страницы, то присваиваем переменной новый индекс
    }
    
    $navigator = new Navigator($sql, $page, $count); //определяем класс
    $entity = $navigator->getRowsSql();
    foreach($entity as $cell=>$item){
      $entity[$cell]['date_active_from'] = date('d.m.Y H:i',strtotime($item['date_active_from']));
      
      if(!strtotime($item['date_active_to']) || $item['date_active_to']=='0000-00-00 00:00:00'){
        $entity[$cell]['date_active_to'] = '';
      }else{
        $entity[$cell]['date_active_to'] = date('d.m.Y H:i',strtotime($item['date_active_to']));
      }
      
      $entity[$cell]['path'] = '/'.self::$pluginName.'/'.$entity[$cell]['path'];
    }
    if(URL::isSection('mg-admin')){
      $pagination = $navigator->getPager("forAjax");
    }else{
      $pagination = $navigator->getPager();
    }
    
    $result['entity'] = $entity;
    $result['pagination'] = $pagination;
    $result['img_path'] = '/uploads/'.self::$pluginName.'/';
    
    return $result;
  }
  
  /**
   * Получает вывод списка и возвращает в виде строки
   * @param array $arg
   * @return string
   */
  static function printArticleList($arg=array()){
    $result = $arg['result'];
    $catId = 0;
    
    $tag = URL::getQueryParametr('tag');
    
    if(intval($arg['category']['id']) > 0 && empty($tag)){
      $catId = intval($arg['category']['id']);
    }
    
    $option = MG::getSetting('blog-option');
    $option = stripslashes($option);
    $options = unserialize($option);
    self::$arOptions = $options;
    
    $data = self::getEntity(self::$arOptions['page_count'], $catId, true, $tag);
    
    if(!empty($arg['category'])){
      $data['category'] = $arg['category'];
    }else{
      $data['category']['title'] = self::$arOptions['root_category_title'];
      $data['category']['meta_desc'] = self::$arOptions['root_category_description'];
      $data['category']['meta_keywords'] = self::$arOptions['root_category_keywords'];
    }
    
    foreach($data['entity'] as $cell=>$arEntity){
      $text = explode("<!--end-preview-->", $arEntity['description']);
      
      if(count($text) < 2){
        $text = explode("&lt;!--end-preview--&gt;", $arEntity['description']);          
      }
      
      if(count($text) > 1){
        $data['entity'][$cell]['previewText'] = strip_tags(PM::stripShortcodes($text[0]));
      }else{
        $arEntity['description'] = strip_tags(PM::stripShortcodes($arEntity['description']));
        $data['entity'][$cell]['previewText'] = 
          strlen($arEntity['description']) > self::$arOptions['preview_length']?
          mb_substr($arEntity['description'], 0, self::$arOptions['preview_length'], 'utf-8')."...":
          $arEntity['description'];
      }

      $data['category']['url'] = (mb_substr(URL::getClearUri(), -1) == '/')?URL::getClearUri():URL::getClearUri().'/';
      $tags = explode(",", $arEntity['tags']);
      
      if(count($tags) > 0){
        $data['entity'][$cell]['tags'] = array();
      
        foreach($tags as $tag){
          if(empty($tag)){
            continue;
          }
          
          $data['entity'][$cell]['tags'][] = array(
            'value' => trim($tag),
            'url' => SITE.'/'.self::$pluginName.'?tag='.trim($tag),
          );
        }
      }
    }
    
    $realDocumentRoot = str_replace(DIRECTORY_SEPARATOR.'mg-plugins'.DIRECTORY_SEPARATOR.self::$pluginName, '', dirname(__FILE__));
    ob_start();
    include($realDocumentRoot.'/mg-pages/'.self::$pluginName.'/articleList.php');
    $result = ob_get_contents();
    ob_end_clean();
      
    return $result;
  }
  
  /**
   * Получает вывод записи и возвращает его в виде строки
   * @param array $arg
   * @return string
   */
  static function printArticle($arg){
    $result = $arg['result'];
    
    $data = $arg['article'];
    $data['catPath'] = SITE.'/'.str_replace($data['url'], '', trim(URL::getClearUri(), '/'));
    $text = explode("<!--end-preview-->", $data['description']);
    
    if(count($text) < 2){
      $text = explode("&lt;!--end-preview--&gt;", $data['description']);          
    }
    
    if(count($text) > 1){
      $data['previewText'] = $text[0];
      $data['detailText'] = $text[1];
      unset($data['description']);
    }else{
      $data['detailText'] = $data['description'];
    }
    
    $tags = explode(",", $data['tags']);
      
    if(count($tags) > 0){
      $data['tags'] = array();

      foreach($tags as $tag){
        if(empty($tag)){
          continue;
        }

        $data['tags'][] = array(
          'value' => $tag,
          'url' => '/'.self::$pluginName.'?tag='.trim($tag),
        );
      }
    }
    
    $option = MG::getSetting('blog-option');
    $option = stripslashes($option);
    $options = unserialize($option);
    
    $realDocumentRoot = str_replace(DIRECTORY_SEPARATOR.'mg-plugins'.DIRECTORY_SEPARATOR.self::$pluginName, '', dirname(__FILE__));
    ob_start();
    include($realDocumentRoot.'/mg-pages/'.self::$pluginName.'/article.php');
    $result = ob_get_contents();
    ob_end_clean();
    
    return $result;
  }
  
  /**
   * Обрабатывает адрес и определяет что выводить список, или статью.
   * @param array $arg
   * @return string
   */
  static function viewSection($arg){
    $result = $arg['result'];
    
    if(URL::isSection(self::$pluginName)){     
      if(URL::getLastSection() != self::$pluginName){
        $categoryInfo = self::getCategoryByCode(URL::getLastSection());
        
        if(!empty($categoryInfo)){
          $result = self::printArticleList(array('category'=>$categoryInfo));
        }else{
          $articleInfo = self::getArticleByCode(URL::getLastSection());
          
          if(!empty($articleInfo)){
            $arraySections = URL::getSections();                 
            $section = $arraySections[count($arraySections)-2];
            
            if($section == $articleInfo['cat_url'] || 
                    (empty($articleInfo['cat_url']) && count($arraySections) < 5)) {
              $result = self::printArticle(array('article'=>$articleInfo));
            }
          }
        }
      }else{
        $result = self::printArticleList();
      }
    }
    
    return $result;
  }

   /**
   * Получает количество активных записей
   */
   
//  static function getEntityActive(){
//    USER::AccessOnly('1,4','exit()');
//    
//    $sql = "SELECT count(id) as count FROM `".PREFIX.self::$pluginName."_items` WHERE activity = 1";
//    $res = DB::query($sql);
//    if($count = DB::fetchAssoc($res)){
//      return $count['count'];
//    }
//    return 0;
//  }
   
  /**
   * Получает информацию о записи по её id
   * @param type $id
   * @return array
   */
  private function getCategoryById($id){
    $result = array();
    
    $sql = 'SELECT id, title, image_url, description, meta_title, meta_keywords, meta_desc
      FROM `'.PREFIX.self::$pluginName.'_categories`
      WHERE id = '.DB::quote($id);
    $dbRes = DB::query($sql);
    
    if(strlen($result['meta_title']) <= 0){
      $result['meta_title'] = $result['title'];
    }
    
    if($result = DB::fetchAssoc($dbRes)){
      return $result;
    }
    
    return $result;
  }
  
  /**
   * Получает информацию о категории по её символьному коду
   * @param type $code
   * @return array
   */
  private function getCategoryByCode($code){
    $result = array();
    
    $sql = 'SELECT id, title, image_url, description, meta_title, meta_keywords, meta_desc
      FROM `'.PREFIX.self::$pluginName.'_categories`
      WHERE url = '.DB::quote($code);
    $dbRes = DB::query($sql);
    
    if(strlen($result['meta_title']) <= 0){
      $result['meta_title'] = $result['title'];
    }
    
    if($result = DB::fetchAssoc($dbRes)){
      return $result;
    }
    
    return $result;
  }
  
  /**
   * Получает информацию о записи по её символьному коду
   * @param type $code
   * @return array
   */
  private function getArticleByCode($code){
    $result = array();
    
    $sql = '
      SELECT i.*, c.title as cat_title, c.url as cat_url
      FROM `'.PREFIX.self::$pluginName.'_items` i
        LEFT JOIN `'.PREFIX.self::$pluginName.'_item2category` i2c 
          ON i.id = i2c.item_id
        LEFT JOIN `'.PREFIX.self::$pluginName.'_categories` c 
          ON i2c.category_id = c.id
      WHERE i.url = '.DB::quote($code);
    $dbRes = DB::query($sql);
    
    if($result = DB::fetchAssoc($dbRes)){
      
      $option = MG::getSetting('blog-option');
      $options = unserialize(stripslashes($option));
      
      if($options['show_active'] && $options['show_active'] != 'false'){
        $where .= 'AND i.activity = 1 ';
      }
      
      if($options['check_active_period'] && $options['check_active_period'] != 'false'){
        $where .= '
          AND i.date_active_from <= NOW()
          AND (i.date_active_to = \'0000-00-00\' OR IFNULL(i.date_active_to, NOW()) >= NOW())';
      }
      
      //Ищем предидущую статью блога
      $sqlPrev = '
        SELECT i.title as title, CONCAT_WS( "/", c.url, i.url ) url
        FROM `'.PREFIX.self::$pluginName.'_items` i 
          LEFT JOIN `'.PREFIX.self::$pluginName.'_item2category` i2c 
            ON i.id = i2c.item_id 
          LEFT JOIN `'.PREFIX.self::$pluginName.'_categories` c 
            ON i2c.category_id = c.id
        WHERE `date_active_from` < \''.$result['date_active_from'].'\' 
          '.$where.' 
        ORDER BY `date_active_from` DESC 
        LIMIT 1';
      $dbRes = DB::query($sqlPrev);
      
      if($res = DB::fetchAssoc($dbRes)){
        $result['prev_article'] = array(
          'title' => $res['title'],
          'url' => SITE.'/blog/'.$res['url'],
        );
      }
      
      //Ищем следующую статью блога
      $sqlNext = '
        SELECT i.title, CONCAT_WS( "/", c.url, i.url ) url
        FROM `'.PREFIX.self::$pluginName.'_items` i 
          LEFT JOIN `'.PREFIX.self::$pluginName.'_item2category` i2c 
            ON i.id = i2c.item_id 
          LEFT JOIN `'.PREFIX.self::$pluginName.'_categories` c 
            ON i2c.category_id = c.id
        WHERE `date_active_from` > \''.$result['date_active_from'].'\' 
          AND `date_active_from` <= NOW() 
          '.$where.' 
        ORDER BY `date_active_from` ASC 
        LIMIT 1';
      $dbRes = DB::query($sqlNext);
      
      if($res = DB::fetchAssoc($dbRes)){
        $result['next_article'] = array(
          'title' => $res['title'],
          'url' => SITE.'/blog/'.$res['url'],
        );
      }
      
      
      if(strlen($result['meta_title']) <= 0){
        $result['meta_title'] = $result['title'];
      }
      
      $result['orig_date'] = $result['date_active_from'];
      $result['date_active_from'] = date('d.m.Y H:i',strtotime($result['date_active_from']));
      
      if(!strtotime($result['date_active_to']) || $result['date_active_to']=='0000-00-00 00:00:00'){
        $result['date_active_to'] = '';
      }else{
        $result['date_active_to'] = date('d.m.Y H:i',strtotime($result['date_active_to']));
      }
      
      $result['img_path'] = '/uploads/'.self::$pluginName.'/';
      
      return $result;
    }
    
    return $result;
  }
  
  /**
   * Получает информацию о записи по её id
   * @param type $id
   * @return array
   */
  private function getArticleById($id){
    $result = array();
    
    $sql = 'SELECT id, title, url, tags, image_url, date_active_to, date_active_from, description, meta_title, meta_keywords, meta_desc
      FROM `'.PREFIX.self::$pluginName.'_items`
      WHERE id = '.DB::quote($id);
    $dbRes = DB::query($sql);
    
    if($result = DB::fetchAssoc($dbRes)){
      
      if(strlen($result['meta_title']) <= 0){
        $result['meta_title'] = $result['title'];
      }
      
      $result['orig_date'] = $result['date_active_from'];
      $result['date_active_from'] = date('d.m.Y H:i',strtotime($result['date_active_from']));
      
      if(!strtotime($result['date_active_to']) || $result['date_active_to']=='0000-00-00 00:00:00'){
        $result['date_active_to'] = '';
      }else{
        $result['date_active_to'] = date('d.m.Y H:i',strtotime($result['date_active_to']));
      }
      
      $result['img_path'] = '/uploads/'.self::$pluginName.'/';
      
      return $result;
    }
    
    return $result;
  }
  
  /**
   * Получает список категорий для элементов
   * @param array $args - может содержать следующие ключи:
   *    boolean full - Если равно true, то выбираем све поля. Иначе выбираем только id и title
   *    boolean showCnt - Если равно true, учитываем количество записей в категории
   *    string addCol - дополнительные поля для выборки, передаются строкой через запятую
   */
  static function getEntityCategory($args){
    $result = array();
    
    if($args['full']){   
      $sql = '
        SELECT * 
        FROM `'.PREFIX.self::$pluginName.'_categories`
        ORDER BY `sort` ASC';
      
      $dbRes = DB::query($sql);
      
      while($res = DB::fetchAssoc($dbRes)){
        $result[] = $res;
      }
    }else{
      
      if(!empty($args['addCol'])){
        $args['addCol'] = ','.$args['addCol'];
      }
              
      $sql = '
        SELECT c.id as id, title'.$args['addCol'].', count(i2c.id) as cnt
        FROM `'.PREFIX.self::$pluginName.'_categories` c
        LEFT JOIN `'.PREFIX.self::$pluginName.'_item2category` i2c ON i2c.category_id = c.id
        GROUP BY c.id
        ORDER BY `sort` ASC';

      if($args['showEmptyCats'] == 'false'){
        $sql = '
          SELECT * 
          FROM ('.$sql.') cl
          WHERE cl.cnt > 0';
      }
      
      $dbRes = DB::query($sql);
      
      while($res = DB::fetchAssoc($dbRes)){
        $result['categories'][$res['id']] = $res;
      }
      
      if($args['showCnt'] && $args['showCnt'] != 'false'){
        $result['showCnt'] = true;
      }
 
    }
    
    return $result;
  }
  
  // Формирует и выводит RSS ленту.
  public static function blogFeed(){
    $title = '';
    $description = '';
    
    if (URL::getLastSection() == 'rss'){
      $ar = array_diff(explode("/", URL::getClearUri()), array(''));
      $catInfo = self::getCategoryByCode($ar[count($ar)-1]);
      
      $option = MG::getSetting('blog-option');
      $option = stripslashes($option);
      self::$arOptions = unserialize($option);
      
      if(!empty($catInfo)){
        $title = $catInfo['title'];
        $description = strip_tags(PM::stripShortcodes($catInfo['description']));
        $catId = $catInfo['id'];
      }else{  
        $title = self::$arOptions['root_category_title'];
        $description = strip_tags(PM::stripShortcodes(self::$arOptions['root_category_title']));
      }    
      
      MG::disableTemplate();
      include 'feed.php';
      $rss = new blogFeed(SITE, $title, $description);
      $data = self::getEntity(100, $catId, true);
      $articleList = $data['entity'];
      
      foreach($articleList as $arItem){
        
        $text = explode("<!--end-preview-->", $arItem['description']);                
        
        if(count($text) < 2){
          $text = explode("&lt;!--end-preview--&gt;", $arItem['description']);          
        }
        
        if(count($text) > 1){
          $arItem['previewText'] = strip_tags(PM::stripShortcodes($text[0]));
        }else{
          $arItem['description'] = strip_tags(PM::stripShortcodes($arItem['description']));
          $arItem['previewText'] = 
            strlen($arItem['description']) > self::$arOptions['preview_length']?
            mb_substr($arItem['description'], 0, self::$arOptions['preview_length'], 'utf-8')."...":
            $arItem['description'];
        }
        
        $rss->AddItem(
          htmlentities(SITE.$arItem['path']), $arItem['title'], $arItem['previewText'], $arItem['date_active_from']
        );
      }

      // публикуем рузельтирующий RSS 2.0
      $rss->Publish();
      exit;
    }
    
  }
  
  /**
   * Обработчик шотркода вида [blog-categories] 
   * выполняется когда при генерации страницы встречается [blog-categories] 
   */
  static function handleBlogCategoriesShortCode(){
    $result = '';
    
    if(empty(self::$arOptions)){
      $option = MG::getOption('blog-option');
      $option = stripslashes($option);
      self::$arOptions = unserialize($option);
    }
    
    $getCategoryParam = array(
      'addCol' => 'url'
    );
    
    $getCategoryParam['showCnt'] = self::$arOptions['show_category_count'];
    $getCategoryParam['showEmptyCats'] = self::$arOptions['show_empty_categories'];
    
    $data = self::getEntityCategory($getCategoryParam);
    $data['selected_category'] = URl::getLastSection();
  
    $realDocumentRoot = str_replace(DIRECTORY_SEPARATOR.'mg-plugins'.DIRECTORY_SEPARATOR.self::$pluginName, '', dirname(__FILE__));
    ob_start();
    include($realDocumentRoot.DIRECTORY_SEPARATOR.'mg-pages'.DIRECTORY_SEPARATOR.self::$pluginName.DIRECTORY_SEPARATOR.'categoryList.php');
    $result = ob_get_contents();
    ob_end_clean(); 
    
    return $result;
  }
  
  /**
   * Обработчик шотркода вида [blog-category id=%d] или [blog-category code=%s]
   * выполняется когда при генерации страницы встречается [blog-category id=%d] или [blog-category code=%s]
   */
  static function handleBlogCategoryShortCode($args){ 
    $result = '';
    
    if(intval($args['id']) > 0){
      $categoryInfo = self::getCategoryById($args['id']);
    }elseif(strlen($args['code']) > 0){
      $categoryInfo = self::getCategoryByCode($args['code']);
    }else{
      return self::$lang['NOT_SET'];
    }
    
    if(!empty($categoryInfo)){
      $result = self::printArticleList(array('category'=>$categoryInfo));
    }else{
      return self::$lang['NOT_FOUND'];
    }
    
    return $result;
  }
  
  /**
   * Обработчик шотркода вида [blog-article id=%d] или [blog-article code=%s]
   * выполняется когда при генерации страницы встречается [blog-article id=%d] или [blog-article code=%s]
   */
  static function handleBlogArticleShortCode($args){ 
    $result = '';
    
    if(intval($args['id']) > 0){
      $articleInfo = self::getArticleById($args['id']);
    }elseif(strlen($args['code']) > 0){
      $articleInfo = self::getArticleByCode($args['code']);
    }else{
      return self::$lang['NOT_SET'];
    }
    
    if(!empty($articleInfo)){
      $result = self::printArticle(array('article'=>$articleInfo));
    }else{
      return self::$lang['NOT_FOUND'];
    }
    
    return $result;
  }
  
  /*
   * Функция импорта новостных статей в плагин блога
   */
  public static function news2Blog(){
    $dbRes = DB::query('SELECT * FROM `mpl_news` ORDER BY `id`');
    $sql = 'INSERT INTO '.PREFIX.self::$pluginName.'_items 
      (`id`, `title`, `date_active_from`, `url`, `description`, `image_url`, `meta_title`, `meta_keywords`, `meta_desc`) VALUES ';
    
    $insertData = '';
    while($res = DB::fetchAssoc($dbRes)){
      
      if(strlen($insertData) > 0){
        $insertData .= ',';
      }
      
      $insertData .= '('.$res['id'].','.DB::quote($res['title']).','.DB::quote($res['add_date']).','.
        DB::quote($res['url']).','.DB::quote($res['description']).','.DB::quote($res['image_url']).','.
        DB::quote($res['meta_title']).','.DB::quote($res['meta_keywords']).','.DB::quote($res['meta_desc']).')';
    }
    
//    if(DB::query($sql.$insertData)){
//      echo 'okay!';
//    }else{
//      echo 'epic fail!';
//    }
    
    $realDocumentRoot = str_replace(DIRECTORY_SEPARATOR.'mg-plugins'.DIRECTORY_SEPARATOR.self::$pluginName, '', dirname(__FILE__)).'/';
      
    self::copyNewsImg('uploads/news/', 'uploads/'.self::$pluginName.'/');
    self::copyNewsImg('uploads/news/thumbs/', 'uploads/'.self::$pluginName.'/thumbs/');
  }
  
  private static function copyNewsImg($dir, $dst){
    $realDocumentRoot = str_replace(DIRECTORY_SEPARATOR.'mg-plugins'.DIRECTORY_SEPARATOR.self::$pluginName, '', dirname(__FILE__)).'/';
    $files = scandir($dir);
    foreach($files as $file){
      if($file != "." && $file != ".." && $file != 'thumbs'){
        copy($realDocumentRoot.$dir.$file, $realDocumentRoot.$dst.$file); 
      }
    }
  }
}