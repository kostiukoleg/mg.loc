<?php
/**
 * Класс Seo - предназначен для работы с функционалом системы, относящимся к 
 * seo-оптимизации контента.
 *
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Libraries
 */
class Seo{
  /**
   * Возвращает набор шаблонов, для формарования сео тегов, для переданного типа страницы
   * 
   * @param string $type
   * @return araay
   */
  public static function getTemplateForMeta($type){
    switch($type){
      case 'catalog':
        $templates = array(
          'meta_title' => MG::getSetting('catalog_meta_title'),
          'meta_desc' => MG::getSetting('catalog_meta_description'),
          'meta_keywords' => MG::getSetting('catalog_meta_keywords'),
        );
        break;
      case 'product':
        $templates = array(
          'meta_title' => MG::getSetting('product_meta_title'),
          'meta_desc' => MG::getSetting('product_meta_description'),
          'meta_keywords' => MG::getSetting('product_meta_keywords'),
        );
        break;
      case 'page':
        $templates = array(
          'meta_title' => MG::getSetting('page_meta_title'),
          'meta_desc' => MG::getSetting('page_meta_description'),
          'meta_keywords' => MG::getSetting('page_meta_keywords'),
        );
        break;
    }    
    
    return $templates;
  }
  
  /**
   * Возвращает массив со значениями метатегов, сформированных по шаблонам, 
   * заданным в настройках системы
   * 
   * @param string $type - тип страницы(каталог/товар/страница)
   * @param array $data - массив данных, используемых в шаблоне
   * @return array
   */
  public static function getMetaByTemplate($type, $data){
    
    mb_internal_encoding('UTF-8');

    $return = array();
    $templates = self::getTemplateForMeta($type);
    
    foreach($templates as $field=>$template){
      $matches = array();
      preg_match_all("/{[\pL\s\d():_'\",]+}/u", $template, $matches);
      
      foreach($matches[0] as $cell=>$match){
        $keys = mb_substr($match, 1, -1);
        
        if (mb_strpos($match, ":")) {
          $keys = explode(":", $keys);
          $template = str_replace($match, $data[$keys[0]][$keys[1]], $template);
        } else if (mb_strpos($match, ",")) {
          $keys = explode(",", $keys);
          $desc = MG::nl2br($data[$keys[0]]);
          $desc = strip_tags($desc);
          $length = ($keys[1] > 160) ? 160 : $keys[1];
          $tmplValue = mb_substr($desc, 0, $length);

          $template = str_replace($match, $tmplValue, $template);
        } else {
          $matchVar = mb_substr($match, 1, -1);
          $tmplValue = MG::nl2br($data[$matchVar]);
          $tmplValue = strip_tags($tmplValue);
          
          $arTmpl = explode($match, $template);

          if ($arTmpl[0] == "" || mb_strpos($tmplValue, $arTmpl[0]) === 0) {
            if ($arTmpl[1] == "" || 
                    (mb_strrpos($tmplValue, $arTmpl[1])+mb_strlen($arTmpl[1])) 
                    == mb_strlen($tmplValue)) {
              $template = '';
              break;
            }
          }
          
          $template = str_replace($match, $tmplValue, $template);
        }
      }
      
      $return[$field] = $template;
    }          
    
    return $return;
  }
  /**
   * Создает в корневой папке сайта карту в формате XML
   */
  public function autoGenerateSitemap() {
    $urls = array();
    /*
     * категории каталога 
     */
    $result = DB::query('
      SELECT  url,  parent_url 
      FROM `'.PREFIX.'category`');
    while ($row = DB::fetchAssoc($result)) {
      $urls[] = $row['parent_url'].$row['url'];
    }
    /*
     * страницы товаров, с учетом флага коротких ссылок,
     */
    if (SHORT_LINK == 1 || MG::getSetting('shortLink') == 'true') {
      $result = DB::query('   
      SELECT url
      FROM `'.PREFIX.'product`');
    } else {
      $result = DB::query('   
      SELECT CONCAT(c.parent_url,c.url,"/",p.url) as url
      FROM `'.PREFIX.'product` as p
      LEFT JOIN `'.PREFIX.'category` as c
      ON p.cat_id = c.id ');
    }
    while ($row = DB::fetchAssoc($result)) {
      $urls[] = $row['url'];
    }
    /*
     * статические страницы сайта
     */
    $result = DB::query('
      SELECT  parent_url, url
      FROM `'.PREFIX.'page`');
    while ($row = DB::fetchAssoc($result)) {
      if ($row['url'] != 'index') {
        $pattern = "/^(http|https):\/\/([a-z0-9\.-]+)\.([a-z\.]{2,6})(.*)$/";
        $matches = array();
        preg_match($pattern, $row['url'], $matches);
        if (!empty($matches)) {
          if (trim($row['parent_url'], '/') == trim($matches[count($matches) - 1], '/')) {
            continue;
          }
          $urls[] = trim($matches[count($matches) - 1], '/');
          continue;
        }
        $urls[] = $row['parent_url'].$row['url'];
      }
    }
    $res = DB::query("SELECT *  FROM ".PREFIX."plugins WHERE folderName = 'news' and active = '1'");
    if (DB::numRows($res)) {
      /*
       * страницы новостей  
       */
      $result = DB::query('
       SELECT  url
       FROM `mpl_news`');
      while ($row = DB::fetchAssoc($result)) {
        $urls[] = 'news/'.$row['url'];
      }
    }
    $res = DB::query("SELECT *  FROM ".PREFIX."plugins WHERE folderName = 'blog' and active = '1'");
    if (DB::numRows($res)) {
      $result = DB::query("
       SELECT CONCAT(IFNULL(bc.url,''),'/',bi.url) as url
       FROM  ".PREFIX."blog_items as bi
	   LEFT JOIN  `".PREFIX."blog_item2category` as b2c ON b2c.`item_id` = bi.`id`
	   LEFT JOIN  `".PREFIX."blog_categories` as bc ON bc.`id` = b2c.`category_id`

	  ");
      while ($row = DB::fetchAssoc($result)) {
        $urls[] = str_replace('//', '/', 'blog/'.$row['url']);
      }
    }
    
    $dbRes = DB::query("SELECT `short_url` FROM `".PREFIX."url_rewrite` WHERE `activity` = 1");
    if (DB::numRows($dbRes)) {
      while ($row = DB::fetchAssoc($dbRes)) {
        $urls[] = $row['short_url'];
      }
    }
    
    /*
     * страницы из папки mg-pages  
     */
    $files = scandir(PAGE_DIR);
    foreach ($files as $item) {
      $pathInfo = pathinfo($item);
      if ($pathInfo['extension'] == 'php' || $pathInfo['extension'] == 'html') {
        if ($pathInfo['filename'] != 'captcha') {
          $urls[] = $pathInfo['filename'];
        }
      }
    }
    $urls = array_unique($urls);    
    $exl = explode(';', MG::getSetting('excludeUrl'));
    foreach ($exl as &$url) {
      $url = str_replace(SITE.'/', '', trim($url));
    }
    $urls = array_diff($urls, $exl);
    $xmlSitemap = self::getXmlView(array_diff($urls, $exl));
    $string = $xmlSitemap;
    $f = fopen('sitemap.xml', 'w');
    $result = fwrite($f, $string);
    fclose($f);
    if ($result) {
      return count($urls);
    } else {
      return false;
    }
    
  }

  // функция создания sitemap.xml
  public function getXmlView($urls) {
    $nXML = '<?xml version="1.0" encoding="UTF-8"?>
      <urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">
      ';
    $xml = new XMLWriter();
    $xml->openMemory();
    $xml->setIndent(true);
    $date = date("Y-m-d");
    foreach ($urls as $url) {
      $xml->startElement("url");
      $xml->writeElement("loc", SITE.'/'.$url);
      $xml->writeElement("lastmod", $date);
      $partsUrl = URL::getSections($url);
      $priority = count($partsUrl);
      if ($priority >= 3) {
        $priority = '0.5';
        // исключение для главной страницы
        if ($partsUrl[2] == 'ajax') {
          $priority = '1.0';
        }
      }
      if ($priority == 2) {
        $priority = '0.8';
      }
      if ($priority == 1) {
        $priority = '1.0';
      }
      $xml->writeElement("priority", $priority);
      $xml->endElement();
    }
    $nXML .= $xml->outputMemory();
    $nXML .= '</urlset>';
    return mb_convert_encoding($nXML, "WINDOWS-1251", "UTF-8");
  }
}