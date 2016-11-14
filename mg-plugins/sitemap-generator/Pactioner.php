<?php

/**
 * Класс Pactioner наследник стандарного Actioner
 * Предназначен для выполнения действий,  AJAX запросов плагина 
 *
 * @author Avdeev Mark <mark-avdeev@mail.ru>
 */
class Pactioner extends Actioner {

  private $pluginName = 'sitemap-generator';

  /**
   * Создает в корневой папке сайта карту в формате XML
   */
  public function generateSitemap() {
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
    if (SHORT_LINK == 1||MG::getSetting('shortLink')=='true') {
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
      if($row['url']!='index'){
        $pattern = "/^(http|https):\/\/([a-z0-9\.-]+)\.([a-z\.]{2,6})(.*)$/";
        $matches = array();
        preg_match($pattern, $row['url'], $matches);
        if(!empty($matches)){
          if(trim($row['parent_url'], '/') == trim($matches[count($matches)-1], '/')){
            continue;
          }
          $urls[] = trim($matches[count($matches)-1], '/');
          continue;
        }
        $urls[] = $row['parent_url'].$row['url']; 
      }
    }


    /*
     * страницы новостей 

      $result = DB::query('
      SELECT  url
      FROM `'.PREFIX.'news`');
      while ($row = DB::fetchAssoc($result)) {
      $urls[] = 'news/'.$row['url'];
      }
     */

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
      /*
       * страницы новостей  
       */
      $result = DB::query("
       SELECT CONCAT(IFNULL(bc.url,''),'/',bi.url) as url
       FROM  ".PREFIX."blog_items as bi
	   LEFT JOIN  `".PREFIX."blog_item2category` as b2c ON b2c.`item_id` = bi.`id`
	   LEFT JOIN  `".PREFIX."blog_categories` as bc ON bc.`id` = b2c.`category_id`

	  ");
      while ($row = DB::fetchAssoc($result)) {
        $urls[] = str_replace('//','/','blog/'.$row['url']);
      }
    }

	
    /*
     * страницы из папки mg-pages  
     */
    $files = scandir(PAGE_DIR);
    foreach ($files as $item) {
      $pathInfo = pathinfo($item);
      if ($pathInfo['extension']=='php'||$pathInfo['extension']=='html') {
        if ($pathInfo['filename']!='captcha') {
          $urls[] = $pathInfo['filename'];
        }
      }
    }


    $urls = array_unique($urls);
    $xmlSitemap = $this->getXmlView($urls);
    $string = $xmlSitemap;
    $f = fopen('sitemap.xml', 'w');
    fwrite($f, $string);
    fclose($f);
    $this->messageSucces = 'Карта создана и сохранена в корне сайта';
    $msg = "В последний раз файл <span style='color:green'>sitemap.xml</span> был изменен: <span class='date-site-map' style='color:blue'>".MG::dateConvert(date("d.m.Y"), true).' г.</span>
      <br>В файле содержится '.count($urls).' шт. ссылок.';

    $this->data = array('msg' => $msg);
    return true;
  }

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
      if ($priority>=3) {
        $priority = '0.5';
        // исключение для главной страницы
        if($partsUrl[2] == 'ajax'){
          $priority = '1.0';
        }
      }
      if ($priority==2) {
        $priority = '0.8';
      }
      if ($priority==1) {
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