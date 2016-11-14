<?php

/*
  Plugin Name: SMS оповещения
  Description: Позволяет отправлять бесплатные СМС оповещения администратору сайта о новых заказах, а также любых других событиях. Возможна отправка SMS покупателям (платно). Шорт код [sms]Текст ообщения[/sms]
  Author: Антон Кокарев
  Version:  Версия 1.0
 */

new SMSAlerts;

class SMSAlerts {

  public function __construct() {
    mgActivateThisPlugin(__FILE__, array(__CLASS__, 'createDateBase'));
    mgAddAction(__FILE__, array(__CLASS__, 'pageSettingsPlugin'));

    if (!URL::isSection('mg-admin')) {
      mgAddShortcode('sms', array(__CLASS__, 'sendsms'));
    }
  }

  /**
   * Создает таблицу настроек в БД при активации плагина
   */
  static function createDateBase() {
    DB::query("
      CREATE TABLE IF NOT EXISTS `".PREFIX."sms_setting` (
       `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Порядковый номер настройки',
       `option` varchar(255) NOT NULL COMMENT 'Имя опции',
       `value` longtext NOT NULL COMMENT 'Значение опции',
       `name` varchar(255) NOT NULL COMMENT 'Название опции',
       PRIMARY KEY (`id`)
      ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 COMMENT='Настройки плагина SMS' AUTO_INCREMENT=1 ");

    DB::query("INSERT IGNORE INTO `".PREFIX."sms_setting` (`id`, `option`, `value`, `name`) VALUES
      (1, 'nomer', '79991234567', 'NOMER'),
      (2, 'token', 'api_id', 'api_id') ");
  }

  /**
   * Получает текущий номер телефона
   */
  static function getNomer() {
    $nomer = "";
    $res = DB::query(" SELECT `value` FROM `".PREFIX."sms_setting` WHERE `id`= 1");
    if ($row = DB::fetchAssoc($res)) {
      $nomer = $row['value'];
    }
    return $nomer;
  }

  /**
   * Получает текущий токен
   */
  static function getToken() {
    $token = "";
    $res = DB::query(" SELECT `value` FROM `".PREFIX."sms_setting` WHERE `id`= 2");
    if ($row = DB::fetchAssoc($res)) {
      $token = $row['value'];
    }
    return $token;
  }

  /**
   * Страница настроек плагина
   */
  static function pageSettingsPlugin() {
    echo '<link rel="stylesheet" href="../mg-plugins/sms-alerts/css/pageSettings.css" type="text/css" />';

    if (isset($_POST['nomer']) && isset($_POST['token'])) {
      $nomer = $_POST['nomer'];
      $token = $_POST['token'];
      DB::query("
        UPDATE `".PREFIX."sms_setting`
        SET `value` = ".DB::quote($nomer)."
        WHERE id=1
      ");
      DB::query("
        UPDATE `".PREFIX."sms_setting`
        SET `value` = ".DB::quote($token)."
        WHERE id=2
      ");
      echo "<br/><div class=\"sms-setting-update\">Установлен номер для SMS информирования: ".$nomer.". Токен: ".$token."</div>";
    } else {
      $nomer = self::getNomer();
      $token = self::getToken();
    };

    echo '
      <div class="sms-setting-block">  
        <form methot="post" action="">
          Номер телефона: <input class="nomer" type="text" name="nomer" value='.$nomer.'><br/>
          Токен: <input class="token" type="text" name="token" value='.$token.'><br/>
          <input type="hidden" name="pluginTitle" value="".$_POST[\'pluginTitle\'].""/>
          <input type="submit" value="Применить"/>
        </form>
      </div>
      <br/>
      <div class="sms-help-block">
        <i><b>Инструкция:</b></i><br/>
        <br/>
        Для получения токена пройдите по ссылке <a href="http://mogutacms.sms.ru/?panel=register">Регистрация</a>;<br/>
        Пройдите бесплатную регистрацию, подтвердите номер телефона кодом из SMS сообщения (бесплатно);<br/>
        Откройте <a href="http://mogutacms.sms.ru/?panel=my">Панель</a> и скопируйте Ваш <b>api_id</b> - это и есть токен.<br/>
        <br/>
        Для отправки SMS достаточно добавить в нужном месте шорт код <b>[sms]Текст сообщения[/sms]</b>.<br/>
        По-умолчанию сообщение будет отправлено на Ваш номер.<br/>
        Чтобы отправить смс на другой номер укажите его в шорткоде. Например так <b>[sms nomer="79990001122"]Hellow World![/sms]</b>. Перед отправкой SMS на другие номера ознакомьтесь с тарифами и правилами на сайте <a href="http://mogutacms.sms.ru/?panel=settings&subpanel=plan">sms.ru</a>.<br/>
        Бесплатная отправка SMS возможна только на свой номер!<br/>
        <br/>
        Полный формат шорт кода: <b>[sms nomer="79180001122" token="a123b45c-d6e7-8h90-g123-k45m6no78901"]Текст сообщения[/sms]</b>.<br/>
        Обязателен только текст сообщения. Номер и токен можно не указывать (они должны быть указаны в настройках плагина)<br/>
        <br/>
        Для получения уведомления о новом заказе откройте шаблон order.php и найдите текст "<i>На Ваш электронный адрес выслано письмо для подтверждения заказа</i>", сразу после него вставьте <b>[sms]Принята заявка #&lt?php echo $data[\'orderNumber\']; ?&gt Сумма &lt?php echo $data[\'summ\']; ?&gt &lt?php echo $data[\'currency\']; ?&gt[/sms]</b>
      </div>
	';
  }

  /**
   * Отправляет СМС через сервис sms.ru
   */
  static function sendsms_smsru($nomer, $msg, $token) {
    $body = file_get_contents("http://sms.ru/sms/send?api_id=$token&to=$nomer&text=".urlencode($msg));
    return $body;
  }

  /**
   * Обработчик шотркода вида [sms nomer="79123456789" token="abcde123-qwerty-9876"]Текст сообщения[/sms].
   * Отправляет СМС на указанный номер. Если номер не указан, использует номер по-умолчанию.
   * Если токен не указан, использует токен по-умолчанию.
   * Если не указан текст СМС, то сообщение отправлено не будет
   */
  static function sendsms($arg) {
    $msg = $arg['content'];
    if (isset($arg['content'])) {
      $msg = $arg['content'];

      if (isset($arg['nomer'])) {
        $nomer = $arg['nomer'];
      } else {
        $nomer = self::getNomer();
      };

      if (isset($arg['token'])) {
        $token = $arg['token'];
      } else {
        $token = self::getToken();
      };

      $res = self::sendsms_smsru($nomer, $msg, $token);
    }
    return "";
  }

}

?>