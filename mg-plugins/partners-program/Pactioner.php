<?php

/**
 * Класс Pactioner наследник стандарного Actioner
 * Предназначен для выполнения действий,  AJAX запросов плагина 
 *
 * @author Avdeev Mark <mark-avdeev@mail.ru>
 */
class Pactioner extends Actioner {

  private $pluginName = 'partners-program';

  /**
   * Сохраняет  опции плагина
   * @return boolean
   */
  public function saveBaseOption() {
    $this->messageSucces = 'Настройки применены';
    $this->messageError = 'Настройки не применены';
    if (!empty($_POST['data'])) {
      MG::setOption(array('option' => 'partners-program', 
        'value' => addslashes(serialize($_POST['data']))));
    }
    return true;
  }

  /**
   * Сохраняет  информацию о партнере
   * @return boolean
   */
  public function saveInfoPartner() {
    USER::AccessOnly('1,4', 'exit()');
    $this->messageSucces = 'Изменения сохранены';
    $this->messageError = 'Изменения не удалось сохранить';

    if (!empty($_POST['data'])) {
      $contract = $_POST['data']['contract'] == 'true' ? 1 : 0;
      DB::query('UPDATE `'.PREFIX.'partner`
        SET `percent` = '.DB::quote($_POST['data']['percent']).',
        `contract` = '.DB::quote($contract).', `about` = '.DB::quote($_POST['data']['about']).'
        WHERE id='.DB::quote($_POST['data']['id']).';');
    }
    $this->data = $_POST['data'];
    return true;
  }

  /**
   * формирует список запросов от партнеров
   */
  public function getPartnerRequest() {
    USER::AccessOnly('1,4', 'exit()');
    $option = MG::getSetting('partners-program');
    $option = stripslashes($option);
    $options = unserialize($option);
    $period = $options['period'] ? $options['period'] : 30;
    $request = DB::query(" SELECT *
      FROM  `".PREFIX."partner_payments_request` 
      WHERE id = ".DB::quote($_POST['id'])."
      ORDER BY date_add DESC ");
    if ($row = DB::fetchAssoc($request)) {
      $order = explode(',', $row['orders_id']);
      foreach ($order as $order_id) {
        $date = DB::query('SELECT `date_done`, `order_number` 
          FROM `'.PREFIX.'partner_order`  
          WHERE `order_id` = '.DB::quote($order_id).'');
        $date_done = DB::fetchAssoc($date);
        if (strtotime(date("Y-m-d H:i:s")) - strtotime($date_done['date_done']) >= $period * 24 * 60 * 60) {
          $this->data['warning'] = "Заказы выполнены ".$period." дней назад, 
            партнер может получить запрошенные средства. ";
          $this->data['error'] = 0;
        } else {
          $this->data['error'] = 1;
          $this->data['warning'] = "ВНИМАНИЕ! 
            Заказ ".$date_done['order_number']." выполнен менее ".$period." дней назад! 
            Выполнение запроса на вывод средств недоступно.";
        }
      }
      $result = DB::query('
        SELECT u.email
        FROM  `'.PREFIX.'partner` AS p
        INNER JOIN  `'.PREFIX.'user` AS u ON u.id = p.user_id
        AND p.id = '.DB::quote($row['partner_id'])
      );
      if ($partner = DB::fetchAssoc($result)) {
        $this->data['email'] = $partner['email'];
      }
      $this->data['request'] = $row;
    } else {
      $this->data = $_POST;
      $this->data['none'] = 1;
    }
    return true;
  }

  /**
   * удаляет из бд парнера и записей оформленных заказов и запросов от этого партнера
   */
  public function deletePartner() {
    USER::AccessOnly('1,4', 'exit()');
    $this->messageSucces = 'Партнер удален';
    $this->messageError = 'Невозможно удалить';
    if (DB::query('DELETE FROM `'.PREFIX.'partner` WHERE `id`= '.DB::quote($_POST['id']))) {
      DB::query('DELETE FROM `'.PREFIX.'partner_order` WHERE `partner_id`= '.DB::quote($_POST['id']));
      DB::query('DELETE FROM `'.PREFIX.'partner_payments_request` WHERE `partner_id`= '.DB::quote($_POST['id']));
      return true;
    }
    return false;
  }

  /**
   * удаляет из бд заказ
   */
  public function deleteOrder() {
    USER::AccessOnly('1,4', 'exit()');
    $this->messageSucces = 'Заказ удален';
    $this->messageError = 'Невозможно удалить';
    DB::query('DELETE FROM `'.PREFIX.'partner_order` WHERE `order_id`= '.DB::quote($_POST['id']));
    return true;
  }

  /**
   * удаляет из бд заказ
   */
  public function deleteRequest() {
    USER::AccessOnly('1,4', 'exit()');
    $this->messageSucces = 'Запрос удален';
    $this->messageError = 'Невозможно удалить';
    DB::query('DELETE FROM `'.PREFIX.'partner_payments_request` WHERE `id`= '.DB::quote($_POST['id']));
    return true;
  }

  //Выплата с записью в историю
  public function paymentToPartner() {
    USER::AccessOnly('1,4', 'exit()');
    $this->messageSucces = 'Изменения сохранены';
    $this->messageError = 'Изменения сохранить не удалось';

    $result = DB::query('SELECT `status`, `comment` FROM `'.PREFIX.'partner_payments_request` 
      WHERE `id` = '.DB::quote($_POST['request_id']).'');
    if ($row = DB::fetchAssoc($result)) {
      if ($row['status'] == $_POST['status']) {
        if ($row['comment'] != $_POST['comment']) {
          DB::query('UPDATE `'.PREFIX.'partner_payments_request` SET 
            `comment` = '.DB::quote($_POST['comment']).' 
            WHERE `id` = '.DB::quote($_POST['request_id']).'');
          $this->messageSucces = 'Комментарий изменен';
        }
        $this->data['request'] = $_POST;
        return true;
      }
    }
    if ($row['status'] != 1 && $row['status'] != 4) {
      DB::query('UPDATE `'.PREFIX.'partner_payments_request` 
        SET `status` = '.DB::quote($_POST['status']).', `comment` = '.DB::quote($_POST['comment']).' 
        WHERE `id` = '.DB::quote($_POST['request_id']).'');
      DB::query('UPDATE `'.PREFIX.'partner_order` 
        SET `status` = '.DB::quote($_POST['status']).' 
        WHERE `request_id` = '.DB::quote($_POST['request_id']).'');
      DB::query('UPDATE `'.PREFIX.'partner_payments_request` SET `date_done` = now() 
        WHERE `id` = '.DB::quote($_POST['request_id']).'');
      
      if ($_POST['status'] == '4') {
        $result = DB::query('SELECT `orders_id`
          FROM `'.PREFIX.'partner_payments_request` WHERE `id` = '.DB::quote($_POST['request_id']).'');
        if ($row = DB::fetchAssoc($result)) {
          DB::query('UPDATE `'.PREFIX.'partner_order` SET `request_id` = 0 
         WHERE `order_id` IN ('.DB::quote($row['orders_id'], true).')');
        }
           
      }
      if ($_POST['status'] == '1') {
        DB::query('UPDATE `'.PREFIX.'partner_payments_request` SET `date_done` = now() 
              WHERE `id` = '.DB::quote($_POST['request_id']).'');
        $result = DB::query('SELECT `partner_id`, `summ` 
          FROM `'.PREFIX.'partner_payments_request` WHERE `id` = '.DB::quote($_POST['request_id']).'');
        if ($row = DB::fetchAssoc($result)) {
          DB::query('UPDATE '.PREFIX.'partner
          SET payments_amount = payments_amount + '.DB::quote($row['summ']).'
          WHERE id='.DB::quote($row['partner_id']).'');
        }
      }
    } else {
      $this->messageSucces = 'Статус Выполнен или Отказ изменить невозможно';
      $this->data['request'] = $_POST;
      $this->data['request']['status'] = $row['status'];
      return true;
    }
    $this->data['request'] = $_POST;
    return true;
  }

  /**
   * Устанавливает количество отображаемых записей в разделе 
   * @return boolean
   */
  public function setCountPrintRowsEnity() {
    //доступно только модераторам и админам.
    USER::AccessOnly('1,4', 'exit()');

    $count = 20;
    if (is_numeric($_POST['count']) && !empty($_POST['count'])) {
      $count = $_POST['count'];
    }

    MG::setOption(array('option' => $_POST['option'], 'value' => $count));
    return true;
  }

  //Сколько всего заработал партнер и сколько ему выплачено
  public function getPartnerBalanse() {

    $data = array('id' => $_POST['id']);

    $result = DB::query('
        SELECT p.* , u.email
        FROM  `'.PREFIX.'partner` AS p
        INNER JOIN  `'.PREFIX.'user` AS u ON u.id = p.user_id
        AND p.id = '.DB::quote($_POST['id'])
    );

    if ($partner = DB::fetchAssoc($result)) {
      $data['links'] = $partner['count'] ? $partner['count'] : 0;
      $data['contract'] = $partner['contract'];
      $data['percent'] = $partner['percent'];
      $data['about'] = $partner['about'];
      $data['email'] = $partner['email'];
      $data['orders'] = $partner['orders_count'] ? $partner['orders_count'] : 0;
      $data['amount'] = $partner['payments_amount'] ? MG::priceCourse($partner['payments_amount']) : 0;
    }

    $result = DB::query('
        SELECT sum(`summ`) as balance
        FROM `'.PREFIX.'partner_order`
        WHERE `partner_id` = '.DB::quote($_POST['id']).'
        AND `hold` = 0 AND `status` != 1 AND `status` != 4  '
    );

    if ($balance = DB::fetchAssoc($result)) {
      $data['balance'] = $balance['balance'] ? MG::priceCourse($balance['balance']) : 0;
    }

    $result = DB::query('
        SELECT sum(summ) as request
        FROM `'.PREFIX.'partner_payments_request`
        WHERE `partner_id` = '.DB::quote($_POST['id']).'
         AND `status` = 2'
    );

    if ($row5 = DB::fetchAssoc($result)) {
      $data['request'] = $row5['request'] ? MG::priceCourse($row5['request']) : 0;
    }
    $result = DB::query('
        SELECT sum(summ) as able
        FROM `'.PREFIX.'partner_order`
        WHERE `partner_id` = '.DB::quote($row['id']).'
         AND `status` = 3
         AND `hold` = 0'
    );

    if ($row4 = DB::fetchAssoc($result)) {
      $data['exitbalance'] = $row4['able'] ? MG::priceCourse($row4['able']) : 0;
    }

    $this->data['info'] = $data;

    $table = DB::query('
      SELECT *
      FROM `'.PREFIX.'partner_order`
      WHERE `partner_id` = '.DB::quote($_POST['id']).' 
      AND `hold` = 0 ORDER BY `date_done` DESC'
    );
    if ($table) {
      while ($row = DB::fetchAssoc($table)) {
        $row['summ'] = MG::priceCourse($row['summ']);
        $row['date_done'] = MG::dateConvert($row['date_done']).' ['.date('H:i', strtotime($row['date_done'])).']';
        $order[] = $row;
      }
    }
    $this->data['order'] = $order;
    return true;
  }

}
