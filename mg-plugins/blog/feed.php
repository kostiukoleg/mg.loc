<?php

class blogFeed{

  var $lang = "ru";
  var $ttl = 60;
  var $date;
  var $url;
  var $title;
  var $description;
  var $items = array();
  var $image = array();
  var $xml_ver = "1.0";
  var $rss_ver = "2.0";
  var $encoding = "utf-8";
  var $content = '';

  function blogFeed($url, $title, $description, $date = null){
    $this->SetChannelInfo($url, $title, $description, $date);
  }

  function AddItem($url, $title, $description, $date, $brief = '', $author = '', $category = ''){
    array_push($this->items, array(
      'url' => $url,
      'title' => $title,
      'description' => $description,
      'date' => $date,
      'category' => $category,
      'author' => $author,
      'brief' => ($brief ? $brief : $description)
    ));
  }

  function Get(){
    $content .= "<?xml version=\"".$this->GetXMLVersion()."\" encoding=\"".$this->GetEncoding()."\" ?>\n";
    $content .= "<rss version=\"".$this->GetRSSVersion()."\" xmlns:dc=\"http://purl.org/dc/elements/1.1/\" xmlns:content=\"http://purl.org/rss/1.0/modules/content/\">\n";
    $content .= "<channel>\n";
    $content .= "<title><![CDATA[".$this->GetChannelTitle()."]]></title>\n";
    $content .= "<link>".$this->GetChannelURL()."</link>\n";
    $content .= "<description><![CDATA[".$this->GetChannelDescription()."]]></description>\n";
    $content .= "<language>".$this->GetLang()."</language>\n";
    # $content .= "<copyright></copyright>\n";
    $content .= "<lastBuildDate>".$this->GetDate()."</lastBuildDate>\n";
    $content .= "<ttl>".$this->GetTTL()."</ttl>\n";

    if($image = $this->GetImage()){
      $content .= "<image>\n";
      $content .= "<url>".$image['path']."</url>\n";
      $content .= "<title><![CDATA[".$image['title']."]]></title>\n";
      $content .= "<link>".$image['url']."</link>\n";
      $content .= "</image>\n";
    }

    if($items = $this->GetItems()){
      foreach($items as $item){
        $content .= "<item>\n";
        $content .= "<title><![CDATA[".$item['title']."]]></title>\n";
        $content .= "<pubDate>".date("r", strtotime($item['date']))."</pubDate>\n";
        $content .= "<link>".$item['url']."</link>\n";
        $content .= "<guid isPermaLink=\"false\">".$item['url']."</guid>\n";

        if($item['category'])
          $content .= "<category><![CDATA[".$item['category']."]]></category>\n";
        if($item['author'])
          $content .= "<dc:creator>".$item['author']."</dc:creator>\n";

        $content .= "<description>\n";
        $content .= "<![CDATA[".$item['brief']."]]>";
        $content .= "\n</description>\n";
        $content .= "<content:encoded>\n";
        $content .= "<![CDATA[".$item['description']."]]>";
        $content .= "\n</content:encoded>\n";
        $content .= "</item>\n";
      }
    }

    $content .= "</channel>\n";
    $content .= "</rss>";
    $this->content = $content;

    return $this->content;
  }

  function Publish($content = ''){
    if(!$content){
      $this->Get();
      $content = $this->content;
    }

    header("Content-Type: application/xml");
    header("Content-Length: ".strlen($content));
    echo $content;
  }

  function SetChannelInfo($url, $title, $description, $date = null){
    $this->url = $url;
    $this->title = $title;
    $this->description = $description;
    $this->date = $date ? date("r", strtotime($date)) : date("r");
  }

  function SetImage($path, $title, $url){
    $this->image['path'] = $path;
    $this->image['title'] = $title;
    $this->image['url'] = $url;
  }

  function GetXMLVersion(){
    return $this->xml_ver;
  }

  function GetRSSVersion(){
    return $this->rss_ver;
  }

  function GetEncoding(){
    return $this->encoding;
  }

  function GetChannelTitle(){
    return $this->title;
  }

  function GetChannelURL(){
    return $this->url;
  }

  function GetChannelDescription(){
    return $this->description;
  }

  function GetLang(){
    return $this->lang;
  }

  function GetDate(){
    return $this->date;
  }

  function GetTTL(){
    return $this->ttl;
  }

  function GetImage(){
    return $this->image;
  }

  function GetItems(){
    return $this->items;
  }

}
