<?php

/**
 * Контроллер: Order
 *
 * Класс Controllers_Order обрабатывает действия пользователей на 
 * странице оформления заказа.
 * - Производит проверку введенных данных в форму оформления заказа;
 * - Добавляет заказ в базу данных сайта;
 * - Для нового покупателя производится регистрация пользователя;
 * - Отправляет письмо с подтверждением заказа на указанный адрес покупателя 
 * и администратору сайта с составом заказа;
 * - Очищает корзину товаров, при успешном оформлении заказа;
 * - Перенаправляет на страницу с сообщеним об успешном оформлении заказа;
 * - Генерирует данные для страниц успешной и неудавшейся электронной оплаты 
 * товаров.
 *
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Controller
 */
class Controllers_Order extends BaseController {

  function __construct() {

    // Модель для работы заказом.
    $model = new Models_Order;  
    
    // Печать заказа в квитанцию.
    if (isset($_POST['printQittance'])) {
      $model->printQittance();
    }
    
    if ($orderId = URL::get('getOrderPdf')) {
      $model->getPdfOrder((int)$orderId);
    }

    // Запрос электронных товаров
    $fileToOrder = null;
    if (isset($_REQUEST['getFileToOrder'])) {
      $electroInfo = $model->getFileToOrder($_REQUEST['getFileToOrder']);

	  $orderInfo = $model->getOrder(' id = '.DB::quote(intval($_REQUEST['getFileToOrder'])));	
	  $orderNumber = $orderInfo[$_REQUEST['getFileToOrder']]['number'];
	  
      if ($electroInfo === false) {
        $infoMsg = "Для просмотра страницы необходимо зайти на сайт под пользователем сделавшим заказ №".$orderNumber;
      }

      if (is_array($electroInfo) && empty($electroInfo)) {
        $infoMsg = "Заказ  не содержит электронных товаров или ожидает оплаты!";
      };

      if (is_array($electroInfo) && !empty($electroInfo)) {
        $infoMsg = "Скачать электронные товары для заказа №".$orderNumber."";
      };
      $fileToOrder = array('infoMsg' => $infoMsg, 'electroInfo' => $electroInfo);
    }

    // пришел запрос на скачивание электронного товара
    if (isset($_REQUEST['link'])) {
      $model->getFileByMd5($_REQUEST['link']);
    }


    // Первый экран - Оформление заказа.
    $step = 1;

    // Если пришли данные с формы оформления заказа.
    if (isset($_POST['toOrder'])) {
      if (empty($_SESSION['cart'])) {
        MG::redirect('/cart');
      }

      // Если параметры введены корректно, то создается новый заказ.
      if ($error = $model->isValidData($_POST)) {
        $msg = $error;
      } else {
        // Второй экран - оплата заказа
        $step = 2;       
        mgAddCustomPriceAction(array(__CLASS__, 'applyRate'));
        $orderArray = $model->addOrder();
        $orderId = $orderArray['id'];
        $orderNumber = $orderArray['orderNumber'];
        $summ = $model->summ + $model->delivery_cost;
        $pay = $model->payment;
        $paramArray = $model->getParamArray($pay, $orderId, $summ);
      }
    }else{
      $_SESSION['price_rate'] = 0;
    }        

    // Обработка действия при переходе по ссылке подтверждения заказа.
    if ($id = URL::getQueryParametr('id')) {
      $info = $this->confirmOrder($id);
      $msg = $info['msg'];
      $userEmail = $info['userEmail'];
      // Третий экран - подтверждение заказа по ссылке из письма.
      $step = 3;
    }
     // Обработка действия при переходе по ссылке получения информации о статусе заказа.
    if (URL::getQueryParametr('hash')) {
      $hash = URL::getQueryParametr('hash');
      // Информация о заказе по переданному id.
      $orderInfo = $model->getOrder('`'.PREFIX.'order`.hash = '.DB::quote($hash));  
      $id = (key($orderInfo));        
      if ($orderInfo) {
        if (USER::getUserInfoByEmail($orderInfo[$id]['user_email'])) {
        $msg = 'Посмотреть статус заказа Вы можете в <a href="'.SITE.'/personal">личном кабинете</a>.';
      } else {
        $lang = MG::get('lang');
        $orderNumber = $orderInfo[$id]['number'];  
        $orderId = $id;     
        $status = $model->getOrderStatus($orderInfo[$id]['status_id']);
        $orderInfo[$id]['string_status_id'] = $lang[$status]; 
        $paymentArray = $model->getPaymentMethod($orderInfo[$id]['payment_id']);
        $orderInfo[$id]['paymentName'] = $paymentArray['name'];
        $msg = '';
      }       
      } else {
       $msg = 'Некорректная ссылка.<br> Заказ не найден<br>';
    }  
      // пятый экран - инфо о статусе заказа
      $step = 5;
    }

    // Запрос оплаты из ЛК.
    if (URL::getQueryParametr('pay')) {
      // Четвертый экран - Запрос оплаты из ЛК.
      $step = 4;
      $pay = URL::getQueryParametr('paymentId');
      $orderId = URL::getQueryParametr('orderID');
      $order = $model->getOrder(' id = '.DB::quote(intval($orderId), true));
      $summ = URL::getQueryParametr('orderSumm');
      $summ = $order[$orderId]['summ'] * 1 + $order[$orderId]['delivery_cost'] * 1;
      $paramArray = $model->getParamArray($pay, $orderId, $summ);
    }

    // Если пользователь авторизован, то заполняем форму личными даными.
    if (User::isAuth()) {
      $userInfo = User::getThis();
      $_POST['email'] = $_POST['email'] ? $_POST['email'] : $userInfo->email;
      $_POST['phone'] = $_POST['phone'] ? $_POST['phone'] : $userInfo->phone;
      $_POST['fio'] = $_POST['fio'] ? $_POST['fio'] : $userInfo->name.' '.$userInfo->sname;
      $_POST['address'] = $_POST['address'] ? $_POST['address'] : $userInfo->address;
      if ($userInfo->inn) {
        $_POST['customer'] = 'yur';
      }
      $_POST['yur_info']['nameyur'] = $userInfo->nameyur;
      $_POST['yur_info']['adress'] = $userInfo->adress;
      $_POST['yur_info']['inn'] = $userInfo->inn;
      $_POST['yur_info']['kpp'] = $userInfo->kpp;
      $_POST['yur_info']['bank'] = $userInfo->bank;
      $_POST['yur_info']['bik'] = $userInfo->bik;
      $_POST['yur_info']['ks'] = $userInfo->ks;
      $_POST['yur_info']['rs'] = $userInfo->rs;
    }

    // Обработка ajax запроса из шаблона.
    if ('getPaymentByDeliveryId' == URL::getQueryParametr('action')) {
      $this->getPaymentByDeliveryId();
    }
    
    // Обработка ajax запроса из шаблона.
    if ('setPaymentRate' == URL::getQueryParametr('action')) {
      $this->setPaymentRate();
    }

    // Обработка ajax запроса из шаблона.
    if ('getEssentialElements' == URL::getQueryParametr('action')) {
      $this->getEssentialElements();
    }
    
    //Обработка ajax запроса из редактирования заказа
    if('getDeliveryOrderOptions' == URL::getQueryParametr('action')){           
      $this->getDeliveryOrderOptions();
    }

    $this->includeIconsPack();
    // Массив способов доставки.    
    $deliveryArray = $this->getDelivery();
    // Массив способов оплаты.
    $deliveryCount = count($deliveryArray);  
   
    // если из доступных способов доставки - только один, то сразу находим для него способы оплаты
    if($deliveryCount===1){
      $keyDev = array_keys($deliveryArray);
      $_POST['delivery'] = $deliveryArray[$keyDev[0]]['id'];
    }
    
    $paymentTable = $this->getPaymentByDeliveryId($_POST['delivery'],$_POST['customer'],true,$deliveryCount);
   
    // если доставка не предусмотрена, то выводим все доступные активные метода оплаты
    if ($deliveryCount === 0) {
      $paymentTable = '';
      foreach ($this->getPayment() as $payment) {
        $paymentRate = '';
        
        $delivArray = json_decode($payment['deliveryMethod'], true);
        if ($_POST['customer'] == "yur" && $payment['id'] != "7") {
          continue;
        }

        if (!empty($payment['rate'])) {
          $paymentRate = (abs($payment['rate']) * 100) . '%';

          if ($payment['rate'] > 0) {
            $paymentRate = '(Наценка ' . $paymentRate . ')';
          } else {
            $paymentRate = '(Скидка ' . $paymentRate . ')';
          }
        }

        $paymentTable .= '
         <li class="noneactive">
           <label>
           <input type="radio" name="payment" rel value=' . $payment['id'] . '>' .
                $payment['name'] .
                '</label>
           <span class="icon-payment-' . $payment['id'] . '"></span>
             <span class="rate-payment">'.$paymentRate.'</span>
         </li>';
      }
    }

    if($step == 1){
      mgAddCustomPriceAction(array(__CLASS__, 'applyRate'));
    }
    
    $cart = new Models_Cart;
    $summOrder = $cart->getTotalSumm();       
    $summOrder = MG::numberFormat($summOrder);
    if ($step !=5 ) {
      $orderInfo = $model->getOrder('`'.PREFIX.'order`.id = '.DB::quote(intval($orderId)).'');
    }    
    $userInfo = USER::getUserInfoByEmail($orderInfo[$orderId]['user_email']);
    $settings = MG::get('settings');
    $orderNumber = $orderInfo[$orderId]['number'] != '' ? $orderInfo[$orderId]['number'] : $orderId;
    $linkToStatus = $orderInfo[$orderId]['hash'] ? $orderInfo[$orderId]['hash'] : '';
    
    $deliveryInfo = $model->getDeliveryMethod(false, $_POST['delivery']);
   
    if(!empty($deliveryInfo['cost'])&&($deliveryInfo['free']<=$summOrder)){
      $deliveryPrice = '+ доставка: <span class="order-delivery-summ">'.round($deliveryInfo['cost']).' '.MG::getSetting('currency').'</span>';   
    }    
    
    // Массив параметров для отображения в представлении.
    $this->data = array(
      'active' => !empty($userEmail) ? $userEmail : '', //состояние активации пользователя.
      'msg' => !empty($msg) ? $msg : '', //сообщение.
      'step' => !empty($step) ? $step : '', //стадия оформления заказа.
      'delivery' => !empty($deliveryArray) ? $deliveryArray : '', //массив способов доставки.
      'deliveryInfo' => $deliveryPrice,
      'paymentArray' => !empty($paymentTable) ? $paymentTable : '', //массив способов оплаты.
      'paramArray' => !empty($paramArray) ? $paramArray : '', //массив способов оплаты.
      'id' => !empty($orderId) ? $orderId : '', //id заказа.
      'orderNumber' => !empty($orderNumber) ? $orderNumber : $orderId, //id заказа.
      'summ' => !empty($summ) ? $summ : '', //сумма заказа.
      'pay' => !empty($pay) ? $pay : '', //
      'payMentView' => $this->getPaymentView($pay), //
      'currency' => $settings['currency'],
      'userInfo' => $userInfo,
      'orderInfo' => $orderInfo,
      'fileToOrder' => $fileToOrder,
      'meta_title' => 'Оформление заказа',
      'meta_keywords' => !empty($model->currentCategory['meta_keywords']) ? $model->currentCategory['meta_keywords'] : "заказы,заявки,оформить,оформление заказа",
      'meta_desc' => !empty($model->currentCategory['meta_desc']) ? $model->currentCategory['meta_desc'] : "Оформление заказа происходит в несколько этапов. 1 - ввод личных данных покупателя, 2 - оплата заказа.",
      'summOrder' => !empty($summOrder) ? $summOrder.' '.MG::getSetting('currency') : '', //сумма заказа без доставки
      'captcha' => (CAPTCHA_ORDER == '1' ? true : false),
      'linkToStatus' => $linkToStatus,
      );
  }    

  /**
   * Возвращает путь к странице с формой оплаты.
   * @param type $pay - id способа оплаты.
   * @return string - путь к странице с формой оплаты.
   */
  public function getPaymentView($pay) {
    switch ($pay) {
      case 1:
        $payMentView = 'mg-pages/payment/webmoney.php';
        break;
      case 2:
        $payMentView = 'mg-pages/payment/yandex.php';
        break;
      case 5:
        $payMentView = 'mg-pages/payment/robokassa.php';
        break;
      case 6:
        $payMentView = 'mg-pages/payment/qiwi.php';
        break;
      case 7:
        $payMentView = 'mg-pages/payment/quittance.php';
        break;
      case 8:
        $payMentView = 'mg-pages/payment/interkassa.php';
        break;
      case 9:
        $payMentView = 'mg-pages/payment/payanyway.php';
        break;
      case 10:
        $payMentView = 'mg-pages/payment/paymaster.php';
        break;
      case 11:
        $payMentView = 'mg-pages/payment/alfabank.php';
        break;
      case 14:
        $payMentView = 'mg-pages/payment/yandex-kassa.php';
        break;
      case 15:
        $payMentView = 'mg-pages/payment/privat24.php';
        break;
      case 16:
        $payMentView = 'mg-pages/payment/liqpay.php';
        break;
      case 17:
        $payMentView = 'mg-pages/payment/sberbank.php';
        break;
    }
    return $payMentView;
  }

  /**
   * Возвращает сообщение о статусе заказа "Подтвержден".
   * @param int $pay - id заказа.
   * @return mixed - сообщение и email пользователя.
   */
  public function confirmOrder($id) {
    // Модель для работы заказом.
    $model = new Models_Order;
    // Информация о заказе по переданному id.
    $orderInfo = $model->getOrder('`'.PREFIX.'order`.id = '.DB::quote(intval($id)));
    $hash = URL::getQueryParametr('sec');
    // Информация о пользователе, сделавший заказ .
    $orderUser = USER::getUserInfoByEmail($orderInfo[$id]['user_email']);
    // Если присланный хэш совпадает с хэшом из БД для соответствующего id.
    if ($orderInfo[$id]['confirmation'] == $hash) {
      if ($orderInfo[$id]['hash'] == '') {
          $msg = 'Посмотреть статус заказа Вы можете в <a href="'.SITE.'/personal">личном кабинете</a>.';
        } 
        else  {
          $msg = 'Следить за статусом заказа Вы можете по ссылке <br> '
            . '<a href="'.SITE.'/order?hash='.$orderInfo[$id]['hash'].'">'.SITE.'/order?hash='.$orderInfo[$id]['hash'].'</a>';
        }
      // Если статус заказа "Не подтвержден".
      if (0 == $orderInfo[$id]['status_id']) {
        // Подтверждаем заказ.
        $orderStatus = 1;
        // если оплата выбрана наложенным платежём или наличными(курьеру), то статус заказа изменяем на "в доставке"
        if(in_array($orderInfo[$id]['payment_id'], array(3, 4))){  
          $orderStatus = 3;
        }    
        
        $model->sendStatusToEmail($id, $orderStatus);
        $model->setOrderStatus($id, $orderStatus);
        
        $orderNumber = $orderInfo[$id]['number'];    
        $orderId = $id;
        $msg = 'Ваш заказ №'.$orderNumber.' подтвержден и передан на обработку. <br>
          '.$msg;
      } else {
        $msg = 'Заказ уже подтвержден и находится в работе. <br>
          '.$msg;
      }
      if (!$orderUser->activity) {
        $userEmail = $orderUser->email;
        $_SESSION['id'] = $orderUser->id;
      }
    } else {
      $msg = 'Некорректная ссылка.<br> Заказ не подтвержден<br>';
    }

    $result = array(
      'msg' => $msg,
      'userEmail' => $userEmail,
    );
    return $result;
  }

  /**
   * Возвращает массив доступных способов доставки.
   * @return mixed массив доступных способов доставки .
   */
  public function getDelivery() {
    $result = array();

    // Модель для работы заказом.
    $model = new Models_Order;
    $cart = new Models_Cart;
    $cartSumm = $cart->getTotalSumm();

    foreach ($model->getDeliveryMethod() as $id => $delivery) {
      if ($delivery['free'] != 0 && $delivery['free'] <= $cartSumm) {
        $delivery['cost'] = 0;
      }

      if (!$delivery['activity']) {
        continue;
      }

      if (isset($_POST['delivery']) && $_POST['delivery'] == $id) {
        $delivery['checked'] = 1;
      }

      // Заполнение массива способов доставки.
      $result[$delivery['id']] = $delivery;
    }

    // Если доступен только один способ доставки, то он будет выделен.
    if (1 === count($result)) {
      $deliveryId = array_keys($result);
      $result[$deliveryId[0]]['checked'] = 1;
    }

    return $result;
  }

  /**
   * Возвращает массив доступных способов оплаты.
   * @return mixed массив доступных способов оплаты.
   */
  public function getPayment() {
    $result = array();

    // Модель для работы заказом.
    $model = new Models_Order;

    $i = 1;
    // Колическтво активных методов оплаты.
    $countPaymentMethod = 0;
    $allPayment = $model->getPaymentBlocksMethod();
    foreach ($allPayment as $payment) {
      $i++;
      if ($_POST['payment'] && !empty($deliveryArray)) {
        $delivArray = json_decode($payment['deliveryMethod'], true);
        if (!$delivArray[$_POST['delivery']])
          continue;
      }

      if (!$payment['activity']) {
        continue;
      }

      if ($_POST['payment'] == $payment['id']) {
        $payment['checked'] = 1;
      }

      // Заполнение массива способов оплаты.
      $result[$payment['id']] = $payment;
      $countPaymentMethod++;
    }
    return $result;
  }

  /**
   * Возвращает массив доступных способов оплаты с учетом количества 
   * способов доставки.
   * @return mixed массив доступных способов оплаты.
   */
  public function getPaymentTable($deliveryArray) {
    $result = array();
    // Массив способов оплаты.
    $paymentArray = $this->getPayment();

    // Если доступен только один способ доставки.
    if (1 == count($deliveryArray)) {
      $deliveryId = array_keys($deliveryArray);
      foreach ($paymentArray as $payment) {
        $delivArray = json_decode($payment['deliveryMethod'], true);
        if (!$delivArray[$deliveryId[0]]) {
          continue;
        }
        $result[$payment['id']] = $payment;
      }
    } else {
      $result = $paymentArray;
    }

    // Если доступен только один способ оплаты, то он будет выделен.
    if (1 == count($result)) {
      $paymentId = array_keys($result);
      $result[$paymentId[0]]['checked'] = 1;
    }

    return $result;
  }

  /**
   * 
   */
  public function getDeliveryOrderOptions($orderId=null, $delivery_id=null){
    $orderId = intval($_POST['order_id']); 
    $orderOptions = array();
    $model = new Models_Order();
    $delivery = $model->getDeliveryMethod(false, $_POST['deliveryId']);
    $orderOptions = array(
      'deliverySum' => $delivery['cost'],
    );        
    //Если указан id заказа
    if($orderId > 0){      
      $orderInfo = $model->getOrder(' id = '.DB::quote($orderId));
      
      if(!empty($delivery['plugin'])){
        if($orderInfo[$orderId]['delivery_id'] == $_POST['deliveryId']){
          if(empty($_SESSION['deliveryAdmin'][$_POST['deliveryId']])){
            $orderOptions = unserialize(stripslashes($orderInfo[$orderId]['delivery_options']));  
            $_SESSION['deliveryAdmin'][$_POST['deliveryId']] = $orderOptions;  
          }   
          
          $orderOptions['deliverySum'] = 0;
        }else{
          $orderOptions = $_SESSION['deliveryAdmin'][$_POST['deliveryId']];
          $orderOptions['deliverySum'] = 0;
        }        
      }else{
        if($orderInfo[$orderId]['delivery_id'] == $_POST['deliveryId']){
          $orderOptions = array(
            'deliverySum' => $orderInfo[$orderId]['delivery_cost'],
          );
        }
      }          
    }else{          
      if(!empty($delivery['plugin'])){
        $orderOptions = $_SESSION['deliveryAdmin'][$_POST['deliveryId']];
        $orderOptions['deliverySum'] = 0;
      }
    } 
    
    echo json_encode($orderOptions);
    exit();
  }
  
  /**
   * Используется при AJAX запросе, 
   * возвращает html список способов оплаты в зависимости от 
   * выбранного способа доставки.
   */
  public function getPaymentByDeliveryId($deliveryId=null,$customer=null,$nojson=false, $countDeliv=null) {
    
    if(!$deliveryId){
      $deliveryId = $_POST['deliveryId'];
    }
    if(!$customer){
      $customer = $_POST['customer'];
    }    
    if($countDeliv===1){
      $seletFirst = true;
    }    
   
    $countPaymentMethod = 0; //колическтво активных методов оплаты
   

    $paymentTable = '';
    foreach ($this->getPayment() as $payment) {
      $delivArray = json_decode($payment['deliveryMethod'], true);      
      $paymentRate = '';
     
      if($customer=="yur" && $payment['id']!="7"){
        continue;
      }
      
      if (!$delivArray[$deliveryId] || !$payment['activity']) {
        continue;
      }    
      
      if(!empty($payment['rate'])){                
        $paymentRate = (abs($payment['rate'])*100).'%';
        
        if($payment['rate'] > 0){
          $paymentRate = '(Наценка '.$paymentRate.')';
        }else{
          $paymentRate = '(Скидка '.$paymentRate.')';
        }
      }
      
      $paymentTable .= '
        <li class="noneactive">
          <label>
          <input type="radio" name="payment" rel value='.$payment['id'].'>'.
        $payment['name'].
        '</label>
          <span class="icon-payment-'.$payment['id'].'"></span>
          <span class="rate-payment">'.$paymentRate.'</span>
        </li>';
      $countPaymentMethod++;
      
      if ($payment['id']===$_POST['payment']) {      
        $paymentTable = str_replace('class="noneactive"', 'class="active"', $paymentTable);
        $paymentTable = str_replace('rel', 'checked', $paymentTable);
      }
    }

    if (1 == $countPaymentMethod ) {
      $paymentTable = str_replace('class="noneactive"', 'class="active"', $paymentTable);
      $paymentTable = str_replace('rel', 'checked', $paymentTable);
    }

    if($nojson){
      return $paymentTable;
    }
    
    $summDelivery = 0;                             
    $deliveryArray = $this->getDelivery();
    foreach($deliveryArray as $delivery) {
      if ($delivery['id'] == $deliveryId && $delivery['cost'] != 0 ) {
        $summDelivery = MG::numberFormat($delivery['cost']).' '.MG::getSetting('currency');
      }
    }    
    
    $result = array(
      'status' => true,
      'paymentTable' => $paymentTable,
      'summDelivery' => $summDelivery,
    );
    
    $args = func_get_args();
    
    if(empty($args)){
      $args = array($deliveryId);
    }
    
    $result = MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
    echo json_encode($result);
    MG::disableTemplate();    
    exit;
  }
  
  public function setPaymentRate(){
    if(!empty($_POST['paymentId'])){
      $order = new Models_Order();
      $payment = $order->getPaymentMethod($_POST['paymentId']);
      
      if(!empty($payment['rate'])){
        $_SESSION['price_rate'] = $payment['rate'];
        mgAddCustomPriceAction(array(__CLASS__, 'applyRate'));        
      }else{
        $_SESSION['price_rate'] = 0;
      }
      
      $cart = new Models_Cart;
      $summOrder = $cart->getTotalSumm();       
      $res = array('summ' => MG::numberFormat($summOrder).' '.htmlspecialchars_decode(MG::getSetting('currency'))); 
      echo json_encode($res);
      exit;
    }        
  }

  function applyRate($args){
    $price = $args['priceWithCoupon'] < $args['priceWithDiscount'] ? $args['priceWithCoupon'] : $args['priceWithDiscount'];
    
    if(!empty($_SESSION['price_rate'])){
      $price += $price * $_SESSION['price_rate'];  
    }      
    $t=(string)($price*100);   
    return ceil($t)/100;           
  }
  
  /**
   * Используется при AJAX запросе.
   */
  public function getEssentialElements() {
    $paymentId = $_POST['paymentId'];
    $paramArray = $model->getParamArray($paymentId, $orderId, $summ);
    $result = array(
      'name' => $paramArray[0]['name'],
      'value' => $paramArray[0]['value']
    );
    echo json_encode($result);
    MG::disableTemplate();
    exit;
  }

  /**
   * Подключает набор иконок для способов оплаты.
   */
  public function includeIconsPack() {
    /* Иконки оплаты для сайта */
    mgAddMeta('<link type="text/css" href="'.SCRIPT.'standard/css/layout.order.css" rel="stylesheet"/>');
  }

}
