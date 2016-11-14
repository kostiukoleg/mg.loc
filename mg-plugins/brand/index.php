<?php

/*
  Plugin Name: Логотипы брендов/производителей
  Description: При активации плагина создается новая характеристика Бренд, куда можно экспортировать уже существующие значения характеристики бренд или производитель. Добавьте шорт-код [brand] для вывода логотипов. При нажатии на логотип загружаются товары данного бренда. Можно копировать значения из других характеристик, они будут добавлены в характеристику "Бренд" и значения будут присвоены в карточке соответсвующих товаров.
  Author: Daria Churkina
  Version: 1.0.5
 */

new brand;

class brand {

  private static $options;
  private static $lang = array(); // массив с переводом плагина 
  private static $pluginName = ''; // название плагина (соответствует названию папки)
  private static $path = ''; //путь до файлов плагина 

  public function __construct() {

    mgActivateThisPlugin(__FILE__, array(__CLASS__, 'activate')); //Инициализация  метода выполняющегося при активации  
    mgAddAction(__FILE__, array(__CLASS__, 'pageSettingsPlugin')); //Инициализация  метода выполняющегося при нажатии на кнопку настроект плагина  
    mgAddShortcode('brand', array(__CLASS__, 'handleShortCode')); // Инициализация шорткода [brand] - доступен в любом HTML коде движка.    

    self::$pluginName = PM::getFolderPlugin(__FILE__);
    self::$lang = PM::plugLocales(self::$pluginName);
    self::$path = PLUGIN_DIR.self::$pluginName;
    $option = MG::getSetting('brand');
    $option = stripslashes($option);
    self::$options = unserialize($option);
    if (!URL::isSection('mg-admin')) { // подключаем CSS плагина для всех страниц, кроме админки
      mgAddMeta('<link rel="stylesheet" href="'.SITE.'/'.self::$path.'/css/style.css" type="text/css" />');
      //  mgAddMeta('<script type="text/javascript" src="'.SITE.'/'.self::$path.'/js/brand.js"></script>');
    } else {
      mgAddMeta('<script type="text/javascript" src="'.SITE.'/'.self::$path.'/js/script.js"></script>');
    }    
    $newfile = 'brand.php';
    if (!file_exists(PAGE_DIR.$newfile)) {
      $file = PLUGIN_DIR.self::$pluginName.'/brandviews.php';
      copy($file, PAGE_DIR.$newfile);
    }
    
  }

  /**
   * Метод выполняющийся при активации палагина 
   */
  static function activate() {
    self::createDateBase();
  }

  /**
   * Создает таблицу плагина в БД
   */
  static function createDateBase() {
    // Запрос для проверки, был ли плагин установлен ранее.
    $exist = false;
    $brandExist = false;
    $result = DB::query('SHOW TABLES LIKE "'.PREFIX.self::$pluginName.'-logo"');
    if (DB::numRows($result)) {
      $exist = true;
    }

    DB::query("
     CREATE TABLE IF NOT EXISTS `".PREFIX.self::$pluginName."-logo` (     
      `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Порядковый номер записи',     
      `brand` text NOT NULL COMMENT 'Бренд',
      `url` text NOT NULL COMMENT 'Логотип',    
      `desc` text NOT NULL COMMENT 'Описание',    
      `sort` int(11) NOT NULL COMMENT 'Порядок',
       PRIMARY KEY (`id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;");
    if (self::$options['propertyId']) {
      $prop = DB::query('SELECT `data` FROM `'.PREFIX.'property` WHERE `id`='.DB::quote(self::$options['propertyId']));
      $res = DB::fetchArray($prop);
      if (!$res) {
        $exist = false; 
        $brandExist = true;
      }
    }
    if (!$exist) {
      $value =  '';
      if ($brandExist) {           
          $res = DB::query("SELECT `id`, `brand` FROM `".PREFIX.self::$pluginName."-logo` ");
          $brand = array();
          while ($row = DB::fetchArray($res)) {
            if ($row['brand']) {
              $value .= $value ? '|'.$row['brand'] : $row['brand'];
            }           
          } 
      }      
      DB::query(
        "INSERT INTO `".PREFIX."property` 
          (`id`, `name`, `type`, `default`, `data`, `all_category`, `activity`, `filter`, `type_filter`) 
          VALUES (NULL, 'Бренд', 'assortmentCheckBox', '', ".DB::quote($value).", '1', '1', '1', 'checkbox')"
      );      
      $propId = DB::insertId();
      $dbRes = DB::query('SHOW COLUMNS FROM `'.PREFIX.'property` WHERE FIELD = "plugin"');
      if($row = DB::fetchArray($dbRes)){
        DB::query(
        "UPDATE `".PREFIX."property` SET 
          `plugin` = ".DB::quote(self::$pluginName)." WHERE `id`=".DB::quote($propId));
        }
      DB::query(
        "UPDATE `".PREFIX."property` SET 
          `sort` = ".DB::quote($propId)." WHERE `id`=".DB::quote($propId));
      $category = DB::query(
          "SELECT `id` FROM `".PREFIX."category` "
      );
      while ($cat_id = DB::fetchArray($category)) {
        DB::query("
            INSERT IGNORE INTO `".PREFIX."category_user_property`
            VALUES (".DB::quote($cat_id['id']).", ".DB::quote($propId).")");
      }
      $array = Array(
        'propertyId' => $propId,
        'first' => 'true',
      );
      MG::setOption(array('option' => 'countPrintRowsBrand', 'value' => 10));
      MG::setOption(array('option' => 'brand', 'value' => addslashes(serialize($array))));
      
    }
  }

  /**
   * Выводит страницу настроек плагина в админке
   */
  static function pageSettingsPlugin() {
    $id_prop = self::$options['propertyId'];
    self::compareProp($id_prop);    
    $lang = self::$lang;
    $pluginName = self::$pluginName;
    $countPrintRows = MG::getSetting('countPrintRowsBrand');
    $options = self::$options;
    $result = array();
    $sql = "SELECT * FROM `".PREFIX.self::$pluginName."-logo` ORDER BY `sort`";
    $page = 1;
    if ($_POST["page"]) {
      $page = $_POST["page"]; //если был произведен запрос другой страницы, то присваиваем переменной новый индекс
    }
    $navigator = new Navigator($sql, $page, $countPrintRows); //определяем класс
    $brand = $navigator->getRowsSql();
    $pagination = $navigator->getPager('forAjax');
    $res = DB::query("SELECT * FROM `".PREFIX.self::$pluginName."-logo` WHERE `url`=''");
    $empty = DB::numRows($res);    
    self::preparePageSettings();
    include('pageplugin.php');
  }

  /**
   * Метод выполняющийся перед генерацией страницы настроек плагина
   */
  static function preparePageSettings() {
    echo '   
      <link rel="stylesheet" href="'.SITE.'/'.self::$path.'/css/style.css" type="text/css" />     
      <script type="text/javascript">
        includeJS("'.SITE.'/'.self::$path.'/js/script.js");  
      </script> 
    ';
  }

  /**
   * выводит логотипы брендов по шорткоду [brand]
   */
  static function handleShortCode() {       
      $options = self::$options;
      $brand = array();
      $res = DB::query('SELECT `url`, `brand` FROM `'.PREFIX.self::$pluginName.'-logo` order by `sort`');
      if ($res) {
        while ($row = DB::fetchArray($res)) {
          $brand[] = $row;
        }      
      ob_start();
      include ('layout.php');
      $html = ob_get_contents();
      ob_clean();
      return $html;
      }
  }

  /**
   * Возвращает массив продуктов  по запрошенному бренду и информацию о бренде
   * @param $brand - название тега
   */
  static function getProductsByBrand($brand) {
    if (empty(self::$options['propertyId'])) {
      $option = MG::getSetting('brand');
      $option = stripslashes($option);
      self::$options = unserialize($option);
    }
    // Показать первую страницу выбранного раздела.
    $page = 1;
    // Запрашиваемая страница.
    if (isset($_REQUEST['page'])) {
      $page = $_REQUEST['page'];
    }

    $catalog = new Models_Catalog;
    $currencyRate = MG::getSetting('currencyRate');      
    $currencyShopIso = MG::getSetting('currencyShopIso'); 
    
    if (!empty($brand)) {
      // Формируем список товаров для блока продукции.
       // Вычисляет общее количество продуктов.
       // Запрос вернет общее кол-во продуктов в выбранной категории.
    $sql = '
      SELECT distinct p.id,
        CONCAT(c.parent_url,c.url) as category_url,
        p.url as product_url,
        p.*,pv.product_id as variant_exist,
        rate,(p.price_course + p.price_course * (IFNULL(rate,0))) as `price_course`,
        p.currency_iso
      FROM `'.PREFIX.'product` p
      LEFT JOIN `'.PREFIX.'category` c
        ON c.id = p.cat_id
      LEFT JOIN `'.PREFIX.'product_variant` pv
        ON p.id = pv.product_id
      LEFT JOIN  `'.PREFIX.'product_user_property` up ON up.`product_id` = p.id
      WHERE up.`property_id` = '.DB::quote(self::$options['propertyId']).'AND up.`value`='.DB::quote($brand).' AND p.activity = 1';

    $navigator = new Navigator($sql, $page, MG::getSetting('countСatalogProduct')); //определяем класс.
    $products = $navigator->getRowsSql();
    $pager = $navigator->getPager();
    // добавим к полученым товарам их свойства    
    $products = $catalog->addPropertyToProduct($products);   
    $product = new Models_Product;    
    if(!empty($products)){
      foreach ($products as $item) {
        $productIds[] = $item['id'];
      }
      $blocksVariants = $product->getBlocksVariantsToCatalog($productIds);  
      foreach ($products as $k => $item) {
        $imagesUrl = explode("|", $item['image_url']);
        $products[$k]["image_url"] = "";
        if (!empty($imagesUrl[0])) {
          $products[$k]["image_url"] = $imagesUrl[0];
        }
        
        $item['currency_iso'] = $item['currency_iso']?$item['currency_iso']:$currencyShopIso;
        //$item['price'] *= $currencyRate[$item['currency_iso']];   
        
        $item['old_price'] = $item['old_price']* $currencyRate[$item['currency_iso']];
        $item['old_price'] = $item['old_price']? MG::priceCourse($item['old_price']):0;
        $item['price'] =  MG::priceCourse($item['price_course']); 
          
        $products[$k]['title'] = MG::modalEditor('catalog', $item['title'], 'edit', $item["id"]);
        // Формируем варианты товара.
        // if ($item['variant_exist']) {

          // Легкая форма без характеристик.
          $liteFormData = $product->createPropertyForm($param = array(
            'id' => $item['id'],
            'maxCount' => $item['count'],
            'productUserFields' => null,
            'action' => "/catalog",
            'method' => "POST",
            'ajax' => true,
            'blockedProp' => $blockedProp,
            'noneAmount' => true,
            'titleBtn' => MG::getSetting('buttonBuyName'),
            'buyButton' => ($products[$k]['count']==0 ||MG::getSetting('actionInCatalog')=='false')?$products[$k]['actionView']:'',
            'blockVariants' => $blocksVariants[$item['id']]
          ));
          $products[$k]['liteFormData'] = $liteFormData['html'];
         // }
         // опледеляем для каждого продукта  тип выводимой формы: упрощенная, с кнопками или без.        
          if (!$products[$k]['liteFormData']){
            if($products[$k]['count']==0||MG::getSetting('actionInCatalog')=='false'){
              $buyButton = $products[$k]['actionView'];          
            }else{
              $buyButton = $products[$k]['actionButton']; 
            }
          } else{
            $buyButton = $products[$k]['liteFormData'];
          }
           $products[$k]['buyButton'] = $buyButton;

          }
      }
    
    $brandInfo = array();
    $res = DB::query('SELECT * FROM `'.PREFIX.'brand-logo` WHERE `brand`='.DB::quote($brand));

    if ($row = DB::fetchArray($res)) {
      $brandInfo = $row;
    }
    $result = array(
      'items' => $products,
      'brand' => $brandInfo,
      'pager' => $pager);
    }
    return $result;
  }

  static function compareProp($id) {
    $prop = DB::query('SELECT `data` FROM `'.PREFIX.'property` WHERE `id`='.DB::quote($id));
    if ($res = DB::fetchArray($prop)) {
      $value = $res['data'] ? $res['data'] : '';
      $data = explode('|', $res['data']);
      $res = DB::query("SELECT `id`, `brand` FROM `".PREFIX.self::$pluginName."-logo` ");
      $brand = array();
      while ($row = DB::fetchArray($res)) {
        $brand[$row['id']] = $row['brand'];
      }
      $diff = array_diff($data, $brand);
      if (!empty($diff)) {
        foreach ($diff as $newBrand) {
          if ($newBrand != '') {
            $res = DB::query("INSERT INTO `".PREFIX.self::$pluginName."-logo` (`brand`) VALUES (".DB::quote($newBrand).")");
            $brandId = DB::insertId();
            DB::query("UPDATE `".PREFIX.self::$pluginName."-logo` SET `sort`=".DB::quote($brandId)." WHERE `id`= ".DB::quote($brandId));
          }
        }
      }
    } 
  }

}
