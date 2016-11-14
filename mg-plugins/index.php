<?php

/*
  Plugin Name: Вставка роликов Youtube
  Description: Плагин позволяет использую шорт коды вставлять в разметку страницы ролики из youtube используя лишь буквенный код страницы. Может задавать ширину (width, по умолчанию 560 px) и высоту (height, по умолчанию 315 px) окна просмотра в пиксилях. К примеру, [youtube width = 300 height = 200]XrVHXQ7CDFo[/youtube]
  Author: dmgriny
  Version: 1.0.1
 */

   mgAddShortcode('youtube',  'px_showVideo');

   function px_showVideo($args){
   ;
	if(!$args['width']){
		$args['width'] = 560;
	}
	if(!$args['height']){
		$args['height'] = 315;
	}
	$string = '<iframe width="'.$args['width'].'" height="'.$args['height'].'" src="http://www.youtube.com/embed/'.$args['content'].'?rel=0" frameborder="0" allowfullscreen></iframe>';
	
    return $string;
  }
