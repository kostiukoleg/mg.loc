<?php

/*
  Plugin Name: Партнерская программа
  Description: Устанавливает связь между оплаченными заказами и пользователем, благодаря которому был оплачен заказ. Добавляет страницу /affiliate, на которой необходимо разместить информацию о вашей партнерской программе. Шорт код [data-balance] необходимо разместить на странице личного кабинета в файле 'ваша тема'/views/personal.php , для отображения баланса партнеров.
  Author: Avdeev Mark, Чуркина Дарья
  Version: 1.3.2
 */

if (URL::isSection('personal') || URL::isSection('affiliate')) {
  mgAddMeta("<link rel='stylesheet' href='".SITE."/mg-plugins/partners-program/css/style.css' type='text/css' />");
  mgAddMeta("<script type='text/javascript' src='".SITE."/mg-plugins/partners-program/js/patner.js'> </script>");
}

new PartnerProgram;

class PartnerProgram {

  static public $percent = 20; //процент для партнеров
  static public $exitMoneyLimit = 50; //минимальная сумма для вывода
  static public $contract = false; // флаг для обязательного договора
  private $status = array('0' => 'Не доступно', 1 => 'Выплачен', 2 => 'Запрос отправлен', 3 => 'Доступно');
  static public $_partner = array();
  public function __construct() {

    mgActivateThisPlugin(__FILE__, array(__CLASS__, 'createDateBase'));
    mgAddAction(__FILE__, array(__CLASS__, 'pageSettingsPlugin'));
    mgAddShortcode('data-balance', array(__CLASS__, 'getBalanceInPersonal'));
    mgAddShortcode('affiliate', array(__CLASS__, 'getBalance'));
    mgAddAction('models_order_updateorder', array(__CLASS__, 'holdMoney'), 1);

    // установка куки если есть гет параметр
    if (isset($_GET['partnerId']) && is_numeric($_GET['partnerId'])) {
      self::setPartnerCookie($_GET['partnerId']);
    }

    // при каждом оформлении заказа создавать запись в партнерской таблице
    mgAddAction('models_order_addorder', array(__CLASS__, 'partnerToOrder'), 1);

    // ждем когда придет оплата
    mgAddAction('controllers_payment_actionwhenpayment', array(__CLASS__, 'eventPayment'), 1);

    $option = MG::getSetting('partners-program');
    $option = stripslashes($option);
    $options = unserialize($option);
    self::$percent = $options['percent'];
    self::$exitMoneyLimit = $options['exitMoneyLimit'];
    self::$contract = $options['contract'];
  }

  //Пришла оплата заказа по электронным деньгам
  static function eventPayment($arg) {
    self::updateOrder($arg);
  }

  /**
   * Создает таблицу для функционирования плагина партнерки
   */
  static function createDateBase() {
    DB::query("CREATE TABLE IF NOT EXISTS `".PREFIX."partner` (
      `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Номер партнера',
      `user_id` int(11) NOT NULL COMMENT 'Партнер',
      `percent` float NOT NULL COMMENT 'Процент', 
      `payments_amount` float NOT NULL COMMENT 'Всего было выплачено',
      `count` int NOT NULL COMMENT 'Количество переходов по ссылке',
      `contract` INT(1) NOT NULL,
      `about` TEXT NOT NULL,
      `orders_count` INT NOT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ");

    DB::query("
      CREATE TABLE IF NOT EXISTS `".PREFIX."partner_order` (
      `partner_id` int(11) NOT NULL,
      `order_id` int(11) UNIQUE NOT NULL,
      `order_number` VARCHAR(32) NOT NULL,
      `percent` double NOT NULL,
      `summ` double NOT NULL,
      `date_order` DATETIME NOT NULL,
      `date_done` DATETIME NOT NULL,
      `status` INT(11) NOT NULL DEFAULT 0, 
	    `hold` tinyint(1) NOT NULL DEFAULT 1,
      `request_id` INT(11) NOT NULL
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Связь партнеров с оплаченными заказами'
     "
    );

    DB::query("
      CREATE TABLE IF NOT EXISTS `".PREFIX."partner_payments_request` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `partner_id` int(11) NOT NULL,
        `orders_id` varchar(255) NOT NULL,
        `orders_numbers` text NOT NULL,
        `date_add` datetime NOT NULL,
        `date_done` datetime NOT NULL DEFAULT 0,
        `summ` double NOT NULL,
        `status` int(11) NOT NULL,
        `comment` text NOT NULL,
        PRIMARY KEY (`id`)
      ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1
     "
    );

    //добавляем статическую страницу /affiliate, чтобы ее было удобно редактировать.
    $res = DB::query("SELECT id FROM `".PREFIX."page` WHERE `url` = 'affiliate'");
    if (!DB::numRows($res)) {
      DB::query("INSERT IGNORE INTO `".PREFIX."page` 
        ( `title`, `url`, `html_content`, `meta_title`, `meta_keywords`, `meta_desc`) 
        VALUES ( 'Партнерская программа', 'affiliate', '[affiliate]', 'Партнерская программа', 
        'Партнерская программа', 'Партнерская программа на ".MG::getSetting('sitename').", зарабатывайте с нами!')");
    }
    $array = Array(
      'percent' => 20,
      'exitMoneyLimit' => 1000,
      'contarct' => 'false',
      'period' => 30,
    );

    MG::setOption(array('option' => 'partners-program', 'value' => addslashes(serialize($array))));
    MG::setOption(array('option' => 'countPrintRowsPartners', 'value' => 20));
  }

  /**
   * Выводит страницу настроек плагина в админке
   */
  static function pageSettingsPlugin() {
    self::checkOrderPartner();
    $page = !empty($_POST["page"]) ? $_POST["page"] : 0; //если был произведен запрос другой страницы, то присваиваем переменной новый индекс
    $countPrintRowsPartners = MG::getSetting('countPrintRowsPartners');
    $navigator = new Navigator("
      SELECT p. * , u.email
      FROM  `".PREFIX."partner` AS p
      LEFT JOIN  `".PREFIX."user` AS u ON u.id = p.user_id
      LEFT JOIN  `".PREFIX."partner_order` AS po ON po.partner_id = p.id
      GROUP BY p.id
      ORDER BY p.payments_amount DESC  ", $page, $countPrintRowsPartners); //определяем класс

    $partners = $navigator->getRowsSql();
    $pagination = $navigator->getPager('forAjax');

    $sql = " SELECT *
      FROM  `".PREFIX."partner_payments_request` 
      ORDER BY date_add DESC ";
    $navigator = new Navigator($sql, $page, $countPrintRowsPartners);
    $request = $navigator->getRowsSql();
    $pageRequest = $navigator->getPager('forAjax');

    $option = MG::getSetting('partners-program');
    $option = stripslashes($option);
    $options = unserialize($option);

    $statusRequest = array(2 => 'Ожидает оплаты', 1 => 'Выполнен', 0 => 'Не доступно', 4 => 'Отказ');
    include 'pagePlugin.php';
  }

  // блокирует деньги парнета по данному заказу, если статус не равен - выполнен или оплачен.
  static function holdMoney($arg) {
    // viewData($arg['args'][0]['id']);
    $orderId = $arg['args'][0]['id'];
    $orderStatus = $arg['args'][0]['status_id'];

    $partner = self::closeOrderPartner($orderId);
    $hold = 'SET `hold` = 1';
    $done = '';

    if ($partner['date_done'] == 0) {
      $done = ', `date_done` = now()';
    }
    //если статус заказа "выполнен" или "оплачен", то разблокируем средства для вывода
    if ($arg['args'][0]['status_id'] == 5 || $arg['args'][0]['status_id'] == 2) {
      $hold = 'SET `hold` = 0'.$done;
    }

    DB::query('
		UPDATE `'.PREFIX.'partner_order`
		 '.$hold.' 
		WHERE 
		 `order_id` = '.DB::quote($orderId).' 
		AND 
		 `partner_id` = '.DB::quote($partner['id'])
    );

    return $arg['result'];
  }

  /**
   * Проверяем, нужно ли отчислить коммисионные по пришедшей оплате для заказа с сервисов оплаты
   * @param $arg - параметры переданые из payment.php
   */
  static function updateOrder($arg) {
    $orderId = $arg["args"]["paymentOrderId"];
    $model = new Models_Order;
    $order = $model->getOrder(PREFIX.'order.id='.$orderId);

    // если статус заказа становится "Оплачен или выполнен", то отправляем письмо админу, о том что заказ оформлен благодаря партнеру.
    // в базе сохраняется привязка. Если в последствии изменить статус, то привязка останется!
    if ($order[$orderId]['status_id'] == 2) {
      $partner = self::closeOrderPartner($orderId);

      if (empty($partner)) {
        return true;
      }
      $done = '';
      if ($partner['date_done'] == '0000-00-00') {
        $done = ', `date_done` = now()';
      }
      DB::query('
        UPDATE `'.PREFIX.'partner_order`
        SET `hold` = 0'.$done.'
        WHERE 
        `order_id` = '.DB::quote($orderId).' 
          AND 
        `partner_id` = '.DB::quote($partner['id'])
      );

      // разблокируем начисленые  деньги за заказ
      //Отправляем админам
      $sitename = MG::getSetting('sitename');
      $message = 'Заказ #'.$orderId.' был оплачен после перехода 
        по реферальной ссылке <b>партнера #'.$partner['id'].'</b>.
        На счет пользователя <b>'.$partner['email'].'</b> 
        зачислены коммисионные '.MG::priceCourse($partner['summ']).' '.MG::getSetting('currency');

      $mails = explode(',', MG::getSetting('adminEmail'));
      foreach ($mails as $mail) {
        if (preg_match('/^[-._a-zA-Z0-9]+@(?:[a-zA-Z0-9][-a-zA-Z0-9]+\.)+[a-zA-Z]{2,6}$/', $mail)) {
          Mailer::addHeaders(array("Reply-to" => MG::getSetting('noReplyEmail')));
          Mailer::sendMimeMail(array(
            'nameFrom' => $sitename,
            'emailFrom' => MG::getSetting('noReplyEmail'),
            'nameTo' => $sitename,
            'emailTo' => $mail,
            'subject' => $partner['percent'].'% от заказа #'.$partner['id'].' зачислено на счет партнеру '.$partner['email'],
            'body' => $message,
            'html' => true
          ));
        }
     }
    }

    return true;
  }

  /**
   * При добавлении нового заказа проверяем, наличие партнерской куки.
   */
  static function partnerToOrder($arg) {

    $partnerId = self::getPartnerCookie();
    $partner = self::getPartner(self::getPartnerCookie($partnerId));
    $orderId = $arg['result']['id']; //для новой верси
    $orderNum = $arg['result']['orderNumber'];
    if (!empty($partner) && $orderId) {
      $model = new Models_Order;
      $order = $model->getOrder(PREFIX.'order.id='.$orderId);

      $summ = $partner['percent'] * $order[$orderId]['summ'] / 100;
      self::addConnetcToPartner($partnerId, $orderId, $partner['percent'], $summ, $orderNum);
    }

    return $arg['result'];
  }

  /**
   * Добавляет в таблицу partner_orders привязку.
   */
  static function addConnetcToPartner($partnerId, $orderId, $percent, $summ, $orderNum) {

    DB::query("
      INSERT IGNORE INTO  `".PREFIX."partner_order` 
        (`partner_id`, `order_id`, `percent`, `summ`, `order_number`)
      VALUES (".DB::quote($partnerId).", ".DB::quote($orderId).", ".DB::quote($percent).", ".DB::quote($summ).", ".DB::quote($orderNum).")"
    );
    DB::query("UPDATE `".PREFIX."partner_order` SET `date_order` = now() 
      WHERE `order_id` = ".DB::quote($orderId)."");
    $res = DB::query("UPDATE `".PREFIX."partner` SET `orders_count` = `orders_count`+1 
      WHERE `id` =".DB::quote($partnerId));
  }

  /**
   * Обработчик шотркода вида [data-balance].
   * Выводит информацию о  заработанных средствах партнера, и о том cколько было выплаченно в общей сумме
   */
  static function getBalance() {

    $id = USER::getThis()->id;

    $option = MG::getSetting('partners-program');
    $option = stripslashes($option);
    $options = unserialize($option);
    $currency = MG::getSetting('currency');
    $status = array(0 => 'Не доступно', 1 => 'Выплачен', 2 => 'Запрос отправлен', 3 => 'Доступно', 4 => 'Отказ');
    $statusRequest = array(0 => 'Не доступно', 1 => 'Выполнен', 2 => 'Запрос отправлен', 4 => 'Отказ');
    $data = array('nopartner' => true);

    $html = '
      <div class="partnerProgram">
      <div class="becomePartner">'.self::affiliatePanel()."</div>";
    if(!empty(self::$_partner[$id])) {
      $row = self::$_partner[$id];
      $data = self::$_partner[$id];
    } else {
    $result = DB::query('
      SELECT *
      FROM `'.PREFIX.'partner`
      WHERE `user_id` = '.DB::quote($id)
    );
    if ($row = DB::fetchAssoc($result)) {
      self::$_partner[$id] = $row;
      $data = $row;
    }
    }
    // если пользователь является партнером, обращаемся к бд за информацией о его запросах и заказах
    if (!empty(self::$_partner[$id]) ) {
      self::checkOrderPartner($row['id']);        // проверка дат выполненных заказов
      // получение информации о балансе партнера
      $balance = DB::query('
        SELECT sum(summ) as balance
        FROM `'.PREFIX.'partner_order`
        WHERE `partner_id` = '.DB::quote($row['id']).' 
        AND `hold` = 0 AND `status` != 1'
      );
      if ($balanceData = DB::fetchAssoc($balance)) {
        $data['balance'] = $balanceData['balance'] ? $balanceData['balance'] : 0;
      }
      // информация о сумме запрошенных средств, которые ожидают ответа от администратора
      $result = DB::query('
        SELECT sum(summ) as request
        FROM `'.PREFIX.'partner_payments_request`
        WHERE `partner_id` = '.DB::quote($row['id']).'
         AND `status` = 2'
      );
      if ($account = DB::fetchAssoc($result)) {
        $data['request'] = $account['request'] ? $account['request'] : 0;
      }

      // информация о сумме доступной к выводу - от заказов после выполнения которых прошло N дней
      $result = DB::query('
        SELECT sum(summ) as able
        FROM `'.PREFIX.'partner_order`
        WHERE `partner_id` = '.DB::quote($row['id']).'
         AND `status` = 3
         AND `hold` = 0'
      );
      if ($canRealize = DB::fetchAssoc($result)) {
        $data['exitbalance'] = $canRealize['able'] ? $canRealize['able'] : 0;
      }
      // получаем данные о том какая страница таблицы запрошена, какая таблица открыта у пользователя и сколько записей выаодить 
      $page = URL::get("page") ? URL::get("page") : "0";
      $numberTable = URL::get("table") ? URL::get("table") : "1";
      $countRows = $_COOKIE['countRowsPartnersProgram'] ? $_COOKIE['countRowsPartnersProgram'] : 20;
      $active1 = '';
      $active2 = '';
      if (1 == $numberTable) {
        $active1 = 'active';
      } else if (2 == $numberTable) {
        $active2 = 'active';
      }
      // таблица выполненных заказов по ссылке партнера и сумма которую он может получить от этого заказа
      $table = '
      SELECT order_id, summ, date_done, status, order_number
      FROM `'.PREFIX.'partner_order`
      WHERE `partner_id` = '.DB::quote($row['id']).' 
      AND `hold` = 0 ORDER BY date_done DESC';
      $navOrders = new Navigator($table, $page, $countRows); //определяем класс
      $table = $navOrders->getRowsSql();
      $pagtable = $navOrders->getPager();

      //таблицы запросов на вывод средст от партнера 
      $statements = '
        SELECT *
        FROM `'.PREFIX.'partner_payments_request`
        WHERE `partner_id` = '.DB::quote($row['id']).' ORDER BY `date_add` DESC';

      $navRequest = new Navigator($statements, $page, $countRows); //определяем класс
      $statements = $navRequest->getRowsSql();
      $pagtable2 = $navRequest->getPager();

      // блок информации о договоре и ссылка на него, если договор 
      // в партнерской программе обязателен, если нет - блок не выводится
      $dataContract = ($data['contract'] == 0 && $options['contract'] == 'true') ? '0' : '1';
      $linkContract = ($options["contractLink"] && $options["contractLink"] != '') ? SITE.$options["contractLink"] : '';
      if ($linkContract != '') {
        $linkHtml = '<a href = "'.$linkContract.'">Скачать договор</a> ';
      } else {
        $linkHtml = '<p>Для получения договора обратитесь к администратору.</p>';
      }
      $htmlContract = '<div class="contract">
            <span>Для получения заработанных средств, необходимо подписать 
            договор и отправить подписанный экземпляр администратору сайта! </span>';
      $partnerContract = ($data['contract'] == 1) ? '<p>Договор партнерской программы подписан! </p>' : '';
      $htmlContract .= $linkHtml.$partnerContract.'</div>';
      if ($options['contract'] == 'true') {
        $html .= $htmlContract;
      }
      $tableOrderHtml = '
        <div id="table1" style="display:none"><table class="widget-table-partner order">
          <thead>
            <tr>
              <th>№</th>
              <th>Сумма</th>
              <th>Дата выполнения заказа</th>
              <th>Статус</th>
              <th class="choose">Отметить</th>
            </tr>
          </thead>
          <tbody class="partner-orders-tbody">';

      if (!$table) {
        $tableOrderHtml .=' <tr class="noneOrders"><td colspan="5">Нет выполненных заказов.</td></tr>';
      } else {
        foreach ($table as $order) {
          $tableOrderHtml .= '<tr id="'.$order['order_id'].'" data-status ='.$order['status'].'>
					<td class="number">
				   '.$order['order_number'].'  
					</td>
					<td class="summ">'.MG::priceCourse($order['summ']).' '.$currency.'</td>
					<td class="date">'.MG::dateConvert($order['date_done']).' ['.date('H:i', strtotime($order['date_done'])).']'.'</td>
					<td class="status">'.$status[$order['status']].'</td>
					<td class="action"><input type="checkbox" name="pay" data-summa='.$order['summ'].' value=false ></td> 
				  </tr>';
        }
      }

      $tableOrderHtml .= '
        </tbody>
        </table> '.$pagtable.'<div class="totalSum" data-min = '.self::$exitMoneyLimit.'>
        Итого к выводу: <span class="total-request"></span> '.$currency.'
        <span id = "actionPartner">
        <button class="showFormOrderParnet" data-currency ="'.$currency.'" data-contract ='.$dataContract.' >
          Отправить заявку на вывод средств</button></span>
        <div class=error style ="display:none">
        Для запроса и получения средств необходимо подписать договор: '.$linkHtml.'
        </div></div>
        <div class="link-result">Внимание! Выплата доступных средств осуществляется после '.($options['period'] ? $options['period'] : 30).' дней от выполнения заказа. 
        Минимальная сумма вывода <strong>'.self::$exitMoneyLimit.' '.$currency.'</strong> !</div>';


      $tableRequestHtml = '<table class="widget-table-partner request">
          <thead>
            <tr>
              <th>id запроса</th>
              <th>Дата</th>
              <th>Сумма</th>
              <th>Номера заказов</th>
              <th>Статус</th>
              <th>Комменатрий</th>
              <th>Статус изменен</th>
            </tr>
          </thead>
          <tbody class="partner-request-tbody">';
      if (!$statements) {
        $tableRequestHtml .=' <tr class="noneRequest"><td colspan="7">Запросов нет</td></tr>';
      } else {
        foreach ($statements as $request) {
          $dateDone = ($request['status'] == 1 || $request['status'] == 4 ) ? MG::dateConvert($request['date_done']) : '';
          $tableRequestHtml .= '<tr id="'.$request['id'].'" >
            <td class="id_request">'.$request['id'].'</td>
            <td class="dateRequest">'.MG::dateConvert($request['date_add']).'</td>
            <td class="summRequest">'.MG::priceCourse($request['summ']).' '.$currency.'</td>
            <td class="ordersNum">'.$request['orders_numbers'].'</td>
            <td class="statusRequest">'.$statusRequest[$request['status']].'</td>
            <td class="comment">'.$request['comment'].'</td> 
            <td>'.$dateDone.'</td>
            </tr>';
        }
      }
      $tableRequestHtml.= '
        </tbody>
        </table>'.$pagtable2;
    }

    if (!$data['nopartner']) {
      $html .= '
      <div class="blockBalance">
      
      <ul>
        <li><span class="bold-text">
        Баланс:</span> <span class="payment-count">'.MG::priceCourse($data['balance']).' '.$currency.'</span></li>
        <li><span class="bold-text">
        Можно вывести:</span> '.MG::priceCourse($data['exitbalance']).' '.$currency.'</li>
        <li><span class="bold-text">
        Выплачено:</span> '.MG::priceCourse($data['payments_amount']).' '.$currency.'</li>
        <li><span class="bold-text">
        Запрошен счет на:</span> '.MG::priceCourse($data['request']).' '.$currency.'</li> 
        <li><span class="bold-text">
        Всего переходов по Вашей ссылке:</span> '.$data['count'].', 
        оформленных заказов: '.$data['orders_count'].' </li>  
      </ul>
      <div class="showAccountStatements" data-active = '.$numberTable.' >
        <a href ="affiliate?table=1" id="historyOrder" 
          class="default-btn '.$active1.'" data-partner="'.$row['id'].'">Выполненные заказы</a>
        <a href ="affiliate?table=2" id="historyRequest" 
          class="default-btn '.$active2.'" data-partner="'.$row['id'].'">История запросов</a>
      </div>
        <div class="filter-panel"><span id="count-items">Выводить записей в таблице:</span>
          <select class="count-items-dropdown" data-count='.$countRows.'>
             <option value="10">10</option>
             <option value="20">20</option>
             <option value="30">30</option>
             <option value="50">50</option>
             <option value="100">100</option>
          </select>
      </div>'
        .$tableOrderHtml.
        '</div>
      <div id="table2" class="statements" style="display:none" > 
      '.$tableRequestHtml.'
      </div></div>';
    }
    $html .= '</div>';
    return $html;
  }

  /**
   * Обработчик шотркода вида [data-balance].
   * Выводит ссылку на сраницу, где можно узнать информацию о заработанных деньгах
   */
  static function getBalanceInPersonal() {
    $id = USER::getThis()->id;
    $data = array('nopartner' => true);
    $result = DB::query('
      SELECT `id`, `percent`
      FROM `'.PREFIX.'partner`
      WHERE `user_id` = '.DB::quote($id)
    );
    // если пользователь является партнером, обращаемся к бд за информацией о его запросах и заказах

    if ($row = DB::fetchAssoc($result)) {
      $data = $row;
    }
    $html = '
    <div class="partnerProgram">
    <div class="becomePartner">'.self::affiliatePanel($data)."</div>";

    if (!$data['nopartner']) {
      $html .= ' Узнать о заказах, оформленных по Вашей ссылке, 
        отправить запрос на получение заработанных средств,
        и просмотреть историю запросов Вы можете 
        на странице <a href ='.SITE.'/affiliate >Партнерская программа</a>';
    }
    $html .= '</div>';
    return $html;
  }

  /**
   * Устанавливаем  значение партнерской куки на год и 
   * прибавляет в бд количество переходов по ссылке партнера
   */
  static function setPartnerCookie($id) {
    if (isset($_COOKIE['parnerId']) && (($_COOKIE['parnerId']) == $id)) {
      return true;
    }
    SetCookie('parnerId', $id, time() + 3600 * 24 * 365);
    DB::query(' 
      UPDATE `'.PREFIX.'partner` SET `count` = `count`+ 1
      WHERE `id` = '.DB::quote($id)
    );
  }

  /**
   * Получаем значение партнерской куки
   */
  static function getPartnerCookie() {
    return isset($_COOKIE['parnerId']) ? $_COOKIE['parnerId'] : false;
  }

  /**
   * Получаем параметры партнера
   */
  static function getPartner($id) {
    $result = array();

    $res = DB::query("
        SELECT *
        FROM `".PREFIX."partner`
        WHERE id = ".DB::quote($id)
    );

    if ($row = DB::fetchAssoc($res)) {
      $result = $row;
    }

    return $result;
  }

  /**
   * Получаем  параметры партнера при  закрытии заказа оформленного  по его ссылке.
   * id - заказа 
   */
  static function closeOrderPartner($id) {
    $result = array();

    $res = DB::query("
        SELECT u.email, p.*, po.*
        FROM `".PREFIX."partner_order` as po
        LEFT JOIN `".PREFIX."partner` as p ON po.partner_id = p.id
        LEFT JOIN `".PREFIX."user` as u ON u.id = p.user_id
        WHERE po.order_id = ".DB::quote($id)
    );

    if ($row = DB::fetchAssoc($res)) {
      $result = $row;
    }

    return $result;
  }

  //выводит партнерскую ссылку если ссылки нет, то предлагает стать партнером
  static function affiliatePanel($data = null) {
    $id = USER::getThis()->id;

    if (!$id) {
      return 'Пожалуйста, <a href="'.SITE.'/registration">зарегистрируйтесь</a>, 
        чтобы принять участие в партнерской программе '.MG::get('sitename').' 
        и получать '.self::$percent.'% от стоимости заказов ваших друзей и знакомых.';
    }
    
    $parnterLink = false;
    if(!$data) {
      if(!empty(self::$_partner[$id])) {
      $data = self::$_partner[$id];
    } else {
    $result = DB::query('
      SELECT *
      FROM `'.PREFIX.'partner`
      WHERE `user_id` = '.DB::quote($id)
    );
    if ($row = DB::fetchAssoc($result)) {
      self::$_partner[$id] = $row;
      $data = $row;
    }
    }
    }
    if (!empty($data['id'])) {
      $parnterLink = SITE."?partnerId=".$data['id'];
      $percent = $data['percent'] ? $data['percent'] : self::$percent;
    }
    if ($parnterLink) {
      return '<div class="accostPartner"><p>Уважаемый, партнер! 
        Ваша реферальная ссылка: <a href="'.$parnterLink.'">'.$parnterLink.'</a></p>
        <p>Передайте ее друзьям и знакомым, 
        и вы получите '.$percent.'% от стоимости их заказа.</p></div>';
    } else {
      $html = '  
      
      Здравствуйте, '.USER::getThis()->name.' '.USER::getThis()->sname.', 
        мы предлагаем Вам стать нашим партнером и 
        получать '.self::$percent.'% от всех заказов клиентов, привлеченных вами.
        <button id="becomePartner" class="default-btn">Получить реферальную сылку</button>
        ';
      return $html;
    }
  }

  /**
   * Проверяет статус и дату проведения заказка для корректного отображения 
   * id - заказа 
   */
  static function checkOrderPartner($id = null) {
    $option = MG::getSetting('partners-program');
    $option = stripslashes($option);
    $options = unserialize($option);
    $period = $options['period'] ? $options['period']  : 30;
    $result = array();
    $partner_id = '1';
    if ($id != null) {
      $partner_id = " `partner_id` = ".DB::quote($id);
    }
    $res = DB::query("
        SELECT `date_done`, `order_id`, `request_id`, `partner_id`, `status`
        FROM `".PREFIX."partner_order` 
        WHERE ".$partner_id."
        AND `hold` = 0
        AND (`status` = 0 OR `status` = 3 OR `status` = 4)"
    );

    while($row = DB::fetchAssoc($res)) {
      if ((strtotime(date("Y-m-d")) - strtotime($row['date_done']) >= $period * 24 * 60 * 60) 
        && ($row['request_id'] == 0)) {
        if ($row['status']!='4') {
          DB::query("
          UPDATE `".PREFIX."partner_order`
          SET `status` = 3
          WHERE 
            `order_id` = ".DB::quote($row['order_id'])." 
            AND 
            `partner_id` = ".DB::quote($row['partner_id']).""
          );
        }
      } else {
        DB::query("
        UPDATE `".PREFIX."partner_order`
        SET `status` = 0
		    WHERE 
          `order_id` = ".DB::quote($row['order_id'])." 
		       AND 
		      `partner_id` = ".DB::quote($row['partner_id']).""
        );
      }
    }

    return true;
  }

}
