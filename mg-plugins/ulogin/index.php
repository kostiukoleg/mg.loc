<?php

/*
  Plugin Name: Авторизация ULogin
  Description: Плагин авторизации с использованием аккаунта в социальных сетях, с помощью сервиса <a href="http://ulogin.ru" target="_blank">uLogin.ru</a>. Шорт-код [ulogin]
  Author: <a style="text-decoration: none; color:black" href="http://mogutashop.ru/" target="_blank">MogutaSHOP Developers</a>
  Version: 1.1 Free
 */

new ULoginAuth;

class ULoginAuth {

  private static $lang = array(); // массив с переводом плагина 
  private static $pluginName = ''; // название плагина (соответствует названию папки)
  private static $path = ''; //путь до файлов плагина 

  public function __construct() {

    mgActivateThisPlugin(__FILE__, array(__CLASS__, 'activate')); //Инициализация  метода выполняющегося при активации  
	mgDeactivateThisPlugin(__FILE__, array(__CLASS__, 'deactivate')); //Инициализация  метода выполняющегося при деактивации    
    mgAddShortcode('ulogin', array(__CLASS__, 'handleShortCode')); // Инициализация шорткода [ulogin] - доступен в любом HTML коде движка.    

    self::$pluginName = PM::getFolderPlugin(__FILE__);
    self::$lang = PM::plugLocales(self::$pluginName);
    self::$path = PLUGIN_DIR.self::$pluginName;

    if (!URL::isSection('mg-admin')) { // подключаем CSS плагина для всех страниц, кроме админки
      //mgAddMeta('<link rel="stylesheet" href="'.SITE.'/'.self::$path.'/css/style.css" type="text/css" />');
    }
  }

  
  /**
   * Метод выполняющийся при активации палагина 
   */
  static function activate() {
	copy('mg-plugins/ulogin/socialauth.php', 'mg-pages/socialauth.php');
    DB::query("ALTER TABLE `".PREFIX."user` ADD `ulogin_hash` VARCHAR( 255 ) NOT NULL DEFAULT '',
    ADD INDEX ( `ulogin_hash` )
    ");
	
	
  }
  
  /**
   * Метод выполняющийся при деактивации палагина 
   */
  static function deactivate() {
	unlink('mg-pages/socialauth.php');
	DB::query('DELETE FROM `'.PREFIX.'user` WHERE ulogin_hash != ""');
    DB::query('ALTER TABLE `'.PREFIX.'user` DROP `ulogin_hash`');
  }

  


  /**
   * Обработчик шотркода вида [ulogin] 
   * выполняется когда при генерации страницы встречается [ulogin] 
   */
  static function handleShortCode() {
    $option = MG::getSetting('ulogin-auth');
    $option = stripslashes($option);
    $options = unserialize($option);   

	if ($options['widget'] == '') {
	//providers=vkontakte,odnoklassniki,mailru,facebook;
		$html = '
		<script src="//ulogin.ru/js/ulogin.js"></script>
		<a href="#" id="uLogin" data-ulogin="display=window;fields=first_name,last_name,nickname,email;optional=bdate,sex,photo_big,city,country;redirect_uri='.SITE.'/socialauth"><img src="https://ulogin.ru/img/button.png" width=187 height=30 alt="МультиВход"/></a>
		';
	}
	else {
		$html = $options['widget'];
	}
 
	if (USER::isAuth()) $html = '';

    return $html;
  }

}