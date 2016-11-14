<?php

/**
 * Класс Partner наследник стандарного Actioner
 * Предназначен для выполнения действий, запрошеных  AJAX функциями
 *
 * @author Avdeev Mark <mark-avdeev@mail.ru>
 */
class Partner extends Actioner {

  //Отправка заявки админам на выплату партнеру указанной суммы
  public function sendOrderToPayment() {
    $id = USER::getThis()->id;
    
    $data = array('nopartner' => true);
    $result = DB::query('
      SELECT *
      FROM `'.PREFIX.'partner`
      WHERE `user_id` = '.DB::quote($id)
    );
    if ($row = DB::fetchAssoc($result)) {
      $orders = substr($_POST['orders'], 0, -1);
      $numbers = substr($_POST['numbers'], 0, -1);
      $res = DB::query("
        SELECT date_done, order_id, request_id, order_number
        FROM `".PREFIX."partner_order` 
        WHERE `order_id` IN (".DB::quote($orders, true).')');
      $error = 0;
      $errorOrdres = '';
      $errorRequest = '';
      while ($check = DB::fetchAssoc($res)) {
        if ($check["date_done"] == '0000-00-00' || 
          (strtotime(date("Y-m-d H:i:s")) - strtotime($check["date_done"]) < $period * 24 * 60 * 60)) {
          $error = 1;
          $errorOrdres .= $check["order_number"]." ";
        }
        if ($check["request_id"] != 0) {
          $error = 1;
          $errorRequest .= $check["order_number"]." ";
        }
      }
      if ($error == 1) {
        $errorDate = $errorOrdres == '' ? '' : 'Заказ № '.$errorOrdres.' 
          не проходят проверку истечения '.$period.' дней после его выполнения! Повторите попытку через день.';
        $errorRepeat = $errorRequest == '' ? '' : 'Запрос на вывод средств от заказов №№ '.$errorRequest.' 
          уже отправлен. Повторный запрос невозможен.';
        $this->data['error'] = $errorDate.' '.$errorRepeat;
        return false;
      } else {
        $data = $row;
        $sitename = MG::getSetting('sitename');
        $subj = 'Партнер #'.$row['id'].' на сайте '.$sitename.' отправил запрос на получение выплаты';
        $msg = 'Партнер #'.$row['id'].' на сайте '.$sitename.' отправил запрос на получение выплаты в размере 
          <b>'.MG::priceCourse($_POST['summ']).' '.MG::getSetting('currency').'</b>        
          <br/> Воспользуйтесь <a href="'.SITE.'/mg-admin">панелью администрирования</a>, 
          чтобы проверить информацию о партнере и его заработке.';

        $mails = explode(',', MG::getSetting('adminEmail'));
        // Отправка заявки админам
        foreach ($mails as $mail) {
          $mail = trim($mail);
          if (preg_match('/^[A-Za-z0-9._-]+@[A-Za-z0-9_-]+.([A-Za-z0-9_-][A-Za-z0-9_]+)$/', $mail)) {
            Mailer::addHeaders(array("Reply-to" => $this->email));
            Mailer::sendMimeMail(array(
              'nameFrom' => USER::getThis()->sname." ".USER::getThis()->name,
              'emailFrom' => USER::getThis()->email,
              'nameTo' => $sitename,
              'emailTo' => $mail,
              'subject' => $subj,
              'body' => $msg,
              'html' => true
            ));
          }
        }

        //оповещение на мыло партнера
        Mailer::sendMimeMail(array(
          'nameFrom' => $sitename,
          'emailFrom' => "noreply@".$sitename,
          'nameTo' => USER::getThis()->sname." ".USER::getThis()->name,
          'emailTo' => USER::getThis()->email,
          'subject' => 'Отправлена заявка на получение партнерской выплаты на сайте '.$sitename,
          'body' => 'Вами была отправлена заявка на получение партнерской выплаты 
            на сайте '.$sitename.' в размере <b>'.MG::priceCourse($_POST['summ']).' '.MG::getSetting('currency').'</b>
            <br/>Пожалуйста, дождитесь пока мы свяжемся с Вами по электронной почте для учтонения способов перевода денежных средств.
            <br/>Данное письмо сформированно роботом, отвечать на него не надо.',
          'html' => true
        ));

        // сохранение запросы на вывод средств в БД
        $date = date("Y-m-d H:i:s");


        DB::query('INSERT INTO '.PREFIX.'partner_payments_request (`partner_id`, `orders_id`, `orders_numbers`, `date_add`, `summ`, `status`) '
          .'VALUES('.DB::quote($row['id']).', '.DB::quote($orders).', '.DB::quote($numbers).','.DB::quote($date).','.DB::quote($_POST['summ']).', 2);');
        $requestId = DB::insertId();

        DB::query('UPDATE '.PREFIX.'partner_order SET `status` = 2, `request_id` = '.DB::quote($requestId).' 
          WHERE `order_id` IN ('.DB::quote($orders, true).')');
        $this->data = array(
          'id' => $requestId,
          'date_add' => MG::dateConvert($date).' ['.date('H:i', strtotime($date)).']',
          'summ' => MG::priceCourse($_POST['summ']),
        );
      }
    }
    return true;
  }

  /*
   * Добавление пользователя в партнеры по его запросу
   */

  public function becomePartner() {
    if (!USER::getThis()->id) {
      return false;
    } else {
      DB::query('INSERT INTO '.PREFIX.'partner (user_id,percent,payments_amount)
       VALUES('.DB::quote(USER::getThis()->id).','.DB::quote(PartnerProgram::$percent).',0);');
    }
    return true;
  }

}
