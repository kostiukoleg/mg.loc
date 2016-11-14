<?php

class Controllers_Payment extends BaseController {

  public $msg = "";

  function __construct() {
    $this->msg = "";
    $paymentID = URL::getQueryParametr('id');
    $paymentStatus = URL::getQueryParametr('pay');
    $_POST['url'] = URL::getUrl();
    $modelOrder = new Models_Order();

    switch ($paymentID) {
      case 1: //webmoney
        $msg = $this->webmoney($paymentID, $paymentStatus);
        break;
      case 5: //robokassa
        $msg = $this->robokassa($paymentID, $paymentStatus);
        break;
      case 6: //qiwi
        $msg = $this->qiwi($paymentID, $paymentStatus);
        break;
      case 8: //interkassa
        $msg = $this->interkassa($paymentID, $paymentStatus);
        break;
      case 2: //ЯндексДеньги    
        $msg = $this->yandex($paymentID, $paymentStatus);
        break;
      case 9: //PayAnyWay
        $msg = $this->payanyway($paymentID, $paymentStatus);
        break;
      case 10: //PayMastert
        $msg = $this->paymaster($paymentID, $paymentStatus);
        break;
      case 11: //alfabank
        $msg = $this->alfabank($paymentID, $paymentStatus);
        break;
      case 14: //Яндекс.Касса
        $msg = $this->yandexKassa($paymentID, $paymentStatus);
        break;
      case 15: //privat24
        $msg = $this->privat24($paymentID, $paymentStatus);
        break;
      case 16: //LiqPay
        $msg = $this->liqpay($paymentID, $paymentStatus);
        break;
      case 17: //Sberbank
        $msg = $this->sberbank($paymentID, $paymentStatus);
        break;
    }

    $this->data = array(
      'payment' => $paymentID, //id способа оплаты
      'status' => $paymentStatus, //статус ответа платежной системы (result, success, fail)
      'message' => $msg, //статус ответа платежной системы (result, success, fail)
    );
  }

  /**
   * Действие при оплате заказа.
   * Обновляет статус заказа на Оплачен, отправляет письма оповещения, генерирует хук.
   */
  public function actionWhenPayment($args) {
    $result = true;
    ob_start();

    $order = new Models_Order();
    if (method_exists($order, 'updateOrder')) {
      $order->updateOrder(array('id' => $args['paymentOrderId'], 'status_id' => 2));
    }
    if (method_exists($order, 'sendMailOfPayed')) {
      $order->sendMailOfPayed($args['paymentOrderId'], $args['paymentAmount'], $args['paymentID']);
    }
    if (method_exists($order, 'sendLinkForElectro')) {
      $order->sendLinkForElectro($args['paymentOrderId']);
    }

    $content = ob_get_contents();
    ob_end_clean();

    // если в ходе работы метода допущен вывод контента, то записать в лог ошибку.
    if (!empty($content)) {
      MG::loger('ERROR PAYMENT: ' . $content);
    }

    return MG::createHook(__CLASS__ . "_" . __FUNCTION__, $result, $args);
  }

  /**
   * Проверка платежа через WebMoney.
   */
  public function webmoney($paymentID, $paymentStatus) {
    $order = new Models_Order();
    
    if ('success' == $paymentStatus) {
  
    if(empty($_POST['LMI_PAYMENT_NO'])){
      echo "ERR: НЕКОРРЕКТНЫЕ ДАННЫЕ ЗАКАЗА";
        exit;
    }
    
      $orderInfo = $order->getOrder(" id = " . DB::quote(intval($_POST['LMI_PAYMENT_NO']), 1));
      $msg = 'Вы успешно оплатили заказ №' . $orderInfo[$_POST['LMI_PAYMENT_NO']]['number']; 
      $msg .= $this->msg;
    } elseif ('result' == $paymentStatus && $_POST) {      
      $paymentAmount = trim($_POST['LMI_PAYMENT_AMOUNT']);
      //$paymentAmount = $paymentAmount*1;
      $paymentOrderId = trim($_POST['LMI_PAYMENT_NO']);
      if (!empty($paymentAmount) && !empty($paymentOrderId)) {
        $orderInfo = $order->getOrder(" id = " . DB::quote(intval($paymentOrderId), 1) . " and summ+delivery_cost = " . DB::quote($paymentAmount, 1));
        $paymentInfo = $order->getParamArray($paymentID, $orderInfo['id'], $orderInfo['summ']);
      }

      $payeePurse = trim($paymentInfo[0]['value']);
      $secretKey = trim($paymentInfo[1]['value']);
      $alg = $paymentInfo[3]['value'];
      // предварительная проверка платежа
      if ($_POST['LMI_PREREQUEST'] == 1) {
        $error = false;

        if (empty($orderInfo)) {
          echo "ERR: НЕКОРРЕКТНЫЕ ДАННЫЕ ЗАКАЗА";
          exit;
        }

        if (trim($_POST['LMI_PAYEE_PURSE']) != $payeePurse) {
          echo "ERR: НЕВЕРНЫЙ КОШЕЛЕК ПОЛУЧАТЕЛЯ " . $_POST['LMI_PAYEE_PURSE'];
          exit;
        }
        echo "YES";
        exit;
      } else {
        // проверка хэша, присвоение нового статуса заказу
        $chkstring = $_POST['LMI_PAYEE_PURSE'] .
          $_POST['LMI_PAYMENT_AMOUNT'] .
          $_POST['LMI_PAYMENT_NO'] .
          $_POST['LMI_MODE'] .
          $_POST["LMI_SYS_INVS_NO"] .
          $_POST["LMI_SYS_TRANS_NO"] .
          $_POST["LMI_SYS_TRANS_DATE"] .
          $secretKey .
          $_POST["LMI_PAYER_PURSE"] .
          $_POST["LMI_PAYER_WM"];
        
        $md5sum = strtoupper(hash($alg, $chkstring));

        if ($_POST['LMI_HASH'] == $md5sum) {
          $this->actionWhenPayment(
            array(
              'paymentOrderId' => $paymentOrderId,
              'paymentAmount' => $paymentAmount,
              'paymentID' => $paymentID
            )
          );
          echo "YES";
          exit;
        } else {
          echo "ERR: Произошла ошибка или подмена параметров.";
          exit;
        }
      }
    } else {
      $msg = 'Оплата не удалась';
    }

    return $msg;
  }

  /**
   * Проверка платежа через paymaster.
   */
  public function paymaster($paymentID, $paymentStatus) {
    $order = new Models_Order();
    if ('success' == $paymentStatus) {
      $orderInfo = $order->getOrder(" id = " . DB::quote(intval($_POST['LMI_PAYMENT_NO']), 1));
      $msg = 'Вы успешно оплатили заказ №' . $orderInfo[$_POST['LMI_PAYMENT_NO']]['number']; 
      $msg .= $this->msg;
    } elseif ('result' == $paymentStatus && $_POST) {
      $paymentAmount = trim($_POST['LMI_PAYMENT_AMOUNT']);
      //$paymentAmount = $paymentAmount*1;
      $paymentOrderId = trim($_POST['LMI_PAYMENT_NO']);
      if (!empty($paymentAmount) && !empty($paymentOrderId)) {
        $orderInfo = $order->getOrder(" id = " . DB::quote(intval($paymentOrderId), 1) . " and summ+delivery_cost = " . DB::quote($paymentAmount, 1));
        $paymentInfo = $order->getParamArray($paymentID, $orderInfo['id'], $orderInfo['summ']);
      }

      $payeePurse = trim($paymentInfo[0]['value']);
      $secretKey = trim($paymentInfo[1]['value']);
      $alg =  $paymentInfo[2]['value'];
      // предварительная проверка платежа
      if ($_POST['LMI_PREREQUEST'] == 1) {
        $error = false;

        if (empty($orderInfo)) {
          echo "ERR: НЕКОРРЕКТНЫЕ ДАННЫЕ ЗАКАЗА";
          exit;
        }

        echo "YES";
        exit;
      } else {

        $chkstring = $_POST['LMI_MERCHANT_ID'] . ";" .
          $_POST['LMI_PAYMENT_NO'] . ";" .
          $_POST['LMI_SYS_PAYMENT_ID'] . ";" .
          $_POST['LMI_SYS_PAYMENT_DATE'] . ";" .
          $_POST['LMI_PAYMENT_AMOUNT'] . ";" .
          $_POST['LMI_CURRENCY'] . ";" .
          $_POST['LMI_PAID_AMOUNT'] . ";" .
          $_POST['LMI_PAID_CURRENCY'] . ";" .
          $_POST['LMI_PAYMENT_SYSTEM'] . ";" .
          $_POST['LMI_SIM_MODE'] . ";" .
          $secretKey;

        $md5sum = base64_encode(hash($alg,$chkstring, true));

        if ($_POST['LMI_HASH'] == $md5sum) {

          $this->actionWhenPayment(
            array(
              'paymentOrderId' => $paymentOrderId,
              'paymentAmount' => $paymentAmount,
              'paymentID' => $paymentID
            )
          );
          echo "YES";
          exit;
        } else {
          echo "ERR: Произошла ошибка или подмена параметров.";
          exit;
        }
        $msg = 'Оплата не удалась';
      }
    }

    return $msg;
  }

  /**
   * Проверка платежа через ROBOKASSA.
   */
  public function robokassa($paymentID, $paymentStatus) {
    $order = new Models_Order();
    if ('success' == $paymentStatus) {
      if(!empty($_POST['InvId'])){
        $orderInfo = $order->getOrder(" id = " . DB::quote(intval($_POST['InvId']), 1));
        $msg = 'Вы успешно оплатили заказ №' . $orderInfo[$_POST['InvId']]['number']; 
      }else{
        $msg = 'Не указан номер заказа!';
      }
      
      $msg .= $this->msg;
    } elseif ('result' == $paymentStatus && isset($_POST)) {    
      $paymentAmount = trim($_POST['OutSum']);
      $paymentOrderId = trim($_POST['InvId']);
      if (!empty($paymentAmount) && !empty($paymentOrderId)) {
        $orderInfo = $order->getOrder(" id = " . DB::quote(intval($paymentOrderId), 1) . " and summ+delivery_cost = " . DB::quote($paymentAmount, 1));
        $paymentInfo = $order->getParamArray($paymentID, $orderInfo['id'], $orderInfo['summ']+$orderInfo['delivery_cost']);
      }
      // предварительная проверка платежа
      if (empty($orderInfo)) {
        echo "ERR: НЕКОРРЕКТНЫЕ ДАННЫЕ ЗАКАЗА";
        exit;
      }

      $sMerchantPass2 = trim($paymentInfo[2]['value']);
      $alg = $paymentInfo[3]['value'];
      $sSignatureValue = $paymentAmount . ':' . $paymentOrderId . ':' . $sMerchantPass2;
      $md5sum = strtoupper(hash($alg,$sSignatureValue));

      if ($_POST['SignatureValue'] == $md5sum) {
        $this->actionWhenPayment(
          array(
            'paymentOrderId' => $paymentOrderId,
            'paymentAmount' => $paymentAmount,
            'paymentID' => $paymentID
          )
        );

        echo "OK" . $paymentOrderId;
        exit;
      }
    } else {
      $msg = 'Оплата не удалась';
    }

    return $msg;
  }

  /**
   * Проверка платежа через QIWI.
   */
  public function qiwi($paymentID, $paymentStatus) {

    $order = new Models_Order();
    if ('success' == $paymentStatus) {
      $orderInfo = $order->getOrder(" id = " . DB::quote(intval($_GET['order']), 1));
      $msg = 'Вы успешно оплатили заказ №' . $orderInfo[$_GET['order']]['number']; 
      $msg .= $this->msg;
    } elseif ('result' == $paymentStatus && isset($_POST)) {
      $i = file_get_contents('php://input');

      $l = array('/<login>(.*)?<\/login>/', '/<password>(.*)?<\/password>/');
      $s = array('/<txn>(.*)?<\/txn>/', '/<status>(.*)?<\/status>/');

      preg_match($l[0], $i, $m1);
      preg_match($l[1], $i, $m2);

      preg_match($s[0], $i, $m3);
      preg_match($s[1], $i, $m4);

      $paymentOrderId = $m3[1];

      $statusQiwi = $m4[1];


      if (!empty($paymentOrderId)) {
        $orderInfo = $order->getOrder(" id = " . DB::quote(intval($paymentOrderId), 1));
      } else {
        $orderInfo = NULL;
        echo "Ошибка обработки";
        exit();
      }


      $paymentInfo = $order->getParamArray($paymentID, $paymentOrderId, $orderInfo[$paymentOrderId]['summ']);
      $password = trim($paymentInfo[1]['value']);
      $alg = $paymentInfo[2]['value'];
      $parseLog .=
        ' status=' . $statusQiwi .
        ' paymentOrderId=' . $paymentOrderId .
        ' paymentID=' . $paymentID .
        ' summ=' . $orderInfo[$paymentOrderId]['summ'];

      // если заказа не существует то отправляем код 150
      if (empty($orderInfo)) {
        $resultCode = 300;
      } else {

        $hash = strtoupper(hash($alg,$m3[1] . strtoupper(hash($alg,$password))));

        if ($hash !== $m2[1]) { //сравнение хешей
          $resultCode = 150;
        } else {
          if ($statusQiwi == 60) {// заказ оплачен         
            $this->actionWhenPayment(
              array(
                'paymentOrderId' => $paymentOrderId,
                'paymentAmount' => $orderInfo[$paymentOrderId]['summ'],
                'paymentID' => $paymentID
              )
            );
          }
          $resultCode = 0; // все прошло успешно оправляем "0"
        }
      }
      header('content-type: text/xml; charset=UTF-8');
      echo '<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="http://client.ishop.mw.ru/"><SOAP-ENV:Body><ns1:updateBillResponse><updateBillResult>' . $resultCode . '</updateBillResult></ns1:updateBillResponse></SOAP-ENV:Body></SOAP-ENV:Envelope>';
      exit;
    }

    return $msg;
  }

  /**
   * Проверка платежа через Interkassa.
   */
  public function interkassa($paymentID, $paymentStatus) {
    $order = new Models_Order();
    if ('success' == $paymentStatus) {
      $orderInfo = $order->getOrder(" id = " . DB::quote(intval($_POST['ik_pm_no']), 1));
      $msg = 'Вы успешно оплатили заказ №' . $orderInfo[$_POST['ik_pm_no']]['number'];      
      $msg .= $this->msg;
    } elseif ('result' == $paymentStatus && isset($_POST)) {
  
      $paymentAmount = trim($_POST['ik_am']);
      $paymentOrderId = trim($_POST['ik_pm_no']);
      if (!empty($paymentAmount) && !empty($paymentOrderId)) {
        $orderInfo = $order->getOrder(" id = " . DB::quote(intval($paymentOrderId), 1) . " and summ+delivery_cost = " . DB::quote($paymentAmount, 1));
        $paymentInfo = $order->getParamArray($paymentID, $paymentOrderId, $orderInfo[$paymentOrderId]['summ']);
      }
      // предварительная проверка платежа
      if (empty($orderInfo)) {
        echo "ERR: НЕКОРРЕКТНЫЕ ДАННЫЕ ЗАКАЗА";
        exit;
      }


      $testKey = '*****';
      $normKey = trim($paymentInfo[1]['value']);
      $alg = $paymentInfo[3]['value'];
      $signString = $_POST['ik_co_id'];
      $key = $normKey;
      if (!empty($_POST['ik_pw_via']) && $_POST['ik_pw_via'] == 'test_interkassa_test_xts') {
        $key = $testKey;
      }

      $dataSet = $_POST;
      unset($dataSet['url']);
      unset($dataSet['ik_sign']);
      ksort($dataSet, SORT_STRING); // сортируем по ключам в алфавитном порядке элементы массива 
      array_push($dataSet, $key); // добавляем в конец массива "секретный ключ"    
      $signString = implode(':', $dataSet); // конкатенируем значения через символ ":" 
      $sign = base64_encode(hash($alg,$signString, true)); // берем MD5 хэш в бинарном виде по

      if ($sign == $_POST['ik_sign']) {
        $this->actionWhenPayment(
          array(
            'paymentOrderId' => $paymentOrderId,
            'paymentAmount' => $orderInfo[$paymentOrderId]['summ'],
            'paymentID' => $paymentID
          )
        );
        echo "200 OK";
        exit;
      } else {
        echo "Подписи не совпадают!";
        exit;
      }
    }
    return $msg;
  }

  /**
   * Проверка платежа через Interkassa.
   */
  public function payanyway($paymentID, $paymentStatus) {
    $order = new Models_Order();

    if ('success' == $paymentStatus) {
      $paymentOrderId = trim(URL::getQueryParametr('MNT_TRANSACTION_ID'));
      $orderInfo = $order->getOrder(" id = " . DB::quote(intval($paymentOrderId), 1));
      $msg = 'Вы успешно оплатили заказ №' . $orderInfo[$paymentOrderId]['number'];
      $msg .= $this->msg;
            
      $this->actionWhenPayment(
        array(
          'paymentOrderId' => $paymentOrderId,
          'paymentAmount' => $orderInfo[$paymentOrderId]['summ'] + $orderInfo[$paymentOrderId]['delivery_cost'],
          'paymentID' => $paymentID
        )
      );
    } elseif ('result' == $paymentStatus && isset($_POST)) {
      $paymentAmount = trim($_POST['MNT_AMOUNT']);
      $paymentOrderId = trim($_POST['MNT_TRANSACTION_ID']);

      if (!empty($paymentAmount) && !empty($paymentOrderId)) {
        $orderInfo = $order->getOrder(" id = " . DB::quote(intval($paymentOrderId), 1) . " and summ+delivery_cost = " . DB::quote($paymentAmount, 1));
        $paymentInfo = $order->getParamArray($paymentID, $paymentOrderId, $orderInfo[$paymentOrderId]['summ'] + $orderInfo[$paymentOrderId]['delivery_cost']);
      }

      // предварительная проверка платежа
      if (empty($orderInfo)) {
        echo "FAIL";
        exit;
      }

      $testmode = 0;

      if ($paymentInfo[2]['value'] == 'true') {
        $testmode = 1;
      }

      $account = trim($paymentInfo[0]['value']);
      $securityCode = trim($paymentInfo[1]['value']);

      // предварительная проверка платежа обработка команды CHECK
      if ($_POST['MNT_COMMAND'] == 'CHECK') {
        $summ = sprintf("%01.2f", $orderInfo[$paymentOrderId]['summ'] + $orderInfo[$paymentOrderId]['delivery_cost']);
        $currency = (MG::getSetting('currencyShopIso') == "RUR") ? "RUB" : MG::getSetting('currencyShopIso');
        $alg = $paymentInfo[3]['value'];
        $sign = hash($alg, $_POST['MNT_COMMAND'] . $account . $paymentOrderId . $summ . $currency . $testmode . $securityCode);
        
        if ($sign == $_POST['MNT_SIGNATURE']) {
          $signNew = hash($alg, '402' . $account . $paymentOrderId . $securityCode);
          $responseXml = '<?xml version="1.0" encoding="UTF-8"?>
            <MNT_RESPONSE>
            <MNT_ID>' . $account . '</MNT_ID>
            <MNT_TRANSACTION_ID>' . $paymentOrderId . '</MNT_TRANSACTION_ID>
            <MNT_RESULT_CODE>402</MNT_RESULT_CODE>
            <MNT_DESCRIPTION>Оплата заказа ' . $paymentOrderId . '</MNT_DESCRIPTION>
            <MNT_AMOUNT>' . ($orderInfo[$paymentOrderId]['summ'] + $orderInfo[$paymentOrderId]['delivery_cost']) . '</MNT_AMOUNT>
            <MNT_SIGNATURE>' . $signNew . '</MNT_SIGNATURE>
            </MNT_RESPONSE>';
          header("Content-type: text/xml");
          echo $responseXml;
        } else {
          echo "Подписи не совпадают!";
        }
        
        exit;
      } elseif (isset($_POST['MNT_OPERATION_ID'])) {
        $summ = sprintf("%01.2f", $orderInfo[$paymentOrderId]['summ'] + $orderInfo[$paymentOrderId]['delivery_cost']);
        $currency = (MG::getSetting('currencyShopIso') == "RUR") ? "RUB" : MG::getSetting('currencyShopIso');
        $alg = $paymentInfo[3]['value'];
        $sign = hash($alg, $_POST['MNT_COMMAND'] . $account . $paymentOrderId . $_POST['MNT_OPERATION_ID'] . $summ . $currency . $testmode . $securityCode);

        if ($sign == $_POST['MNT_SIGNATURE']) {
          $signNew = hash($alg, '200' . $account . $paymentOrderId . $securityCode);
          $responseXml = '<?xml version="1.0" encoding="UTF-8"?>
            <MNT_RESPONSE>
            <MNT_ID>' . $account . '</MNT_ID>
            <MNT_TRANSACTION_ID>' . $paymentOrderId . '</MNT_TRANSACTION_ID>
            <MNT_RESULT_CODE>200</MNT_RESULT_CODE>
            <MNT_SIGNATURE>' . $signNew . '</MNT_SIGNATURE>
            </MNT_RESPONSE>';

          header("Content-type: text/xml");
          echo $responseXml;
        } else {
          echo "Подписи не совпадают!";
        }
        
        exit;
      }
    }
    
    return $msg;
  }

  /**
   * Проверка платежа через Yandex.
   */
  public function yandex($paymentID, $paymentStatus) {
    $order = new Models_Order();
    if ('success' == $paymentStatus) {      
      $orderInfo = $order->getOrder(" id = " . DB::quote(intval($_POST['label']), 1));
      $msg = 'Вы успешно оплатили заказ №' . $orderInfo[$_POST['label']]['number'];
      $msg .= $this->msg;
    } elseif ('result' == $paymentStatus && isset($_POST)) {     
      $paymentAmount = trim($_POST['withdraw_amount']);
      $paymentOrderId = trim($_POST['label']);
      if (!empty($paymentAmount) && !empty($paymentOrderId)) {
        $orderInfo = $order->getOrder(" id = " . DB::quote(intval($paymentOrderId), 1) . " and summ+delivery_cost = "
          . DB::quote($paymentAmount, 1));
        $paymentInfo = $order->getParamArray($paymentID, $paymentOrderId, $orderInfo[$paymentOrderId]['summ']);
      }
      // предварительная проверка платежа
      if (empty($orderInfo)) {
        echo "ERR: НЕКОРРЕКТНЫЕ ДАННЫЕ ЗАКАЗА";
        exit;
      }

      $secret = trim($paymentInfo[1]['value']);
      $alg = $paymentInfo[3]['value'];
      $pre_sha = $_POST['notification_type'] . '&' .
        $_POST['operation_id'] . '&' .
        $_POST['amount'] . '&' .
        $_POST['currency'] . '&' .
        $_POST['datetime'] . '&' .
        $_POST['sender'] . '&' .
        $_POST['codepro'] . '&' .
        $secret . '&' .
        $_POST['label'];

      $sha = hash($alg,$pre_sha);
      if ($sha == $_POST['sha1_hash']) {
        $this->actionWhenPayment(
          array(
            'paymentOrderId' => $paymentOrderId,
            'paymentAmount' => $orderInfo[$paymentOrderId]['summ'],
            'paymentID' => $paymentID
          )
        );
        echo "0";
        exit;
      } else {
        echo "1";
        exit;
      }
    }
    return $msg;
  }
  
  /*
   * Проверка платежа через Яндекс.Кассу.
   */
  public function yandexKassa($paymentID, $paymentStatus){
    $order = new Models_Order();
    $action = URL::getQueryParametr('action');
    $orderNumber = URL::getQueryParametr('orderNumber');
    $orderId = URL::getQueryParametr('orderId');
    
    if($paymentStatus == 'success'){
      //$orderInfo = $order->getOrder(" number = " . DB::quote($orderNumber));
      $msg = 'Вы успешно оплатили заказ №' . $orderNumber;
      $msg .= $this->msg;
      return $msg;
    }elseif($paymentStatus == 'fail'){
      //$orderInfo = $order->getOrder(" number = " . DB::quote($orderNumber));
      $msg = 'При попытке оплаты заказа №'.$orderNumber.' произошла ошибка.<br />Пожалуста, попробуте позже или используйте другой способ оплаты';
      $msg .= $this->msg;
      return $msg;
    }
    
    $error = false;
    
    $orderSumAmount = URL::getQueryParametr('orderSumAmount');
    $orderSumCurrencyPaycash = URL::getQueryParametr('orderSumCurrencyPaycash');
    $orderSumBankPaycash = URL::getQueryParametr('orderSumBankPaycash');
    $shopId = URL::getQueryParametr('shopId');
    $invoiceId = URL::getQueryParametr('invoiceId');
    $customerNumber = URL::getQueryParametr('customerNumber');
    $key = URL::getQueryParametr('md5');
    
    $responseXml = '<?xml version="1.0" encoding="UTF-8"?> ';
    
    if($action == 'paymentAviso'){
      $responseXml .= '<paymentAvisoResponse ';
    }else{
      $responseXml .= '<checkOrderResponse ';
    }
    
    $responseXml .= 'performedDatetime="'.date('c').'" ';
    
    if(!empty($orderSumAmount) && !empty($orderNumber) && !empty($orderId)) {
      $orderInfo = $order->getOrder(" number = " . DB::quote($orderNumber) . " and summ+delivery_cost = " . DB::quote($orderSumAmount, 1));
      $paymentInfo = $order->getParamArray($paymentID, $orderNumber, $orderInfo[$orderId]['summ']);
      $shopPassword = trim($paymentInfo[3]['value']);
      $alg= $paymentInfo[4]['value'];
    }else{
      $error = true;
      $responseXml .= 'code="200"
        message="не пришла сумма или номер"';
    }
    
    //action;orderSumAmount;orderSumCurrencyPaycash;orderSumBankPaycash;shopId;invoiceId;customerNumber;shopPassword 
    if(!empty($orderInfo)){
      $hash = strtoupper(hash($alg,$action.';'.$orderSumAmount.';'.$orderSumCurrencyPaycash.';'.$orderSumBankPaycash.';'.$shopId.';'.$invoiceId.';'.$customerNumber.';'.$shopPassword));
      
      if($action == 'checkOrder'){
        if($hash == $key){
          $responseXml .= 'code="0" ';
        }else{
          $responseXml .= 'code="1" ';
        }
      }elseif($action == 'paymentAviso'){
        if($hash == $key){
          $responseXml .= 'code="0" ';
        }else{
          $responseXml .= 'code="1" paymentAviso ';
        }
        
        if($orderInfo[$orderId]['status_id']!=2 && $orderInfo[$orderId]['status_id']!=4 && $orderInfo[$orderId]['status_id']!=5){
          $orderInfo = $order->getOrder(" number = " . DB::quote($orderNumber));
          $this->actionWhenPayment(
            array(
              'paymentOrderId' => $orderId,
              'paymentAmount' => $orderInfo[$orderNumber]['summ'],
              'paymentID' => $paymentID
            )
          );
        }
      }else{
        $responseXml .= 'code="200"
          message="Неизвестное действие"';
      } 
    }elseif(!$error){
      $responseXml .= '
        code="200"
        message="Указаны неверные параметры заказа"';
    }
    
    $responseXml .= '
      invoiceId="'.$invoiceId.'" 
      shopId="'.$shopId.'" />';

    header('content-type: text/xml; charset=UTF-8');
    echo $responseXml;
    exit;
  }
  
  /**
   * Проверка платежа через AlfaBank.
   */
  public function alfabank($paymentID, $paymentStatus) {
  
    $order = new Models_Order();
    if ('result' == $paymentStatus && isset($_POST)) {     
      // если пользователь вернулся на страницу после оплаты, проверяем статус заказа
      if (isset($_REQUEST['orderId'])) {
        $paymentInfo = $order->getParamArray($paymentID, null, null);
        $serverUrl = (empty($paymentInfo[2]['value'])) 
                ? "https://engine.paymentgate.ru" : $paymentInfo[2]['value'];
        $jsondata = file_get_contents($serverUrl.'/payment/rest/getOrderStatusExtended.do?language=ru&orderId='
          . $_REQUEST['orderId'] . '&userName=' . trim($paymentInfo[0]['value']) . '&password='
          . trim($paymentInfo[1]['value']));
        $obj = json_decode($jsondata);

        // приводим сумму заказа к нормальному виду
        $obj->amount = substr($obj->amount, 0, - 2) . "." . substr($obj->amount, -2);

        // приводим номер заказа к нормальному виду
        $orderNumber = explode('/', $obj->orderNumber);
        $obj->orderNumber = $orderNumber[0];

        $paymentAmount = trim($obj->amount);
        $paymentOrderId = trim($obj->orderNumber);

        // проверяем имеется ли в базе заказ с такими параметрами
        if (!empty($paymentAmount) && !empty($paymentOrderId)) {
          $orderInfo = $order->getOrder(" id = " . DB::quote(intval($paymentOrderId), 1) . " and summ+delivery_cost = "
            . DB::quote($paymentAmount, 1));
        }

        // если заказа с таким номером и стоимостью нет, то возвращаем ошибку
        if (empty($orderInfo)) {
          echo "ERR: НЕКОРРЕКТНЫЕ ДАННЫЕ";
          exit;
        }

        // если заказ есть и он успешно оплачен в банке
        if ($obj->errorCode == 0 && $obj->actionCode==0) {
          // высылаем письма админу и пользователю об успешной оплате заказа, 
      // только если его действующий статус не равен "оплачен" или "выполнен" или "отменен"   
      if($orderInfo[$paymentOrderId]['status_id']!=2 && $orderInfo[$paymentOrderId]['status_id']!=4 && $orderInfo[$paymentOrderId]['status_id']!=5){
        $this->actionWhenPayment(
        array(
          'paymentOrderId' => $paymentOrderId,
          'paymentAmount' => $orderInfo[$paymentOrderId]['summ'],
          'paymentID' => $paymentID
        )
        );
      }
          $msg = 'Вы успешно оплатили заказ №' . $orderInfo[$paymentOrderId]['number'];
          $msg .= $this->msg;
        }else{
      $msg = $obj->actionCodeDescription;
    }
    
      } else {
        //Запрос в альфабанк на формирование ссылки для перенаправления клиента к платежной форме
        if (!empty($_POST['paymentAlfaBank'])) {
          $paymentAmount = trim($_POST['amount']);
          $paymentOrderId = trim($_POST['orderNumber']);
          if (!empty($paymentAmount) && !empty($paymentOrderId)) {
            $orderInfo = $order->getOrder(" id = " . DB::quote(intval($paymentOrderId), 1) . " and summ+delivery_cost = " . DB::quote($paymentAmount, 1));
            $paymentInfo = $order->getParamArray($paymentID, $paymentOrderId, $orderInfo[$paymentOrderId]['summ']);
          }
          // предварительная проверка платежа
          if (empty($orderInfo)) {
            echo "ERR: НЕКОРРЕКТНЫЕ ДАННЫЕ ЗАКАЗА";
            exit;
          }

          $_POST['orderNumber'] = $_POST['orderNumber'] . '/' . time();
          $_POST['userName'] = trim($paymentInfo[0]['value']);
          $_POST['password'] = trim($paymentInfo[1]['value']);
          $_POST['amount'] = number_format($_POST['amount'], 2, '', '');
          $serverUrl = (empty($paymentInfo[2]['value'])) 
                ? "https://engine.paymentgate.ru" : $paymentInfo[2]['value'];
          $jsondata = file_get_contents($serverUrl.'/payment/rest/register.do?amount=' . $_POST['amount'] . '&currency='
            . $_POST['currency'] . '&language=' . $_POST['language'] . '&orderNumber=' . $_POST['orderNumber']
            . '&returnUrl=' . urlencode($_POST['returnUrl']) . '&userName=' . $_POST['userName'] . '&password='
            . $_POST['password']. '&description=' . $_POST['description']);
          $obj = json_decode($jsondata);
    
          // если произошла ошибка
          if (!empty($obj->errorCode)) {
            echo "ERR: " . $obj->errorMessage;
            exit;
          }

          // если ссылка сформированна, то отправляем клиента в альфабанк
          if (!empty($obj->orderId) && !empty($obj->formUrl)) {
            header('Location: ' . $obj->formUrl);
          }

          exit;
        }
      }
    }
    return $msg;
  }
  
  /*
   * Проверка платежа через liqpay.
   */
  private function liqpay($paymentID, $paymentStatus){ 
    $data = json_decode(base64_decode($_POST['data']));
    $orderId = URL::getQueryParametr('order_id'); 

    if(intval($orderId) > 0){
      $orderId = intval($orderId);
      $order = new Models_Order(); 
      $orderInfo = $order->getOrder(" id = " . DB::quote($orderId, 1));
      
      if(!empty($orderInfo)){
        if(in_array($orderInfo[$orderId]['status_id'], array(2,5))){
          $msg = 'Вы успешно оплатили заказ №' . $orderInfo[$orderId]['number'];
          $msg .= $this->msg;
          $pay = 'success';
        }else{
          $msg = 'Неудалось произвести оплату заказа №' . $orderInfo[$orderId]['number'].'. Используйте другой способ оплаты, или попробуте позже.';
          $pay = 'fail';    
        }
      }else{
        $msg = 'Заказа, с указанным идентификатором не существует с системе';       
        $pay = 'fail';
      }      

      if(empty($paymentStatus)){
        MG::redirect(URL::getUri().'&pay='.$pay);
      }
      
      return $msg;
    }    
    
    if('result' == $paymentStatus && isset($_POST)){
      
      if(empty($_POST['data']) || empty($_POST['signature'])){
        $msg = "Не верный ответа от сервиса оплаты";
        return $msg;
      }
      
      if($data->status == 'failure') {
        $msg = 'Неуспешный платеж';
        return $msg;
      }
      
      if($data->status == 'error') {
        $msg = 'Неуспешный платеж. Некорректно заполнены данные';
        return $msg;
      }
      
      if($data->status == 'reversed') {
        $msg = 'Платеж возвращен';
        return $msg;
      }
      
      $order = new Models_Order();              
      $received_public_key = $data->public_key;
      $paymentOrderId = $data->order_id;
      $orderInfo = $order->getOrder(" id = " . DB::quote(intval($paymentOrderId), 1));
      
      if(empty($orderInfo)){
        $msg = 'Заказа, с указанным идентификатором не существует с системе';
        return $msg;
      }
      
      $paymentInfo = $order->getParamArray($paymentID, $paymentOrderId, $orderInfo[$paymentOrderId]['summ']);
      $publicKey = trim($paymentInfo[0]['value']);
      $privateKey = trim($paymentInfo[1]['value']);
      $sign = base64_encode(sha1($privateKey.$_POST['data'].$privateKey, 1));
      $paymentAmount = $data->amount;
      
      if($sign != $_POST['signature'] || $publicKey != $received_public_key){
        $msg = "Не совпадает подпись или ключ доступа";
        return $msg;
      }else if($data->status == 'success'){
        $this->actionWhenPayment(
          array(
            'paymentOrderId' => $paymentOrderId,
            'paymentAmount' => $paymentAmount,
            'paymentID' => $paymentID
          )
        );
        
        $msg = 'Вы успешно оплатили заказ №' . $orderInfo[$paymentOrderId]['id'];      
        $msg .= $this->msg;
      }else{
        $msg = 'Во время оплаты произошла ошибка.';
      }
    }else{
      $msg = "Не верный ответа от сервиса оплаты";        
    }
    
    return $msg;
  }
  
  /*
   * Проверка платежа через privat24.
   */
  public function privat24($paymentID, $paymentStatus){
    $order = new Models_Order();
    
    if ('result' == $paymentStatus && isset($_POST)){
      $payment = $_POST['payment'];

      if($payment){
        $payment_array = array();
        parse_str($payment, $payment_array);

        $state = trim($payment_array['state']);
        $paymentOrderId = trim($payment_array['order']);
        $orderNumber = trim($payment_array['ext_details']);
        $paymentAmount = trim($payment_array['amt']);

        switch($state){
          case 'not found':
            $msg = "Платеж не найден";
            return $msg;
            break;
          case 'fail':
            $msg =  "Ошибка оплаты";
            return $msg;
            break;
          case 'incomplete':
            $msg = "Пользователь не подтвердил оплату";
            return $msg;
            break;
          case 'wait':
            $msg = "Платеж в ожидании";
            return $msg;
            break;
        }
        
        if (empty($paymentOrderId)){
          $msg = "Оплата не удалась";
          return $msg;
        }

        if (!empty($paymentAmount) && !empty($paymentOrderId)) {
          $orderInfo = $order->getOrder(" id = " . DB::quote(intval($paymentOrderId), 1));
          $paymentInfo = $order->getParamArray($paymentID, $paymentOrderId, $orderInfo[$paymentOrderId]['summ']);
          $merchant = trim($paymentInfo[0]['value']);
          $pass = trim($paymentInfo[1]['value']);
        }
  
        if (empty($orderInfo)) {
          $msg = "ERR: НЕКОРРЕКТНЫЕ ДАННЫЕ ЗАКАЗА";
          return $msg;
        }

        $amt = round($orderInfo[$paymentOrderId]['summ'], 2) + round($orderInfo[$paymentOrderId]['delivery_cost'], 2);
        $payment = 'amt='.$amt.'&ccy=UAH&details=заказ на '.SITE.'&ext_details='.$orderNumber.'&pay_way=privat24&order='.$paymentOrderId.'&merchant='.$merchant;
        $signature = sha1(md5($payment.$pass));

        $paymentSignatureString = 'amt=' . round($payment_array['amt'], 2) . '&ccy=' . $payment_array['ccy'] . '&details=' .  $payment_array['details'] . '&ext_details=' . $payment_array['ext_details'] . '&pay_way=' . $payment_array['pay_way'] . '&order=' . $payment_array['order'] . '&merchant=' . $payment_array['merchant'];
        $paymentSignature = sha1(md5($paymentSignatureString.$pass));

        if($paymentSignature !== $signature){
          $msg = "Подписи не совпадают!";
           return $msg;
        }

        $this->actionWhenPayment(
          array(
            'paymentOrderId' => $paymentOrderId,
            'paymentAmount' => $paymentAmount,
            'paymentID' => $paymentID
          )
        );

        $msg = 'Вы успешно оплатили заказ №' . $orderInfo[$paymentOrderId]['id'];      
        $msg .= $this->msg;

      }else{
        $msg = 'Оплата не удалась';
      }
    }else{
      $msg = 'Оплата не удалась';
    }
    return $msg;
  }
  
  public function sberbank($paymentID, $paymentStatus) {
    if ('result' == $paymentStatus && isset($_POST)) {
      $order = new Models_Order();
      $paymentInfo = $order->getParamArray($paymentID, null, null);
      $serverUrl = (empty($paymentInfo[2]['value'])) 
              ? "https://3dsec.sberbank.ru" : $paymentInfo[2]['value'];
      $userName = trim($paymentInfo[0]['value']);
      $password = trim($paymentInfo[1]['value']);

      if (!empty($_POST['paymentSberbank'])) {
        $paymentAmount = trim($_POST['amount']);
        $paymentOrderId = trim($_POST['orderNumber']);

        if (!empty($paymentAmount) && !empty($paymentOrderId)) {
          $orderInfo = $order->getOrder(" id = " . DB::quote($paymentOrderId, 1) 
              . " and summ+delivery_cost = " . DB::quote($paymentAmount, 1));
        }
        // предварительная проверка платежа
        if (empty($orderInfo)) {
          $msg =  "ERR: НЕКОРРЕКТНЫЕ ДАННЫЕ ЗАКАЗА";
          return $msg;
        }

        $orderNumber = $_POST['orderNumber'] . '/' . time();
        $amount = number_format($_POST['amount'], 2, '', '');

        $url = $serverUrl.'/payment/rest/register.do';
        $url .= '?userName=' . $userName . '&password=' . $password . '&amount=' . $amount  
            . '&currency=' . $_POST['currency'] . '&language=' . $_POST['language'] 
            . '&orderNumber=' . $orderNumber . '&description=' . $_POST['description'] 
            . '&returnUrl=' . urlencode($_POST['returnUrl']);
        $jsondata = file_get_contents($url);
        $objResponse = json_decode($jsondata);
        
        // если произошла ошибка
        if (!empty($objResponse->errorCode)) {
          $msg = "ERR: " . $objResponse->errorMessage;
          return $msg;
        }

        // если ссылка сформированна, то отправляем клиента в альфабанк
        if (!empty($objResponse->orderId) && !empty($objResponse->formUrl)) {
          header('Location: ' . $objResponse->formUrl);
        }

        exit;
      } else if (!empty($_REQUEST['orderId'])) {
        $url = $serverUrl.'/payment/rest/getOrderStatusExtended.do';
        $url .= '?userName=' . $userName . '&password=' . $password 
            . '&language=ru' . '&orderId=' . $_REQUEST['orderId'];

        $jsondata = file_get_contents($url);
        $objResponse = json_decode($jsondata);

        // если произошла ошибка
        if (!empty($objResponse->ErrorCode)) {
          $msg = "ERR: " . $objResponse->ErrorMessage;
          return $msg;
        }

        if ($objResponse->errorCode == 0 && $objResponse->orderStatus == 2 
            && $objResponse->actionCode == 0) {
          // приводим номер заказа к нормальному виду
          $orderNumber = explode('/', $objResponse->orderNumber);
          $paymentOrderId = $orderNumber[0];
          
          $paymentAmount = substr($objResponse->amount, 0, - 2) . "." . substr($objResponse->amount, -2);

          // проверяем имеется ли в базе заказ с такими параметрами
          if (!empty($paymentAmount) && !empty($paymentOrderId)) {
            $orderInfo = $order->getOrder(" id = " . DB::quote($paymentOrderId, 1) 
                . " and summ+delivery_cost = " . DB::quote($paymentAmount, 1));
          }

          // если заказа с таким номером и стоимостью нет, то возвращаем ошибку
          if (empty($orderInfo)) {
            $msg =  "ERR: НЕКОРРЕКТНЫЕ ДАННЫЕ";
            return $msg;
          }
          
          // высылаем письма админу и пользователю об успешной оплате заказа, 
          // только если его действующий статус не равен "оплачен" или "выполнен" или "отменен"   
          if ($orderInfo[$paymentOrderId]['status_id'] != 2 && $orderInfo[$paymentOrderId]['status_id'] != 4 && $orderInfo[$paymentOrderId]['status_id'] != 5) {
            $this->actionWhenPayment(
              array(
                'paymentOrderId' => $paymentOrderId,
                'paymentAmount' => $orderInfo[$paymentOrderId]['summ'],
                'paymentID' => $paymentID
              )
            );
          }

          $msg = 'Вы успешно оплатили заказ №' . $orderInfo[$paymentOrderId]['number'];
          $msg .= $this->msg;
        } else {
          $msg = $objResponse->actionCodeDescription;
        }

      }
    }

    return $msg;
  }

}
