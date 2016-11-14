<?php

/*
  Plugin Name: Купить одним кликом
  Description: Плагин предосталяет пользователю возможности быстрой покупки. После подключения плагина необходимо вставить в Ваш шаблон, где формируется карточка товара, шорт-код [buy-click id="&lt;?php echo $data['id']?&gt;" count="&lt;?php echo $data['count']?&gt;"] в файле view/product.php, также возможно добавить кнопку в мини-карточке товара в каталоге, для этого необходимо добавить [buy-click id="&lt;?php echo $item['id']?&gt;" count="&lt;?php echo $item['count']?&gt;" variant="&lt;?php echo $item['variants']?&gt;"] в файле view/catalog.php. Плагин имеет страницу настроек для выбора необходимых данных от покупателя при быстрой покупке.
  Author: Чуркина Дарья
  Version: 1.2.8
 */

new BuyClick;

class BuyClick {

  private static $lang = array(); // массив с переводом плагина 
  private static $pluginName = ''; // название плагина (соответствует названию папки)
  private static $path = ''; //путь до файлов плагина 
  private static $duplicate = array(); //масив с данными из карзины

  public function __construct() {

    mgActivateThisPlugin(__FILE__, array(__CLASS__, 'activate')); //Инициализация  метода выполняющегося при активации  
    mgAddAction(__FILE__, array(__CLASS__, 'pageSettingsPlugin')); //Инициализация  метода выполняющегося при нажатии на кнопку настроект плагина  
    mgAddAction('models_cart_addtocart', array(__CLASS__, 'operationsWithCart'), 1);
    mgAddShortcode('buy-click', array(__CLASS__, 'buyOneClick')); /* Инициализация шорткода [buy-click id="<?php echo $data['id']?>"] - доступен в любом HTML коде движка.   */

    self::$pluginName = PM::getFolderPlugin(__FILE__);
    self::$lang = PM::plugLocales(self::$pluginName);
    self::$path = PLUGIN_DIR.self::$pluginName;

    if (!URL::isSection('mg-admin')) { // подключаем CSS плагина для всех страниц, кроме админки
      mgAddMeta('<link rel="stylesheet" href="'.SITE.'/'.self::$path.'/css/style.css" type="text/css" />');
    }
    
    mgAddMeta('<script type="text/javascript" src="'.SITE.'/'.self::$path.'/js/buyclick.js"></script>');
    mgAddMeta('<script type="text/javascript" src="'.SITE.'/mg-core/script/jquery.maskedinput.min.js"></script>');
  }

  /**
   * Метод выполняющийся при активации палагина 
   */
  static function activate() {
    $option = MG::getSetting('buyClickOption');
    if (empty($option)) {
      $array = Array(
        'name' => 'true',
        'phone' => 'true',
        'email' => 'true',
        'address' => 'true',
        'payment' => '4',
        'delivery' => '3',
        'capcha' => 'true',
        'product' => 'true',
        'header' => 'Купить в один клик',
        'button' => 'Купить одним кликом',
        'comment' => 'true',
      );

      MG::setOption(array('option' => 'buyClickOption', 'value' => addslashes(serialize($array))));
    }
  }

  /**
   * Метод выполняющийся перед генераццией страницы настроек плагина
   */
  static function preparePageSettings() {
    echo '   
      <link rel="stylesheet" href="'.SITE.'/'.self::$path.'/css/style.css" type="text/css" />     
      <script type="text/javascript">
        includeJS("'.SITE.'/'.self::$path.'/js/script.js");  
      </script> 
    ';

    $option = MG::getSetting('buyClickOption');
    $option = stripslashes($option);
    $options = unserialize($option);
  }

  /**
   * Выводит страницу настроек плагина в админке
   */
  static function pageSettingsPlugin() {
    $lang = self::$lang;
    $pluginName = self::$pluginName;
    $entity = self::getDelivery();
    $payment = self::getPayment();
    self::preparePageSettings();

    //получаем опцию buyClickOption в переменную option
    $option = MG::getSetting('buyClickOption');
    $option = stripslashes($option);
    $options = unserialize($option);

    include('pageplugin.php');
  }

  /**
   * Получает из БД информацию о доставках
   */
  static function getDelivery() {
    USER::AccessOnly('1,4', 'exit()');
    $entity = array();
    $res = DB::query("
      SELECT * 
      FROM `".PREFIX."delivery` 
    ");
    while ($row = DB::fetchAssoc($res)) {
      $entity[] = $row;
    }

    return $entity;
  }

  /**
   * Получает из БД информацию о методах оплаты
   */
  static function getPayment() {
    USER::AccessOnly('1,4', 'exit()');
    $payment = array();
    $resultPayment = DB::query("
      SELECT * 
      FROM `".PREFIX."payment` 
    ");
    while ($row = DB::fetchAssoc($resultPayment)) {
      $payment[] = $row;
    }

    return $payment;
  }

  /**
   * Обработчик шотркода вида  [buy-click id="<?php echo $data['id']?>" count="<?php echo $data['count']?>" variant = id="<?php echo $data['variants']?>"]
   * выполняется когда при генерации страницы встречается  
   */
  static function buyOneClick($product) {
    if (empty($product['id'])) {
      return false;
    }
    $option = MG::getSetting('buyClickOption');
    $option = stripslashes($option);
    $options = unserialize($option);
    if (!isset($product['count'])) {
      $res = DB::query('SELECT `count` FROM `'.PREFIX.'product` WHERE `id`='.DB::quote($product['id']));
      $count = DB::fetchArray($res);    
      if ($count['count'] == '0') {
        $res = DB::query('SELECT `count` FROM `'.PREFIX.'product_variant` WHERE `product_id`='.DB::quote($product['id']).' AND `count` <> 0');
        if (DB::numRows($res)== 0) {
          return false;
        }      
      }
    } elseif($product['count']=='0'&&!isset($product['variant'])) {
      $res = DB::query('SELECT `count` FROM `'.PREFIX.'product_variant` WHERE `product_id`='.DB::quote($product['id']).' AND `count` <> 0');
        if (DB::numRows($res)== 0) {
          return false;
        }      
    } elseif($product['count']=='0'&&!$product['variant']) {
      return false;
    }
    
    $result = '<div class="wrapper-mg-buy-click">
                <a class="mg-buy-click-button mg-plugin-btn"  data-product-id = '.$product['id'].'>'
                .(($options['button'] != '') ? $options['button'] : 'Купить одним кликом').'
                </a>
              </div>';
    return $result;
  }

  /**
   * 
   * Обработчик хука функции addToCart($id, $count = 1, $property = array('property' => '', 'propertyReal' => ''), $variantId = null)
   * 
   */
  static function operationsWithCart($arg) {
    if ($_POST['ajax'] == 'buyclickflag') {
      $model = new Models_Cart;
      self::$duplicate = array(
        'propertySetArray' => $_SESSION['propertySetArray'],
        'cart' => $_SESSION['cart']);
      $ourProperty = (html_entity_decode($arg['args'][2]['property']));
      // очищаем корзину - все кроме нужного товара
      foreach ($_SESSION['cart'] as $key => $item) {
        $propertyCompare = htmlspecialchars_decode(str_replace(array('&amp;', '&#37;'), array('&', '%'), $item['property'])) == $ourProperty ? false: true;

        if ($item['id'] != $arg['args'][0] || ($item['variantId'] != $arg['args'][3]) || $propertyCompare) {
          $model->delFromCart($item['id'], $item['property'], $item['variantId']);
        }
        if ($item['id'] == $arg['args'][0] && $item['variantId'] == $arg['args'][3] && !$propertyCompare) {
          $_SESSION['cart'][$key]['count'] = $arg['args'][1];
        }
      }

      self::orderOneClick();      // оформляем заказ

      $_SESSION['propertySetArray'] = self::$duplicate['propertySetArray'];
      $_SESSION['cart'] = self::$duplicate['cart'];

      // восстанавливаем содержимое корзины
      foreach ($_SESSION['cart'] as $item) {
        $propertyCompare = htmlspecialchars_decode(str_replace(array('&amp;', '&#37;'), array('&', '%'), $item['property'])) == $ourProperty ? true : false;
        if ($item['id'] == $arg['args'][0] && $item['variantId'] == $arg['args'][3] && $propertyCompare) {
          $model->delFromCart($item['id'], $item['property'], $item['variantId']);
        }
      }
    }
    return $arg['result'];
  }

  /*
   * Функция оформления заказа - добавление в БД и отправка писем администратору и покупателю.
   */

  static function orderOneClick() {

    // Модель для работы заказом.
    $option = MG::getSetting('buyClickOption');
    $option = stripslashes($option);
    $options = unserialize($option);
    $model = new Models_Order;
    $info = Array(
      'fio' => $_SESSION['infoClient']['name'],
      'email' => $options['email'] ? $_SESSION['infoClient']['email'] : "",
      'phone' => $options['phone'] ? $_SESSION['infoClient']['phone'] : "",
      'address' => $_SESSION['infoClient']['address'],
      'info' =>  $_SESSION['infoClient']['comment'] != '' ?  $_SESSION['infoClient']['comment'] : "Быстрая покупка",
      'delivery' => $options['delivery'],
      'payment' => $options['payment'],
      'customer' => "fiz",
      'capcha' => $_SESSION['capcha']
    );
    unset($_SESSION['infoClient']);
    $answerOrder = false;
    $request = array();
    $newuser = false;
    $valid = $model->isValidData($info, $request, $newuser);
    if (!$valid) {
      $model->addOrder();
      $answerOrder = true;
    }
    return $answerOrder;
  }

}
