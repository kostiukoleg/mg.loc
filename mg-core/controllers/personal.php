<?php

/**
 * Контроллер: Personal
 *
 * Класс Controllers_Personal обрабатывает действия пользователей на странице личного кабинета.
 * - подготавливает данных пользователя для их отображения;
 * - обрабатывает запрос на изменения пароля;
 * - обрабатывает запрос на изменения способа оплаты;
 * - обрабатывает запрос на изменение данных пользователя.
 * 
 * @package moguta.cms
 * @subpackage Controller
 */
class Controllers_Personal extends BaseController {

  function __construct() {
    $lang = MG::get('lang');
    $settings = MG::get('settings');
    $this->lang = $lang;
    $status = 0;
    if (User::isAuth()) {
      $order = new Models_Order;
      $status = 3;

      //обработка запроса на изменение данных пользователя
      if (URL::getQueryParametr('userData')) {
        $customer = URL::getQueryParametr('customer');
        $birthday = URL::getQueryParametr('birthday');
        if ($birthday) {
          $birthday = date('Y-m-d', strtotime(URL::getQueryParametr('birthday')));  
        }
        $userData = array(
          'name' => URL::getQueryParametr('name'),
          'sname' => URL::getQueryParametr('sname'),
          'birthday' => $birthday,
          'address' => URL::getQueryParametr('address'),
          'phone' => URL::getQueryParametr('phone'),
          'nameyur' => $customer == 'yur' ? URL::getQueryParametr('nameyur') : '',
          'adress' => $customer == 'yur' ? URL::getQueryParametr('adress') : '',
          'inn' => $customer == 'yur' ? URL::getQueryParametr('inn') : '',
          'kpp' => $customer == 'yur' ? URL::getQueryParametr('kpp') : '',
          'bank' => $customer == 'yur' ? URL::getQueryParametr('bank') : '',
          'bik' => $customer == 'yur' ? URL::getQueryParametr('bik') : '',
          'ks' => $customer == 'yur' ? URL::getQueryParametr('ks') : '',
          'rs' => $customer == 'yur' ? URL::getQueryParametr('rs') : '',
        );
        
       
        if (USER::update(User::getThis()->id, $userData)) {
          $message = 'Данные успешно сохранены';
        } else {
          $error = 'Не удалось сохранить данные '.$this->_newUserData['sname'];
        }
      }

      // Обработка запроса на изменения пароля.
      if (URL::getQueryParametr('chengePass')) {
        if (USER::auth(User::getThis()->email, URL::getQueryParametr('pass'))) {
          $person = new Models_Personal;
          $message = $person->changePass(URL::getQueryParametr('newPass'), User::getThis()->id);
        } else {
          $error = 'Неверный пароль';
        }
      }

      // Обработка запроса на изменения способа оплаты.
      if (URL::getQueryParametr('changePaymentId')) {
        $paymentId = intval($_POST['changePaymentId']);  
        $orderId = intval($_POST['orderId']);
        
        $payment = $order->getPaymentMethod($paymentId);
        $orderData = $order->getOrder(' id = '.DB::quote($orderId));
        $orderData = $orderData[$orderId];        
        
        $orderContent = unserialize(stripslashes($orderData['order_content']));
        $summ = 0;
        
        $cart = new Models_Cart();
        
        foreach($orderContent as $cell=>$item){
          $priceWithCoupon = $cart->applyCoupon($item['coupon'], $item['fulPrice'], $item);
          $priceWithDiscount = $cart-> applyDiscountSystem($item['fulPrice'], $item);           
          $fullPrice = $cart->customPrice(array(
            'product' => $product,
            'priceWithCoupon' => $priceWithCoupon, 
            'priceWithDiscount' => $priceWithDiscount['price'],
          ));
          
          if(!empty($payment['rate'])){
            $item['price'] = $fullPrice - $fullPrice * $payment['rate'] * (-1);
          }else{
            $item['price'] = $fullPrice;
          }  
          
          $summ += $item['price']*$item['count'];
          $orderContent[$cell] = $item;
        }                
        $orderContent = addslashes(serialize($orderContent));
        
        $status = $order->updateOrder(array('payment_id' => $paymentId, 'summ' => $summ, 'order_content' => $orderContent, 'id' => $orderId));
        $result = array(
          'status' => $status,
          'comment' => 2,
          'orderStatus' => 3,
          'summ' => $summ,
        );

        echo json_encode($result);
        MG::disableTemplate();
        exit;
      }

      // Обработка AJAX запроса на закрытие заказа.
      if (URL::getQueryParametr('delOK')) {
        $comment = 'Отменено покупателем '.date('d.m.Y H:i').', по причине <br>"'.URL::getQueryParametr('comment').'"' ;
        // Пересчитываем остатки продуктов из заказа.
        $order->refreshCountProducts(URL::getQueryParametr('delID'), 4);

        $res = DB::query('
          UPDATE `'.PREFIX.'order`
          SET close_date = now(), status_id = 4, comment = '.DB::quote($comment).'
          WHERE id = '.DB::quote(URL::getQueryParametr('delID')).' AND user_email ='.DB::quote(User::getThis()->email));

        if ($res) {
          $status = true;
        }

        if ($comment) {
          $comm = "<b>Комментарий: ".$comment."</b>";
        }

        $result = array(
          'status' => $status,
          'comment' => $comm,
          'orderStatus' => $lang[$order->getOrderStatus(array('status_id' => 4))]
        );

        $order->sendMailOfUpdateOrder(URL::getQueryParametr('delID'));

        echo json_encode($result);
        MG::disableTemplate();
        exit;
      }
      
      // Отображение данных пользователя.
      //$orderArray = $order->getOrder('user_email = "'.User::getThis()->email.'"');
      $page=!empty($_REQUEST["page"])?$_REQUEST["page"]:0;
      $sql = ""
          . "SELECT * FROM `".PREFIX."order` "
          . "WHERE user_email=".DB::quote(User::getThis()->email)." "
          . "ORDER BY `add_date` DESC";
      $nav = new Navigator($sql, $page, 10);
      $orderArray = $nav->getRowsSql();
      $pagination = $nav->getPager();

      if (is_array($orderArray)) {
        foreach ($orderArray as $orderId => $orderItems) {
          $orderArray[$orderId]['string_status_id'] = $order->getOrderStatus($orderItems);
          $paymentArray = $order->getPaymentMethod($orderItems['payment_id']);
          $orderArray[$orderId]['name'] = $paymentArray['name'].mgGetPaymentRateTitle($paymentArray['rate']);
          $orderArray[$orderId]['paided'] = $order->getPaidedStatus($orderItems);
        }
      }

      if (!User::getThis()->activity) {
        $status = 2;
        unset($_SESSION['user']);
      }

      if (User::getThis()->blocked) {
        $status = 1;
        unset($_SESSION['user']);
      }
      $paymentListTemp = $order->getPaymentBlocksMethod();
      $paymentList[] = array();
      foreach ($paymentListTemp as $item) {
        if ($item['activity'] != '0') {
          $item['name'] .= mgGetPaymentRateTitle($item['rate']);          
          $paymentList[$item['id']] = $item;
        }
      }
    }

    $this->data = array(
      'error' => !empty($error) ? $error : '', // Сообщение об ошибке.
      'message' => !empty($message) ? $message : '', // Информационное сообщение.
      'status' => !empty($status) ? $status : '', // Статус пользователя.
      'userInfo' => User::getThis(), // Информация о пользователе.
      'orderInfo' => !empty($orderArray) ? $orderArray : '', // Информация о заказе.
      'pagination' => $pagination,
      'currency' => $settings['currency'],
      'paymentList' => $paymentList,
      'meta_title' => 'Личный кабинет',
      'meta_keywords' => !empty($model->currentCategory['meta_keywords']) ? $model->currentCategory['meta_keywords'] : "заказы,личные данные, личный кабинет",
      'meta_desc' => !empty($model->currentCategory['meta_desc']) ? $model->currentCategory['meta_desc'] : "В личном кабинете нашего сайта вы сможете отслеживать состояние заказов и менять свои данные",
      'assocStatusClass'=> array('dont-confirmed', 'get-paid', 'paid', 'in-delivery', 'dont-paid', 'performed', 'processed') // цветная подсветка статусов
    );
  }

}
