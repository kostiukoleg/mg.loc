<?php

/**
 * Модель: Product
 *
 * Класс Models_Product реализует логику взаимодействия с товарами магазина.
 * - Добавляет товар в базу данных;
 * - Изменяет данные о товаре;
 * - Удаляет товар из базы данных;
 * - Получает информацию о запрашиваемом товаре;
 * - Получает продукт по его URL;
 * - Получает цену запрашиваемого товара по его id.
 *
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Model
 * 
 */
class Models_Product {

  /**
   * Добавляет товар в базу данных. 
   * @param array $array массив с данными о товаре.
   * @return bool|int в случае успеха возвращает id добавленного товара.
   */
  public function addProduct($array, $clone = false) {

    if (empty($array['title'])) {
      return false;
    }
	
    $userProperty = $array['userProperty'];
    $variants = !empty($array['variants']) ? $array['variants'] : array(); // варианты товара
    unset($array['userProperty']);
    unset($array['variants']);
    unset($array['count_sort']);
    if(empty($array['id'])){
      unset($array['id']);
    }

    $result = array();

    $array['url'] = empty($array['url']) ? MG::translitIt($array['title']) : $array['url'];
  

    $maskField = array('title','meta_title','meta_keywords','meta_desc','image_title','image_alt');

    foreach ($array as $k => $v) {
       if(in_array($k, $maskField)){
        $v = htmlspecialchars_decode($v);
        $array[$k] = htmlspecialchars($v);       
       }
    }
    

    if (!empty($array['url'])) {
      $array['url'] = URL::prepareUrl($array['url']);
    }

    // Исключает дублирование.
    $dublicatUrl = false;
    $tempArray = $this->getProductByUrl($array['url']);
    if (!empty($tempArray)) {
      $dublicatUrl = true;
    }

    if($array['weight']){
     $array['weight'] = (double)str_replace(array(',',' '), array('.',''), $array['weight']);
    }

    if($array['price']){
      $array['price'] = (double)str_replace(array(',',' '), array('.',''), $array['price']);
    }
    

    $array['sort'] = 0;
    $array['system_set'] = 1;

    if (DB::buildQuery('INSERT INTO `'.PREFIX.'product` SET ', $array)) {
      $id = DB::insertId();

     
      // Если url дублируется, то дописываем к нему id продукта.
      if ($dublicatUrl) {
        $url_explode = explode('_', $array['url']);
        if (count($url_explode) > 1) {
          $array['url'] = str_replace('_'.array_pop($url_explode), '', $array['url']);
        }
        $this->updateProduct(array('id' => $id, 'url' => $array['url'].'_'.$id, 'sort' => $id));
      } else {
        $this->updateProduct(array('id' => $id, 'url' => $array['url'], 'sort' => $id));
      }
      
      $array['id'] = $id;
      $array['sort'] = (int)$id;
      $array['userProperty'] = $userProperty;
      $userProp = array();

      if ($clone) {
        if (!empty($userProperty)) {
          foreach ($userProperty as $property) {
            $userProp[$property['property_id']] = $property['value'];
            if (!empty($property['product_margin'])) {
              $userProp[("margin_".$property['property_id'])] = $property['product_margin'];
            }
          }
          $userProperty = $userProp;
        }
      }

      if (!empty($userProperty)) {
        $this->saveUserProperty($userProperty, $id);
      }

      // Обновляем и добавляем варианты продукта.      
      $this->saveVariants($variants, $id);
      $variants = $this->getVariants($id);
      foreach ($variants as $variant) {
        $array['variants'][] = $variant;
      }

      $tempProd = $this->getProduct($id);
      $array['category_url'] = $tempProd['category_url'];
      $array['product_url'] = $tempProd['product_url'];

      $result = $array;
    }
    
    $this->updatePriceCourse($currencyShopIso, array($result['id']));  

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Преобразует массив характеристик в удобный для работы вид.
   * @param type $array
   * @return array
   */
  public function preProcessUserProperty($userProperty) {
    $prefixs = array("margin", "type");

    foreach ($userProperty as $propertyId => $value) {
      foreach ($prefixs as $prefix) {
        if (strpos($propertyId, $prefix."_") !== false) {
          $propertyId = str_replace($prefix."_", "", $propertyId);
          if (is_array($userProperty[$propertyId])) {
            $userProperty[$propertyId][$prefix] = $value;
          } else {
            $userProperty[$propertyId] = array(
              'value' => $userProperty[$propertyId],
              $prefix => $value,
            );
          }
        }
      }
    }
    return $userProperty;
  }

  /**
   * Изменяет данные о товаре.
   * @param array $array массив с данными о товаре.
   * @param int $id  id изменяемого товара.
   * @return bool
   */
  public function updateProduct($array) {

    $id = $array['id'];
    $userProperty = !empty($array['userProperty']) ? $array['userProperty'] : null; //свойства товара
    $variants = !empty($array['variants']) ? $array['variants'] : array(); // варианты товара
    $updateFromModal = !empty($array['updateFromModal']) ? true : false; // варианты товара

    unset($array['userProperty']);
    unset($array['variants']);
    unset($array['updateFromModal']);
    unset($array['count_sort']);

    if (!empty($array['url'])) {
        
      $array['url'] = URL::prepareUrl($array['url']);
    }
	
 
    $maskField = array('title','meta_title','meta_keywords','meta_desc','image_title','image_alt');

    foreach ($array as $k => $v) {
       if(in_array($k, $maskField)){
        $v = htmlspecialchars_decode($v);
        $array[$k] = htmlspecialchars($v);       
       }
    }
	
    $result = false;

    // Если происходит обновление параметров.
    if (!empty($id)) {
      unset($array['delete_image']);

      if($array['weight']){
        $array['weight'] = (double)str_replace(array(',',' '), array('.',''), $array['weight']);
      }

      if($array['price']){
        $array['price'] = (double)str_replace(array(',',' '), array('.',''), $array['price']);
      }
      if($array['price_course']){
        $array['price_course'] = (double)str_replace(array(',',' '), array('.',''), $array['price_course']);
      }
      if(empty($array['price_course'])){
        unset($array['price_course']);
      } 
    
      // Обновляем стандартные  свойства продукта.
      if (DB::query('
          UPDATE `'.PREFIX.'product`
          SET '.DB::buildPartQuery($array).'
          WHERE id = '.DB::quote($id))) {

        // Обновляем пользовательские свойства продукта.
        if (!empty($userProperty)) {
          $this->saveUserProperty($userProperty, $id);
        }

        // Эта проверка нужна только для того, чтобы исключить удаление 
        //вариантов при обновлении продуктов не из карточки товара в админке, 
        //например по нажатию на "лампочку".
        if (!empty($variants) || $updateFromModal) {

          // обновляем и добавляем варианты продукта.
          if ($variants === null) {
            $variants = array();
          }
     
          $this->saveVariants($variants, $id);
        }

        $result = true;
      }
    } else {
      $result = $this->addProduct($array);
    }
    
    $currencyShopIso = MG::getSetting('currencyShopIso');  
    
    $this->updatePriceCourse($currencyShopIso, array($id));   

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Обновляет поле в варианте и синхронизирует привязку первого варианта с продуктом.
   * @param type $id - id варианта.
   * @param type $array - ассоциативный массив поле=>значение.
   * @param type $product_id - id продукта.
   * @return boolean
   */
  public function fastUpdateProductVariant($id, $array, $product_id) {
    if (!DB::query('
       UPDATE `'.PREFIX.'product_variant`
       SET '.DB::buildPartQuery($array).'
       WHERE id = '.DB::quote($id))) {
      return false;
    };
  
    // Следующие действия выполняются для синхронизации  значений первого 
    // варианта со значениями записи продукта из таблицы product.
    // Перезаписываем в $array новое значение от первого в списке варианта,
    // и получаем id продукта от этого варианта
    $variants = $this->getVariants($product_id);
   
    $field = array_keys($array);
    foreach ($variants as $key => $value) {
      $array[$field[0]] = $value[$field[0]];
      break;
    }

    // Обновляем продукт в соответствии с первым вариантом.
    $this->fastUpdateProduct($product_id, $array);
    return true;
  }

  /**
   * Аналогичная fastUpdateProductVariant функция, но с поправками для
   * процесса импорта вариантов.
   * @param int $id id варианта.
   * @param array $array массив поле = значение.
   * @param int $product_id id продукта.
   * @return boolean
   */
  public function importUpdateProductVariant($id, $array, $product_id) {

    if($array['weight']){
     $array['weight'] = (double)str_replace(array(',',' '), array('.',''), $array['weight']);
    }

    if($array['price']){
      $array['price'] = (double)str_replace(array(',',' '), array('.',''), $array['price']);
    }

    if($array['price_course']){
      $array['price_course'] = (double)str_replace(array(',',' '), array('.',''), $array['price_course']);
    }
    if(empty($array['price_course'])){
      unset($array['price_course']);
    }
    
    if (!$id || !DB::query('
       UPDATE `'.PREFIX.'product_variant`
       SET '.DB::buildPartQuery($array).'
       WHERE id = %d
     ', $id)) {

      DB::query('
       INSERT INTO `'.PREFIX.'product_variant`
       SET '.DB::buildPartQuery($array)
      );
    };

    return true;
  }

  /**
   * Обновление заданного поля продукта.
   * @param int $id - id продукта.
   * @param array $array - параметры для обновления.
   * @return boolean
   */
  public function fastUpdateProduct($id, $array) {
    if($array['price']){
      $array['price'] = (double)str_replace(array(',',' '), array('.',''), $array['price']);
    }
    if($array['sort']){
      $array['sort'] = (int)str_replace(array(',',' '), array('.',''), $array['price']);
    }
    if($array['count']){
      $array['count'] = (int)str_replace(array(',',' '), array('.',''), $array['count']);
    }
    
    if (!DB::query('
      UPDATE `'.PREFIX.'product`
      SET '.DB::buildPartQuery($array).'
      WHERE id = %d
    ', $id)) {
      return false;
    };
    
    $currencyShopIso = MG::getSetting('currencyShopIso');  
    $this->updatePriceCourse($currencyShopIso, array($id));   
    
    return true;
  }

  /**
   * Сохраняет пользовательские характеристики для товара.
   * @param int $id - id товара.
   * @param array $userProperty набор характеристик.
   * @return boolean
   */
  public function saveUserProperty($userProperty, $id, $type = 'select') {
 
    $userProperty = $this->preProcessUserProperty($userProperty);

    foreach ($userProperty as $propertyId => $value) {
      $propertyId = (int)$propertyId;
      // Проверяем существует ли запись в базе о текущем свойстве.
      $res = DB::query("
        SELECT * FROM `".PREFIX."product_user_property`
        WHERE property_id = ".DB::quote($propertyId)."
          AND product_id = ".DB::quote($id)
      );
      
      // Обновляем значение свойства если оно существовало.
      if (DB::numRows($res) && is_array($value)) {
        if (!is_array($value)) {
          DB::query("
            UPDATE `".PREFIX."product_user_property`
            SET value = ".DB::quote(trim($value))."
            WHERE property_id = ".DB::quote($propertyId)."
              AND product_id = ".DB::quote($id)
          );
        } else {
          DB::query("
            UPDATE `".PREFIX."product_user_property`
            SET value = ".DB::quote(trim($value['value'])).",
              product_margin = ".DB::quote($value['margin']).",
              type_view = ".DB::quote($value['type'])."
            WHERE property_id = ".DB::quote($propertyId)."
              AND product_id = ".DB::quote($id)
          );
        }
      } else {

        // Создаем новую запись со значением свойства
        // если его небыло сохранено ранее.
        if (!is_array($value)) {
          DB::query("
            INSERT INTO `".PREFIX."product_user_property`
            VALUES (
            ".DB::quote($id).",
            ".DB::quote($propertyId).",
            ".DB::quote(trim($value)).",'', ".DB::quote($type).")");
        } else {
          DB::query("
            INSERT INTO `".PREFIX."product_user_property`
            VALUES (
            ".DB::quote($id).",
            ".DB::quote($propertyId).",
            ".DB::quote(trim($value['value'])).",
            ".DB::quote($value['margin']).",
            ".DB::quote($value['type'])."
            )");
        }
      }
    }
  }

  /**
   * Сохраняет варианты товара.
   * @param int $id  id товара
   * @param array $variants набор вариантов
   * @return bool
   */
  public function saveVariants($variants = array(), $id) {
    $existsVariant = array();
    
    $dbRes = DB::query('SHOW COLUMNS FROM `'.PREFIX.'product` WHERE FIELD = \'system_set\'');
    if(!$row = DB::fetchArray($dbRes)){
      return false;
    }
    
    $dbRes = DB::query(""
            . "SELECT * "
            . "FROM `".PREFIX."product_variant` "
            . "WHERE product_id = ".DB::quote($id));
    
    while ($arRes = DB::fetchAssoc($dbRes)) {
      $existsVariant[$arRes['id']] = $arRes;
    }
    
    // Удаляем все имеющиеся товары.
    $res = DB::query("
      DELETE FROM `".PREFIX."product_variant` WHERE product_id = ".DB::quote($id)
    );

    // Если вариантов как минимум два.
   // if (count($variants) > 1) {
      // Сохраняем все отредактированные варианты.
      $i = 1;
      foreach ($variants as $variant) {
        if (!empty($existsVariant[$variant['id']]['1c_id'])) {
          $variant['1c_id'] = $existsVariant[$variant['id']]['1c_id'];
        }
        
        $variant['sort'] = $i++;
        unset($variant['product_id']);
        unset($variant['rate']);
        unset($variant['count_sort']);
        DB::query(' 
          INSERT  INTO `'.PREFIX.'product_variant` 
          SET product_id= '.DB::quote($id).", ".DB::buildPartQuery($variant)
        );
      }
   // }
  }

  /**
   * Клонируем товар.
   * @param int $id  id клонируемого товара.
   * @return bool
   */
  public function cloneProduct($id) {
    $result = false;

    $arr = $this->getProduct($id);
    $arr['title'] = htmlspecialchars_decode($arr['title']);
    $image_url = basename($arr['image_url']);         
    
    foreach ($arr['images_product'] as $k=>$image) {
      $arr['images_product'][$k] = basename($image);
    }   
    $arr['image_url'] = implode("|", $arr['images_product']);
    $imagesArray = $arr['images_product'];
    
    $userProperty = $arr['thisUserFields'];
    unset($arr['category_name']);
    unset($arr['thisUserFields']);
    unset($arr['category_url']);
    unset($arr['product_url']);
    unset($arr['images_product']);
    unset($arr['images_title']);
    unset($arr['images_alt']);
    unset($arr['rate']);    
    unset($arr['plugin_message']);
    unset($arr['id']);
    unset($arr['count_buy']);
    $arr['code'] = '';
    $arr['userProperty'] = $userProperty;
    $variants = $this->getVariants($id);
    
    foreach ($variants as &$item) {
      unset($item['id']);
      unset($item['product_id']);
      unset($item['rate']);      
      $item['code'] = '';
      $imagesArray[] = $item['image'];
      $item['image'] = $item['image'];      
    }
    
    $arr['variants'] = $variants;    
    
    // перед клонированием создадим копии изображений, 
    // чтобы в будущем можно было без проблемно удалять их вместе с удалением продукта       
    $result = $this->addProduct($arr, true);
    
    $this->cloneImagesProduct($imagesArray, $id, $result['id']); 
    
    $result['image_url'] = $image_url;
    $result['currency'] = MG::getSetting('currency');

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }
  
   /**
   * Клонирует изображения продукта.
   * @param array $imagesArray - массив url изображений, которые надо клонировать.
   * @return bool
   */
  public function cloneImagesProduct($imagesArray=array(), $oldId = 0, $newId = 0) { 

    if(!$oldId && !$newId){
      return false;
    }
    
    $ds = DIRECTORY_SEPARATOR;
    $documentroot = str_replace($ds.'mg-core'.$ds.'models','',dirname(__FILE__)).$ds;     
    $dir = floor($oldId/100).'00'.$ds.$oldId;        
    $this->movingProductImage($imagesArray, $newId, 'uploads'.$ds.'product'.$ds.$dir, false);
 
    return true;
  }

  /**
   * Удаляет товар из базы данных.
   *
   * @param int $id id удаляемого товара
   * @return bool
   */
  public function deleteProduct($id) {
    $result = false;
    $prodInfo = $this->getProduct($id);  
       
    $this->deleteImagesProduct($prodInfo['images_product'], $id); 
    $this->deleteImagesVariant($id); 
    $this->deleteImagesFolder($id);

    // Удаляем продукт из базы.
    DB::query('
      DELETE
      FROM `'.PREFIX.'product`
      WHERE id = %d
    ', $id);

    // Удаляем все значения пользовательских характеристик данного продукта.
    DB::query('
      DELETE
      FROM `'.PREFIX.'product_user_property`
      WHERE product_id = %d
    ', $id);

    // Удаляем все варианты данного продукта.
    DB::query('
      DELETE
      FROM `'.PREFIX.'product_variant`
      WHERE product_id = %d
    ', $id);

    $result = true;
    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

   /**
   * Удаляет папки из структуры папок изображений относящиеся к заданному продукту.
   * @param int $id id товара.
   */
  public function deleteImagesFolder($id){
    if(!empty($id)){
      $ds = DIRECTORY_SEPARATOR;
      $path = 'uploads'.$ds.'product'.$ds.floor($id/100).'00'.$ds.$id;
      
      if(file_exists($path)){
        
        if(file_exists($path.$ds.'thumbs')){
          rmdir($path.$ds.'thumbs');
        }
        
        rmdir($path);
      }
    }
  }
  /**
   * Удаляет все картинки привязанные к продукту.
   */
   public function deleteImagesProduct($arrayImages = array(), $productId = false) {
     if(empty($arrayImages)){       
       return true;
     }     
     // удаление картинки с сервера
    $uploader = new Upload(false);   
    foreach ($arrayImages as $key => $imageName) {
      $pos = strpos($imageName, 'no-img');
      if(!$pos && $pos !== 0){
        $uploader->deleteImageProduct($imageName, $productId);     
      }
    }
     
   }
  /**
   * Получает информацию о запрашиваемом товаре.
   * @param string $where необязательный параметр, формирующий условия поиска, например: id = 1
   * @return array массив заказов
   */
  public function getProductByUserFilter($where = '') {
    $result = array();

    if ($where) {
      $where = ' WHERE '.$where;
    }
    
    $res = DB::query('
     SELECT  CONCAT(c.parent_url,c.url) as category_url,
       p.url as product_url, p.*, rate,(p.price_course + p.price_course * (IFNULL(rate,0))) as `price_course`, 
       p.`currency_iso`
     FROM `'.PREFIX.'product` p
       LEFT JOIN `'.PREFIX.'category` c
       ON c.id = p.cat_id
     '.$where);
    
    while ($order = DB::fetchAssoc($res)) {
      $result[$order['id']] = $order;
    }
    return $result;
  }

  /**
   * Получает информацию о запрашиваемом товаре по его ID.
   * @param int $id id запрашиваемого товара.
   * @return array массив с данными о товаре.
   */
  public function getProduct($id) {         
    $id =  intval($id);
    $result = array();
    $res = DB::query('
      SELECT  CONCAT(c.parent_url,c.url) as category_url, c.title as category_name,
        p.url as product_url, p.*, rate, (p.price_course + p.price_course * (IFNULL(rate,0))) as `price_course`, 
        p.`currency_iso` 
      FROM `'.PREFIX.'product` p
        LEFT JOIN `'.PREFIX.'category` c
        ON c.id = p.cat_id
      WHERE p.id = '.DB::quote($id, true));
   
    if (!empty($res)) {
      if ($product = DB::fetchAssoc($res)) {
        $result = $product;

        // Запрос делает следующее 
        // 1. Вычисляет список пользовательских характеристик для категории товара, 
        // 2. Присваивает всем параметрам значения по умолчанию, 
        // 3. Находит заполненные характеристики товара, заменяет ими значения по умолчанию.
        // В результате получаем набор всех пользовательских характеристик включая те, что небыли определены явно.

        $res = DB::query("
          SELECT pup.property_id, pup.value, pup.product_margin, pup.type_view, prop.*
          FROM `".PREFIX."product_user_property` as pup
          LEFT JOIN `".PREFIX."property` as prop
            ON pup.property_id = prop.id
          LEFT JOIN  `".PREFIX."category_user_property` as cup 
            ON cup.property_id = prop.id
          WHERE pup.`product_id` = ".DB::quote($id)." AND cup.category_id = ".DB::quote($result['cat_id'])."
            
          ORDER BY `sort` DESC;
        ");

        while ($userFields = DB::fetchAssoc($res)) {
       
          if(in_array($userFields['property_id'],MG::get('category')->getPropertyForCategoryById($product['cat_id'])) ){
            // Заполняет каждый товар его характеристиками.
            $result['thisUserFields'][$userFields['property_id']] = $userFields;   
          }
        
        }

        $imagesConctructions = $this->imagesConctruction($result['image_url'],$result['image_title'],$result['image_alt'], $result['id']);
        $result['images_product'] = $imagesConctructions['images_product']; 
        $result['images_title'] = $imagesConctructions['images_title']; 
        $result['images_alt'] = $imagesConctructions['images_alt']; 
        $result['image_url'] = $imagesConctructions['image_url']; 
        $result['image_title'] = $imagesConctructions['image_title']; 
        $result['image_alt'] = $imagesConctructions['image_alt']; 
     
      }
    }
   
    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }
  
  /**
   * Создает массивы данных для картинок товара, возвращает три массива со ссылками, заголовками и альт, текстами.
   * @param string $imageUrl строка с разделителями | между ссылок.
   * @param string $imageTitle строка с разделителями | между заголовков.
   * @param string $imageAlt строка с разделителями | между тестов.
   */
  public function imagesConctruction($imageUrl, $imageTitle, $imageAlt, $id = 0) {
    $result = array(
      'images_product'=>array(),
      'images_title'=>array(),
      'images_alt'=>array()
    );
    
    // Получаем массив картинок для продукта, при этом первую в наборе делаем основной.
    $arrayImages = explode("|", $imageUrl);
    
    foreach($arrayImages as $cell=>$image){
      $arrayImages[$cell] = str_replace(SITE.'/uploads/', '', mgImageProductPath($image, $id));
    }
    
    if (!empty($arrayImages)) {
      $result['image_url'] = $arrayImages[0];
    }

    $result['images_product'] = $arrayImages;  
    // Получаем массив title для картинок продукта, при этом первый в наборе делаем основной.
    $arrayTitles = explode("|", $imageTitle);
    if (!empty($arrayTitles)) {
      $result['image_title'] = $arrayTitles[0];
    }

    $result['images_title'] = $arrayTitles;  

    // Получаем массив alt для картинок продукта, при этом первый в наборе делаем основной.
    $arrayAlt = explode("|", $imageAlt);
    if (!empty($arrayAlt)) {
      $result['image_alt'] = $arrayAlt[0];
    }

    $result['images_alt'] = $arrayAlt;  
    
    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }
    
  /**
   * Обновляет остатки продукта, увеличивая их на заданное количество.
   * @param int $id номер продукта.
   * @param int $count прибавляемое значение к остатку.
   * @param string $code артикул.
   */
  public function increaseCountProduct($id, $code, $count) {

    $sql = "
      UPDATE `".PREFIX."product_variant` as pv 
      SET pv.`count`= pv.`count`+".DB::quote($count)." 
      WHERE pv.`product_id`=".DB::quote($id)." 
        AND pv.`code`=".DB::quote($code)." 
        AND pv.`count`>=0
    ";

    DB::query($sql);

    $sql = "
      UPDATE `".PREFIX."product` as p 
      SET p.`count`= p.`count`+".DB::quote($count)." 
      WHERE p.`id`=".DB::quote($id)." 
        AND p.`code`=".DB::quote($code)." 
        AND  p.`count`>=0
    ";

    DB::query($sql);
  }

  /**
   * Обновляет остатки продукта, уменьшая их количество,
   * при смене статуса заказа с "отменет" на любой другой.
   * 
   * @param int $id - Номер продукта.
   * @param int $count - Прибавляемое значение к остатку.
   * @param string $code - Артикул.
   */
  public function decreaseCountProduct($id, $code, $count) {

    $product = $this->getProduct($id);
    $variants = $this->getVariants($product['id']);
    foreach ($variants as $idVar => $variant) {
      if ($variant['code'] == $code) {
        $variantCount = ($variant['count'] * 1 - $count * 1) >= 0 ? $variant['count'] - $count : 0;
        $sql = "
          UPDATE `".PREFIX."product_variant` as pv 
          SET pv.`count`= ".DB::quote($variantCount, true)." 
          WHERE pv.`id`=".DB::quote($idVar)." 
            AND pv.`code`=".DB::quote($code)." 
            AND  pv.`count`>0";
        DB::query($sql);
      }
    }

    $product['count'] = ($product['count'] * 1 - $count * 1) >= 0 ? $product['count'] - $count : 0;
    $sql = "
      UPDATE `".PREFIX."product` as p 
      SET p.`count`= ".DB::quote($product['count'], true)." 
      WHERE p.`id`=".DB::quote($id)." 
        AND p.`code`=".DB::quote($code)."
        AND  p.`count`>0";
    DB::query($sql);
  }

  /**
   * Удаляет все миниатюры и оригинал изображения товара из папки upload.
   * @return bool
   */
  public function deleteImageProduct($arrayDelImages) {
    if (!empty($arrayDelImages)) {
      foreach ($arrayDelImages as $value) {
        if (!empty($value)) {

          // Удаление картинки с сервера.
          $documentroot = str_replace('mg-core'.$ds.'models', '', __DIR__);
          if (is_file($documentroot."uploads/".basename($value))) {
            unlink($documentroot."uploads/".basename($value));

            if (is_file($documentroot."uploads/thumbs/30_".basename($value))) {
              unlink($documentroot."uploads/thumbs/30_".basename($value));
            }
            if (is_file($documentroot."uploads/thumbs/70_".basename($value))) {
              unlink($documentroot."uploads/thumbs/70_".basename($value));
            }
          }
        }
      }
    }

    return true;
  }

  /**
   * Возвращает общее количество продуктов каталога.
   * @param int $id id запрашиваемого товара.
   * @return array массив с данными о товаре.
   */
  public function getProductsCount() {
    $result = 0;
    $res = DB::query('
      SELECT count(id) as count
      FROM `'.PREFIX.'product`
    ');

    if ($product = DB::fetchAssoc($res)) {
      $result = $product['count'];
    }

    return $result;
  }

  /**
   * Получает продукт по его URL.
   * @param string $url запрашиваемого товара.
   * @param int $catId id-категории, т.к. в разных категориях могут быть одинаковые url.
   * @return array массив с данными о товаре.
   *
   */
  public function getProductByUrl($url, $catId = false) {
    $result = array();
    if ($catId !== false) {
      $where = ' and cat_id='.DB::quote($catId);
    }

    $res = DB::query('
      SELECT *
      FROM `'.PREFIX.'product`
      WHERE url = '.DB::quote($url).' 
    '.$where);
   
    if (!empty($res)) {
      if ($product = DB::fetchAssoc($res)) {
        $result = $product;
      }
    }

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Получает цену запрашиваемого товара по его id.
   * @param int $id id изменяемого товара.
   * @return bool|float $error в случаи ошибочного запроса.
   */
  public function getProductPrice($id) {
    $result = false;
    $res = DB::query('
      SELECT price
      FROM `'.PREFIX.'product`
      WHERE id = %d
    ', $id);

    if ($row = DB::fetchObject($res)) {
      $result = $row->price;
    }

    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Создает форму пользовательских характеристик для товара.
   * В качестве входящего параметра получает массив:
   * <code>
   * $param = array(
   * 'id' => null, // id товара.
   * 'maxCount' => null, // максимальное количество товара на складе.
   * 'productUserFields' => null, // массив пользовательских полей для данного продукта.
   * 'action' => "/catalog", // ссылка для метода формы.
   * 'method' => "POST", // тип отправки данных на сервер.
   * 'ajax' => true, // использовать ajax для пересчета стоимости товаров.
   * 'blockedProp' => array(), // массив из ID свойств, которые ненужно выводить в форме.
   * 'noneAmount' => false, // не выводить  input для количества.
   * 'titleBtn' => "В корзину", // название кнопки.
   * 'blockVariants' => '', // блок вариантов.
   * 'classForButton' => 'addToCart buy-product buy', // классы для кнопки.
   * 'noneButton' => false, // не выводить кнопку отправки.
   * 'addHtml' => '' // добавить HTML в содержимое формы.
   * 'currency_iso' => '', // обозначение валюты в которой сохранен товар
   * )
   * </code>
   * @param int $param - массив параметров.
   * @return string html форма.
   */
  public function createPropertyForm(
  $param = array(
    'id' => null,
    'maxCount' => null,
    'productUserFields' => null,
    'action' => "/catalog",
    'method' => "POST",
    'ajax' => true,
    'blockedProp' => array(),
    'noneAmount' => false,
    'titleBtn' => "В корзину",
    'blockVariants' => '',
    'classForButton' => 'addToCart buy-product buy',
    'noneButton' => false,
    'addHtml' => '',   
    'printStrProp' => null,
    'printCompareButton' => null,
    'buyButton' => '',
    'currency_iso' => '',
    'productData' => null,
  )
  ) {
    extract($param);
    if (empty($classForButton)) {
      $classForButton = 'addToCart buy-product buy';
    }
    if ($id === null || $maxCount === null) {
      return "error param!";
    }
    if (empty($printStrProp)) {
      $printStrProp = MG::getSetting('printStrProp');    
    }
    if ($printCompareButton===null) {
      $printCompareButton = MG::getSetting('printCompareButton');    
    }
    $catalogAction = MG::getSetting('actionInCatalog') === "true" ? 'actionBuy' : 'actionView';
    // если используется аяксовый метод выбора, то подключаем доп класс для работы с формой. 
    $marginPrice = 0; // добавочная цена, в зависимости от выбранных автоматом характеристик
    $secctionCartNoDummy = array(); //Не подставной массив характеристик, все характеристики с настоящими #ценами#
    //в сессию записать реальные значения, в паблик подмену, с привязкой в конце #№
    $htmlForm = '<form action="'.SITE.$action.'" method="'.$method.'" class="property-form '.$catalogAction.'" data-product-id='.$id.'>';
    $html = '';
   //if ($ajax) {
    //  mgAddMeta("<script type=\"text/javascript\" src=\"".SITE."/mg-core/script/jquery.form.js\"></script>");
    //}

    $currencyRate = MG::getSetting('currencyRate');
    $currencyShort = MG::getSetting('currencyShort');
    $currencyRate = $currencyRate[$currency_iso];
    $currencyShort = $currencyShort[$currency_iso];
  
    if (!empty($productUserFields)) {
      $defaultSet = array(); // набор характеристик предоставленных по умолчанию.  
      // массив со строковыми характеристиками
      $stringsProperties = array();   
          
      foreach ($productUserFields as $property) {
          
        if (in_array($property['id'], $blockedProp)) {
          continue;
        }

        $collectionParse = array();
        $collectionAccess = array();
        /*
          'select' - набор значений, можно интерпретировать как  выпадающий список либо набор радиокнопок
          'assortment' - мультиселект
          'string' - пара ключ-значение
          'assortmentCheckBox' - набор чекбоксов
         */
    
        switch ($property['type']) {

          case 'select': {
  
              $marginStoper = $marginPrice;
              
              $set = $property['product_margin'] ? $property['product_margin'] : $property['data'];   
       
              if(empty($set)){
                 break; 
              }
              $collection = explode('|', $set);
              
               $firstLiMargin = 0;
               $isExistSelected = false;
               
                foreach ($collection as $value) {   
                    $tempVar = $this->parseMarginToProp($value);
                    if($tempVar['name']){
                      $collectionParse[$tempVar['name']]  =  $tempVar['margin'];                      
                    }else{
                      $collectionParse[$value]  =  0;                          
                    }
                }
          
            
                $collectionAccess = array(); // допустимый актуальный перечень
                foreach (explode('|', $property['product_margin']) as $value) {   
                    $tempVar = $this->parseMarginToProp($value);
                    if($tempVar['name']){
                      $collectionAccess[]  =  $tempVar['name'];                      
                    }else{
                      $collectionAccess[]  =  $value;                          
                    }
                }
             
              // для типа вывода select :
              if ($property['type_view'] == 'select' || empty($property['type_view']) ||$property['type_view']=='type_view') {

                // для типа вывода select :                
                if ($property['value'] == 'null') {
                  break;
                }
                $html .= '<p class="select-type"><span class="property-title">'.$property['name'].'<span class="property-delimiter">:</span> </span> <select name="'.$property['name'].'" class="last-items-dropdown">';
                                     
                $i = 0;  
                $firstLiMargin = 0;
                $isExistSelected = false;
               
                foreach ($collectionAccess as $value) {
                 
                  if(!array_key_exists($value,$collectionParse)){
                    continue; 
                  }
                  $value = $value."#".$collectionParse[$value]."#";
                                
                  
                  $valueArr = $this->parseMarginToProp($value);
                  if (empty($valueArr)) {
                    $valueArr = array('name' => $value, 'margin' => 0);
                  }
                  $plus = $this->addMarginToProp($valueArr['margin'], $currencyRate, $currencyShort);
                  $plus = OUTPUT_MARGIN=='0' ? '' : $plus;
                  $selected = "";
                  
           
                  if($i==0){
                    // сразу делаем выбранным по умолчанию первый пункт ( если бы страница отработала)
                    $defaultSet[$property['property_id'].'#0'] = $value;              
                    // запоминаем наценку для первого пункта
                    $firstLiMargin = $valueArr['margin'];
                    $selected = ' selected="selected" ';
                  }
                  
                  
                  if ($marginStoper == $marginPrice) {
                   
                    // только один раз добавляем цену и выделяем пункт 
                    // (т.к. не исключена возможность нескольких 
                    // выделенных пунктов)
                   
                    if ($property['value'] == $value) {
                      $selected = ' selected="selected" ';
                      $marginPrice += $valueArr['margin'];
                      
                      // отменяем выбранный первый пункт 
                      unset($defaultSet[$property['property_id'].'#0']);                       
                      // запоминаем дефолтное значение
                      $defaultSet[$property['property_id'].'#'.$i] = $value;
                     
                      $isExistSelected = true;
                    };
                  }
                  

                  $html .= '<option value="'.$property['property_id'].'#'.$i.'" '.
                  $selected.'>'.$valueArr['name'].$plus.'</option>';
                  $secctionCartNoDummy[$property['property_id']][$i++] = array(
                    'value' => $value,
                    'name' => $property['name']
                  );
                }
                
                // если  в перечне пунктов не нашлось  выбранного по умолчанию.
                // такое может случиться если список был отредактирован в настройках характеристики и старые значения удалены.
                if(!$isExistSelected){
                  $marginPrice += $firstLiMargin;
                }
                

                $html .= '</select>';
                $html .= "</p>";
                break;
              } elseif ($property['type_view'] == 'radiobutton') {

                if ($property['value'] == 'null') {
                  break;
                }

                $htmlRadio = '<p><span class="property-title">'.$property['name'].'</span>:<br/>';
                $htmlRadioBtn = '';
  
                $i = 0;
                
                foreach ($collectionAccess as $value) {
                  
                  if(!array_key_exists($value,$collectionParse)){
                    continue; 
                  }
                  $value = $value."#".$collectionParse[$value]."#";
                  
                 
                  $valueArr = $this->parseMarginToProp($value);

                  if (empty($valueArr)) {
                    $valueArr = array('name' => $value, 'margin' => 0);
                  }
                  $checked = '';

                  if ($i == 0) {
                    $checked = ' #checked="checked"# ';
                    $defaultSet[$property['property_id'].'#0'] = $value;              
                    // запоминаем наценку для первого пункта
                    $firstLiMargin = $valueArr['margin'];                      
                  };
                  
                  $plus = $this->addMarginToProp($valueArr['margin'], $currencyRate, $currencyShort);
                  $plus = OUTPUT_MARGIN=='0' ? '' : $plus;

                  if ($marginStoper == $marginPrice) {
                    
                    // Только один раз добавляем цену и выделяем пункт 
                    // (т.к. не исключена возможность нескольких выделенных пунктов).
                    if ($property['value'] == $value) {
                      $checked = ' checked="checked" ';
                      $marginPrice += $valueArr['margin'];
                      
                      // отменяем выбранный первый пункт 
                      unset($defaultSet[$property['property_id'].'#0']);                       
                      // запоминаем дефолтное значение
                      $defaultSet[$property['property_id'].'#'.$i] = $value;
                     
                      $isExistSelected = true;
                   
                    };
                  }
                  
                  $htmlRadioBtn .= '<label '.($checked ? 'class="active"': '').'><input type="radio" name="'.
                  $property['property_id'].'#'.$i.'" value="'.$value.'" '.
                    $checked.'>
                    <span class="label-black">'.$valueArr['name'].$plus.'</span></label><br>';
                  $secctionCartNoDummy[$property['property_id']][$i++] = array(
                    'value' => $value,
                    'name' => $property['name']);
                }

                // если  в перечне пунктов не нашлось  выбранного по умолчанию.
                // такое может случиться если список был отредактирован в настройках характеристики и старые значения удалены.
                if(!$isExistSelected){
                  $marginPrice += $firstLiMargin;
                  $htmlRadioBtn = str_replace('#checked="checked"#', 'checked="checked"', $htmlRadioBtn);
                }else{
                  $htmlRadioBtn = str_replace('#checked="checked"#', '', $htmlRadioBtn);
                }
                
                $htmlRadio .= $htmlRadioBtn."</p>";
                
                if($htmlRadioBtn){
                   $html .= $htmlRadio;
                }
                
                break;
              } else {

                $html .= '<p class="select-type"><span class="property-title">'.$property['name'].'<span class="property-delimiter">:</span> </span> <select name="'.$property['name'].'" class="last-items-dropdown">';
                $collection = explode('|', $property['data']);
                $i = 0;

                foreach ($collection as $value) {
                  $valueArr = $this->parseMarginToProp($value);
                  $selected = "";

                  if ($marginStoper == $marginPrice) {

                    // Только один раз добавляем цену и выделяем пункт 
                    // (т.к. не исключена возможность нескольких выделенных пунктов).
                    if ($property['value'] == $valueArr['name']) {
                      $selected = ' selected="selected" ';
                      $marginPrice += $valueArr['margin'];
                    };
                  }

                  $text = !empty($valueArr['margin']) ? $valueArr['name'].' + '.$valueArr['margin'].' '.MG::getSetting('currency') : $value;
                  $html .= '<option value="'.$property['property_id'].'#'.$i.'" '.$selected.'>'.$text.'</option>';
                  $secctionCartNoDummy[$property['property_id']][$i++] = array('value' => $value, 'name' => $property['name']);
                }
                $html .= '</select>';
                $html .= "</p>";
                break;
              }
            }

          case 'assortmentCheckBox': {
              DB::query('SELECT `id` FROM `'.PREFIX.'product` WHERE `system_set` = '.DB::quote(base64_encode($property['name'])));
              $marginStoper = $marginPrice;
              $htmladd = '';
              $htmladd .= '<p><span class="property-title">'.$property['name'].'</span><span class="property-delimiter">:</span> <span class="label-black">';         
         
              $collection = explode('|', $property['data']);

              $values = explode('|', $property['value']);

              foreach ($values as $k => $value) {
                $valueArr = $this->parseMarginToProp($value);
                if (empty($valueArr)) {
                  $valueArr = array('name' => $value, 'margin' => 0);
                }
                $values[$k] = $valueArr['name'];
              }

              $htmladdIn = '';

              foreach ($collection as $value) {
                $valueArr = $this->parseMarginToProp($value);

                if (empty($valueArr)) {
                  $valueArr = array('name' => $value, 'margin' => 0);
                }

                if (in_array($valueArr['name'], $values)) {
                  $htmladdIn .= ''.$value.', ';
                }
              }

              $htmladdIn = substr($htmladdIn, 0, -2);
              $htmladd .= $htmladdIn;
              $htmladd .= '';
              $htmladd .= "</span></p>";

              if (!$htmladdIn) {
                $htmladd = '';
              };
              
              // сохраняем в массив строковых переменных
              $stringsProperties[$property['name']] = $htmladdIn;
              
              // не выводим если ненужно
              if($printStrProp=='true'){
                $html .= $htmladd;          
              }
              
              break;
            }

          case 'assortment': {
              $marginStoper = $marginPrice;
          
                $property['value'] = $property['value']?$property['value']:$property['default'];               
                $property['property_id'] = $property['property_id']?$property['property_id']:$property['id'];                
                $collection = explode('|', $property['value']);
    
                $i = 0;               
                $firstLiMargin = 0;
                $isExistSelected = false;
               
                foreach ($collection as $value) {   
                    $tempVar = $this->parseMarginToProp($value);
                    if($tempVar['name']){
                      $collectionParse[$tempVar['name']]  =  $tempVar['margin'];                      
                    }else{
                      $collectionParse[$value]  =  0;                          
                    }
                }                 
            
                $collectionAccess = array(); // допустимый актуальный перечень
                foreach (explode('|', $property['product_margin']) as $value) {   
                    $tempVar = $this->parseMarginToProp($value);
                    if($tempVar['name']){
                      $collectionAccess[]  =  $tempVar['name'];                      
                    }else{
                      $collectionAccess[]  =  $value;                          
                    }
                }
                            
              // для типа вывода select :
              if ($property['type_view'] == 'select' || empty($property['type_view']) || $property['type_view'] == 'type_view') {
                // для типа вывода select :
                if ($property['value'] == 'null') {
                  break;
                }
               
               
               
                $htmlSelect = '<p class="select-type"><span class="property-title">'.$property['name'].'<span class="property-delimiter">:</span> </span><select name="'.$property['name'].'" class="last-items-dropdown">';
                
            
        
                foreach ($collectionAccess as $value) {   
             
                  if(!array_key_exists($value,$collectionParse)){
                    continue; 
                  }
                 
                  $value = $value."#".$collectionParse[$value]."#";
              
                  $valueArr = $this->parseMarginToProp($value);
                                    
                  if (empty($valueArr)) {
                    $valueArr = array('name' => $value, 'margin' => 0);
                  }
                  
                   // если заданного  значения уже нет в настройках  характеристики то не обрабатываем его.             
                  if(!in_array($valueArr['name'], $collectionAccess)){
                    continue; 
                  }
                  
                  $plus = $this->addMarginToProp($valueArr['margin'], $currencyRate, $currencyShort);
                  $plus = OUTPUT_MARGIN=='0' ? '' : $plus;
                  $selected = "";

                  if ($marginStoper == $marginPrice) {

                    // только один раз добавляем цену и выделяем пункт
                    //  (т.к. не исключена возможнось нескольких выделанных пунктов)                   
                    if ($i == 0) {
                     
                      $selected = ' selected="selected" ';
                      $marginPrice += $valueArr['margin'];
                     
                      // запоминаем дефолтное значение
                      $defaultSet[$property['property_id'].'#'.$i] = $value;
                      $isExistSelected = true;
                       
                    };
                  }
              
                  $htmlSelect .= '<option value="'.$property['property_id'].'#'.
                    $i.'" '.$selected.'>'.$valueArr['name'].$plus.'</option>';
                  $secctionCartNoDummy[$property['property_id']][$i++] = array(
                    'value' => $value,
                    'name' => $property['name']);
                }

                $htmlSelect .= '</select></p>';
                
                if($isExistSelected){
                  $html .= $htmlSelect;
                }                  
     
                break;
              }

              // Для типа вывода radiobutton :              
              if ($property['type_view'] == 'radiobutton') {
                if ($property['value'] == 'null') {
                  break;
                }
                $htmlRadiobutton = '<span class="property-title">'.$property['name'].'</span><span class="property-delimiter">:</span><br/>';
                $collection = explode('|', $property['value']);    
                $i = 0;
                $htmlButtonList = '';
                
               foreach ($collectionAccess as $value) {
                  if(!array_key_exists($value,$collectionParse)){
                    continue; 
                  }
                  $value = $value."#".$collectionParse[$value]."#";
                  
                  $valueArr = $this->parseMarginToProp($value);
                  
                  if (empty($valueArr)) {
                    $valueArr = array('name' => $value, 'margin' => 0);                    
                  }
                  $plus = $this->addMarginToProp($valueArr['margin'], $currencyRate, $currencyShort);
                  $plus = OUTPUT_MARGIN=='0' ? '' : $plus;
                  $checked = '';
                  if ($i == 0) {
                    $checked = ' checked="checked" ';                    
                    
                    // запоминаем дефолтное значение
                    $defaultSet[$property['property_id'].'#'.$i] = $value;
                    
                    if ($marginStoper == $marginPrice) {
                      $marginPrice += $valueArr['margin'];
                      $isExistSelected = true;
                    }
                  };

                  $htmlButtonList .= '<label '.($checked ? 'class="active"': '').'><input type="radio" name="'.
                    $property['property_id'].'#'.$i.'" value="'.$value.'" '.$checked.'>
                    <span class="label-black">'.$valueArr['name'].$plus.'</span></label><br>';
                  
                  $secctionCartNoDummy[$property['property_id']][$i++] = array(
                    'value' => $value,
                    'name' => $property['name']);
                }

                if($htmlButtonList){
                 
                $html .= "<p>".$htmlRadiobutton.$htmlButtonList."</p>";                
                }
                break;
              }

              // Для типа вывода checkbox:                    
              if ($property['type_view'] == 'checkbox') {
               
                if ($property['value'] == 'null') {
                  break;
                }
                $html .= '<p><span class="property-title">'.$property['name'].'</span><span class="property-delimiter">:</span><br/>';

                $collection = explode('|', $property['value']);
  
                $i = 0;
               foreach ($collectionAccess as $value) {
                  
                  if(!array_key_exists($value,$collectionParse)){
                    continue; 
                  }
                  $value = $value."#".$collectionParse[$value]."#";
                  
                  $valueArr = $this->parseMarginToProp($value);
                  if (empty($valueArr)) {
                    $valueArr = array('name' => $value, 'margin' => 0);
                  }
                  $plus = $this->addMarginToProp($valueArr['margin'], $currencyRate, $currencyShort);
                  $plus = OUTPUT_MARGIN=='0' ? '' : $plus;
                  $html .= '<label><input type="checkbox" name="'.
                    $property['property_id'].'#'.$i.'" value="+'.
                    $valueArr['margin'].' '.MG::getSetting('currency').'">
                    <span class="label-black">'.$valueArr['name'].$plus.' </span></label><br>';
                  $secctionCartNoDummy[$property['property_id']][$i++] = array(
                    'value' => $value,
                    'name' => $property['name']
                  );
                }

                $html .= "</p>";
                break;
              }
            }

          case 'string': {
              $marginStoper = $marginPrice;
              if (!empty($property['value'])) {
                $value = !empty($property['value']) ? $property['value'] : $property['data'];
                $stringsProperties[$property['name']] = $value;
                if($printStrProp=='true'){
                  $html .= '<p><span class="property-title">'.$property['name'].'</span><span class="property-delimiter">:</span> <span class="label-black">'.
                    htmlspecialchars_decode($value).
                  '</span><span class="unit"> '.$property['unit'].'</span></p>';                
                }
              }
              break;
            }

          default:
            if (!empty($property['data'])) {
              $html .= ''.$property['name'].': <span class="label-black">'.
                str_replace("|", ",", $property['data']).
                '</span>';
            }
            break;
        }
      }
    }
   
   
    $htmlProperty = $html;
    $data = array(
     'maxCount' => $maxCount,
     'noneAmount' => $noneAmount,
     'noneButton' => $noneButton,
     'printCompareButton' => $printCompareButton,
     'ajax' => $ajax,
     'buyButton' => $buyButton,
     'classForButton' => $classForButton,
     'titleBtn' => $titleBtn,
     'id' => $id,
     'blockVariants' => $blockVariants,
     'addHtml' => $addHtml,
     'price' => ($productData ? $productData['price_course']: ''),
     'old_price' => ($productData ? $productData['old_price'] : ''),
     'activity' => $productData['activity'],
	 'parentData' => $param,
	 'htmlProperty' => $htmlProperty,
    );
    
    $htmlForm .= MG::layoutManager('layout_property', $data);
    $htmlForm .= '</form>';
    $result = array(
        'html' => $htmlForm,    
        'marginPrice' => $marginPrice * $currencyRate, 
        'defaultSet' => $defaultSet,  // набор характеристик, которые были бы выбраны по умолчанию при открытии карточки товара.
        'propertyNodummy' => $secctionCartNoDummy, 
        'stringsProperties' => $stringsProperties
        );
    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }

  /**
   * Формирует блок вариантов товара.
   * @param int $id id товара
   * @return string
   */
  public function getBlockVariants($id) {
    $arr = $this->getVariants($id, false, true);    
    foreach ($arr as &$var){
      $var['price'] = MG::priceCourse($var['price_course']);
    }
    $html = MG::layoutManager('layout_variant', array('blockVariants'=>$arr, 'type'=>'product'));
    return $html;
  }

  /**
   * Формирует массив блоков вариантов товаров на странице каталога.
   * Метод создан для сокращения количества запросов к БД.
   * @param $array  - массив id товаров
   * @param $returnArray  - если true то вернет просто массив без html блоков
   * @return  string
   */
  public function getBlocksVariantsToCatalog($array, $returnArray = false, $mgadmin = false) {
    if (!empty($array)) {
      $in = implode(',', $array);
    }
    $orderBy = 'ORDER BY sort, id';
    $where = '';
    if(MG::getSetting('filterSortVariant') && !$mgadmin){
      $parts = explode('|',MG::getSetting('filterSortVariant'));
      $parts[0] = $parts[0] == 'count' ? 'count_sort' : $parts[0];
      $orderBy = ' ORDER BY `'.DB::quote($parts[0],1).'` '.DB::quote($parts[1],1);      
    }
    if(MG::getSetting('showVariantNull')=='false' && !$mgadmin){
      $orderBy = ' AND  (pv.`count` != 0 OR pv.`count` IS NULL) '.$orderBy; 
    }
    // Получаем все варианты для передранного массива продуктов.
    if ($in) {
      $res = DB::query('
       SELECT  pv.*, c.rate,(pv.price_course + pv.price_course * (IFNULL(c.rate,0))) as `price_course`,
       IF( pv.count<0,  1000000, pv.count ) AS  `count_sort` 
       FROM `'.PREFIX.'product_variant` pv    
         LEFT JOIN `'.PREFIX.'product` as p ON 
           p.id = pv.product_id
         LEFT JOIN `'.PREFIX.'category` as c ON 
           c.id = p.cat_id  
       WHERE pv.product_id  in ('.$in.')
       '.$orderBy);

      if (!empty($res)) {
        while ($variant = DB::fetchAssoc($res)) {      
          if (!$returnArray) {
            $variant['price'] = MG::priceCourse($variant['price_course']);
          }
          $results[$variant['product_id']][] = $variant;
        }
      }
    }

    if ($returnArray) {
      return $results;
    }

    if (!empty($results)) {
      // Для каждого продукта создаем HTML верстку вариантов.
      foreach ($results as &$blockVariants) {       
        $html = MG::layoutManager('layout_variant', array('blockVariants'=>$blockVariants, 'type'=>'catalog'));
        $blockVariants = $html;
      }
    }
    
    return $results;
  }

  /**
   * Формирует добавочную строку к названию характеристики,
   * в зависимости от наличия наценки и стоимости. 
   * @param int $valueArr массив с наценкой
   * @return $array массив с разделенными данными, название пункта и стоимость.
   */
  public function addMarginToProp($margin, $rate = 1, $currency = false) {
    $currency = $currency ? $currency : MG::getSetting('currencyShopIso');
    $symbol = '+';
    if (!empty($margin)) {
      if ($margin < 0) {
        $symbol = '-';
        $margin = $margin * -1;
      }
    }
    return (!empty($margin)) ? ' '.$symbol.' '.MG::numberFormat($margin * $rate).' '.MG::getSetting('currency') : '';
  }

  /**
   * Отделяет название характеристики от цены название_пункта#стоимость#.
   * Пример входящей строки: "Красный#300#"
   * @param string $value строка, которую надо распарсить
   * @return  $array массив с разделенными данными, название пункта и стоимость.
   */
  public function parseMarginToProp($value) {
    $array = array();
    $pattern = "/^(.*)#([\d\.\,-]*)#$/";
    preg_match($pattern, $value, $matches);
    if (isset($matches[1]) && isset($matches[2])) {
      $array = array('name' => $matches[1], 'margin' => $matches[2]);
    }
    return $array;
  }

  /**
   * Обновление состояния корзины.
   */
  public function calcPrice() {
    $product = $this->getProduct($_POST['inCartProductId']);
    $currencyRate = MG::getSetting('currencyRate');      
    $currencyShopIso = MG::getSetting('currencyShopIso');     
    if (isset($_POST['variant'])) {
      $variants = $this->getVariants($_POST['inCartProductId']);
            
      $variant = $variants[$_POST['variant']];
      $product['price'] = $variant['price'];           
      $product['code'] = $variant['code'];
      $product['count'] = $variant['count'];
      $product['old_price'] = $variant['old_price'];
      $product['weight'] = $variant['weight'];
      $product['price_course'] = $variant['price_course'];   
    }

    $cart = new Models_Cart;
    $property = $cart->createProperty($_POST);
    $product['currency_iso'] = $product['currency_iso']?$product['currency_iso']:$currencyShopIso;
    $product['price'] = $product['price_course']; 
    $product['price'] = SmalCart::plusPropertyMargin($product['price'], $property['propertyReal'], $currencyRate[$product['currency_iso']]);
    $product['real_price'] = $product['price'];
    
    $product['old_price'] *= $currencyRate[$product['currency_iso']];
    $product['remInfo'] = !empty($_POST['remInfo']) ? $_POST['remInfo'] : '';
    $response = array(
      'status' => 'success',
      'data' => array(
        'title' => $product['title'],
        'price' => MG::numberFormat($product['price']).' '.MG::getSetting('currency'),
        'old_price' => MG::numberFormat($product['old_price']).' '.MG::getSetting('currency'),
        'code' => $product['code'],
        'count' => $product['count'],
        'price_wc' => $product['price'],
        'real_price' => $product['real_price'],
        'weight' => $product['weight'],
        'count_layout'=> MG::layoutManager('layout_count_product', $product)
      )
    );

    echo json_encode($response);
    exit;
  }

  /**
   * Возвращает набор вариантов товара.
   * 
   * @param int $id id продукта для поиска его вариантов
   * @return  $array массив с параметрами варианта.
   */
  public function getVariants($id, $title_variants = false, $sort = false) {
    $results = array();
    $orderBy = 'ORDER BY sort';
    if(MG::getSetting('filterSortVariant')&& $sort){
      $parts = explode('|',MG::getSetting('filterSortVariant'));
      $parts[0] = $parts[0] == 'count' ? 'count_sort' : $parts[0];
      $orderBy = ' ORDER BY `'.DB::quote($parts[0],1).'` '.DB::quote($parts[1],1);      
    }
    if(MG::getSetting('showVariantNull')=='false' && $sort){
      $orderBy = ' AND pv.`count` != 0 '.$orderBy; 
    }
    if (!$title_variants) {
      $res = DB::query('
      SELECT  pv.*, c.rate,(pv.price_course + pv.price_course *(IFNULL(c.rate,0))) as `price_course`,
      p.currency_iso, IF( pv.count<0,  1000000, pv.count ) AS  `count_sort` 
      FROM `'.PREFIX.'product_variant` pv   
        LEFT JOIN `'.PREFIX.'product` as p ON 
          p.id = pv.product_id
        LEFT JOIN `'.PREFIX.'category` as c ON 
          c.id = p.cat_id       
      WHERE pv.product_id = '.DB::quote($id).' '.$orderBy
      );     
      
    } else {    
      $res = DB::query('
        SELECT  pv.*
        FROM `'.PREFIX.'product_variant` pv    
        WHERE pv.product_id = '.DB::quote($id).'  and pv.title_variant = '.DB::quote($title_variants).' '.$orderBy
      );
    }

    if (!empty($res)) {  
      while ($variant = DB::fetchAssoc($res)) {
        $results[$variant['id']] = $variant;
      }
    }
    return $results;
  }

  /**
   * Возвращает массив id характеристик товара, которые ненужно выводить в карточке.
   * @return  $array - массив с id.
   */
  public function noPrintProperty() {
    $results = array();
   
    $res = DB::query('
      SELECT  `id`
      FROM `'.PREFIX.'property`     
      WHERE `activity` = 0');
    
    while ($row = DB::fetchAssoc($res)) {
      $results[] = $row['id'];
    }
 
    return $results;
  }
  
  /**
   * Возвращает HTML блок связанных товаров.
   * @param type $args
   * @return type
   */
  public function createRelatedForm($args,$title='С этим товаром покупают', $layout = 'layout_related') {
    if($args){     
      $data['title'] = $title;
      
      $stringRelated = ' null';
      $sortRelated = array();
      foreach (explode(',',$args['product']) as $item) {
        $stringRelated .= ','.DB::quote($item);
        $sortRelated[$item] = $item;
      }
      $stringRelated = substr($stringRelated, 1);
      
      $data['products'] = $this->getProductByUserFilter(' p.code IN ('.$stringRelated.') and p.activity = 1 ');
      
      $datarelatedCat = array();
      if (!empty($args['category'])) {
        $stringRelatedCat = ' null';
        foreach (explode(',',$args['category']) as $item) {
          $stringRelatedCat .= ','.DB::quote($item);
        }
        $stringRelatedCat = substr($stringRelatedCat, 1);
        $relatedCat = $this->getProductByUserFilter(' p.`cat_id` IN ('.$stringRelatedCat.') and p.activity = 1 ');
        shuffle($relatedCat);        
      }
      if (!empty($relatedCat)) {
        foreach ($relatedCat as $key => $prod) {
          if ($key > 10) {
            break;
          }
          $data['products'][] = $prod;
          $sortRelated[$prod['code']] = $prod;
        }
      }
      if(!empty($data['products'])){
        $data['currency'] = MG::getSetting('currency');
        foreach ($data['products'] as $item) {            
          $img = explode('|',$item['image_url']);
          $item['img'] = $img[0];
          $item['category_url'] = (MG::getSetting('shortLink') == 'true' ? '' : $item['category_url'].'/');
          $item['category_url'] = ($item['category_url'] == '/' ? 'catalog/' : $item['category_url']);
          $item['url'] = (MG::getSetting('shortLink') == 'true' ? SITE .'/'.$item["product_url"] : SITE .'/'.(isset($item["category_url"])&&$item["category_url"]!='' ? $item["category_url"] : 'catalog/').$item["product_url"]);
          $item['price'] = MG::priceCourse($item['price_course']);
          $sortRelated[$item['code']] = $item;
        }
        $data['products'] = array();
        //сортируем связанные товары в том порядке, в котором они идут в строке артикулов
        foreach ($sortRelated as $item) {
	    if(!empty($item['id']) && is_array($item)){
            $data['products'][$item['id']] = $item;
	  }
        }      
   
        $result = MG::layoutManager($layout, $data);      
      }
      
    };
    
    $args = func_get_args();
    return MG::createHook(__CLASS__."_".__FUNCTION__, $result, $args);
  }
  
  
  /**
   * Конвертирование стоимости товаров по заданному курсу.
   * @return string $iso - валюта в которую будет производиться конвертация.
   * @return array $productsId - массив с id продуктов.
   */
  public function convertToIso($iso,$productsId=array()) {
    
    $productsId = implode(',', $productsId);
    if(empty($productsId)){$productsId = 0;};
    
    // вычислим соотношение валют имеющихся в базе товаров к выбранной для замены
    // вычисление производится на основе имеющихся данных по отношению в  валюте магазина
    $currencyShort = MG::getSetting('currencyShort');     
    $currencyRate = MG::getSetting('currencyRate');
    $currencyShopIso = MG::getSetting('currencyShopIso'); 
       
    // если есть непривязанные к валютам товары, то  назначаем им текущую валюту магазина
    DB::query('
      UPDATE `'.PREFIX.'product` SET 
            `currency_iso` = '.DB::quote($currencyShopIso).'
      WHERE `currency_iso` =  "" AND `id` IN ('.DB::quote($productsId, true).')');
    DB::query('
      UPDATE `'.PREFIX.'product_variant` SET 
            `currency_iso` = '.DB::quote($currencyShopIso).'
      WHERE `currency_iso` =  "" AND `id` IN ('.DB::quote($productsId, true).')');

    // запоминаем базовое соотношение курсов к валюте магазина
    $rateBaseArray = $currencyRate;  
    $rateBase = $currencyRate[$iso];  
    // создаем новое соотношение валют по отношению в выбранной для конвертации
    foreach ($currencyRate as $key => $value) {     
        if(!empty($rateBase)){    
          $currencyRate[$key] = $value / $rateBase;                 
        }        
    }
    $currencyRate[$iso] = 1;
  
    // пересчитываем цену, старую цену и цену по курсу для выбранных товаров
    foreach ($currencyRate as $key => $rate) { 
      DB::query('
      UPDATE `'.PREFIX.'product`
      SET `price`= ROUND(`price`*'.DB::quote($rate,TRUE).',2),
          `old_price`= ROUND(`old_price`*'.DB::quote($rate,TRUE).',2),
          `price_course`= ROUND(`price`*'.DB::quote(($rateBaseArray[$iso]?$rateBaseArray[$iso]:1),TRUE).',2)
      WHERE currency_iso = '.DB::quote($key).' AND `id` IN ('.DB::quote($productsId, true).')');
      
      // также и в вариантах
      DB::query('
      UPDATE `'.PREFIX.'product_variant`
       SET `price`= ROUND(`price`*'.DB::quote($rate,TRUE).',2),
          `old_price`= ROUND(`old_price`*'.DB::quote($rate,TRUE).',2),
          `price_course`= ROUND(`price`*'.DB::quote(($rateBaseArray[$iso]?$rateBaseArray[$iso]:1),TRUE).',2)
      WHERE currency_iso = '.DB::quote($key).' AND `product_id` IN ('.DB::quote($productsId, true).')');
    }
    
    // всем выбранным продуктам изменяем ISO
     DB::query('
      UPDATE `'.PREFIX.'product`
      SET `currency_iso` = '.DB::quote($iso).'
      WHERE `id` IN ('.DB::quote($productsId, true).')');
     
     DB::query('
      UPDATE `'.PREFIX.'product_variant`
      SET `currency_iso` = '.DB::quote($iso).'
      WHERE `product_id` IN ('.DB::quote($productsId, true).')');

  }

   /**
   * Обновления цены выдранных товаров в соответствии с курсом валюты.
   */
  public function updatePriceCourse($iso,$listId = array()) {
    
     if(empty($listId)){$listId = 0;}
     else{
     $listId = implode(',', $listId);     
     }
    
    // вычислим соотношение валют имеющихся в базе товаров к выбранной для замены
    // вычисление производится на основе имеющихся данных по отношению в  валюте магазина
    $currencyShort = MG::getSetting('currencyShort');     
    $currencyRate = MG::getSetting('currencyRate');
    $currencyShopIso = MG::getSetting('currencyShopIso');     
 
    $where = '';
    if(!empty($listId)){
      $where =' AND `id` IN ('.DB::quote($listId, true).')';
    }
    
    $whereVariant = '';
    if(!empty($listId)){
      $whereVariant =' AND `product_id` IN ('.DB::quote($listId, true).')';
    }
    
    DB::query('
     UPDATE `'.PREFIX.'product` SET 
           `currency_iso` = '.DB::quote($currencyShopIso).'
     WHERE `currency_iso` = "" '.$where);
  
    
    $rate = $currencyRate[$iso];  
    foreach ($currencyRate as $key => $value) {     
        if(!empty($rate)){
          $currencyRate[$key] = $value / $rate;                 
        }        
    }
    $currencyRate[$iso] = 1;

    foreach ($currencyRate as $key => $rate) {
   
      DB::query('
      UPDATE `'.PREFIX.'product` 
        SET `price_course`= ROUND(`price`*'.DB::quote((float)$rate,TRUE).',2)          
      WHERE currency_iso = '.DB::quote($key).' '.$where);
     
      DB::query('
      UPDATE `'.PREFIX.'product_variant` 
        SET `price_course`= ROUND(`price`*'.DB::quote((float)$rate,TRUE).',2)         
      WHERE currency_iso = '.DB::quote($key).' '.$whereVariant);
    }
    
  }
  
   /**
   * Удаляет картинки вариантов товара.
   */
  public function deleteImagesVariant($productId) { 
    $imagesArray = array();
    // Удаляем продукт из базы.
    $res = DB::query('
      SELECT image
      FROM `'.PREFIX.'product_variant` 
      WHERE product_id = '.DB::quote($productId) );
    while($row = DB::fetchAssoc($res)){
      $imagesArray[] = $row['image'];
    }    
    $this->deleteImagesProduct($imagesArray, $productId); 
    return true;
  }
  
  /**
   * Подготавливает названия изображений товара.
   */
  public function prepareImageName($product){   
    $result = $product;
    
    $images = explode("|", $result['image_url']);
    foreach($images as $cell=>$image){      
      $pos = strpos($image, 'no-img');
      if($pos || $pos === 0){
        unset($images[$cell]);        
      }else{
        $images[$cell] = basename($image);
      }      
    }
    $result['image_url'] = implode('|', $images);
    
    foreach($result['variants'] as $cell=>$variant){
      if(empty($variant['image'])){
        continue;
      }
      
      $pos = strpos($variant['image'], 'no-img');
      if($pos || $pos === 0){
        unset($result['variants'][$cell]['image']);
      }else{
        $result['variants'][$cell]['image'] = str_replace(array('30_', '70_'), '', basename($variant['image']));      
        $images[] = $result['variants'][$cell]['image'];
      }
    }
    
    return $result;
  }
  
  /**
   * Копирует изображения товара в новую структуру хранения.
   * 
   * @param array $images - массив изображений
   * @param int $productId - id товара
   * @param string $path - папка в которой лежат исходные изображения
   * @param bool $removeOld - флаг удаления изображений из папки $path после копирования в новое место
   * @return void
   */
  public function movingProductImage($images, $productId, $path='uploads', $removeOld = true){
    if(empty($images)){
      return false;
    }
    
    $ds = DIRECTORY_SEPARATOR;
    $dir = floor($productId/100).'00';
    $curdir = getcwd();
    
    if(!file_exists('uploads'.$ds.'product'.$ds.$dir.$ds.$productId.$ds.'thumbs')){
      
      if(!file_exists('uploads'.$ds.'product'.$ds.$dir.$ds.$productId)){

        if(!file_exists('uploads'.$ds.'product'.$ds.$dir)){

          if(!file_exists('uploads'.$ds.'product')){
            if(chdir('uploads'.$ds)){
              mkdir('product', 0755);
              chdir($curdir);
            }             
          }

          if(chdir('uploads'.$ds.'product'.$ds)){
            mkdir($dir, 0755);
            chdir($curdir);
          }           
        }

        if(chdir('uploads'.$ds.'product'.$ds.$dir.$ds)){
          mkdir($productId, 0755);
          chdir($curdir);
        }        
      }
      
      if(chdir('uploads'.$ds.'product'.$ds.$dir.$ds.$productId.$ds)){
        mkdir('thumbs', 0755);
        chdir($curdir);
      }      
    }
    
    foreach($images as $cell=>$image){
      if(copy($path.$ds.$image, 'uploads'.$ds.'product'.$ds.$dir.$ds.$productId.$ds.$image)){
        
        if(copy($path.$ds.'thumbs'.$ds.'30_'.$image, 'uploads'.$ds.'product'.$ds.$dir.$ds.$productId.$ds.'thumbs'.$ds.'30_'.$image) && $removeOld){
          unlink($path.$ds.'thumbs'.$ds.'30_'.$image);
        }
        
        if(copy($path.$ds.'thumbs'.$ds.'70_'.$image, 'uploads'.$ds.'product'.$ds.$dir.$ds.$productId.$ds.'thumbs'.$ds.'70_'.$image) && $removeOld){
          unlink($path.$ds.'thumbs'.$ds.'70_'.$image);
        }
        
        if($removeOld){
          unlink($path.$ds.$image);
        }
      }elseif(copy('uploads'.$ds.$image, 'uploads'.$ds.'product'.$ds.$dir.$ds.$productId.$ds.$image)){
        
        if(copy('uploads'.$ds.'thumbs'.$ds.'30_'.$image, 'uploads'.$ds.'product'.$ds.$dir.$ds.$productId.$ds.'thumbs'.$ds.'30_'.$image) && $removeOld){
          unlink('uploads'.$ds.'thumbs'.$ds.'30_'.$image);
        }
        
        if(copy('uploads'.$ds.'thumbs'.$ds.'70_'.$image, 'uploads'.$ds.'product'.$ds.$dir.$ds.$productId.$ds.'thumbs'.$ds.'70_'.$image) && $removeOld){
          unlink('uploads'.$ds.'thumbs'.$ds.'70_'.$image);
        }
        
        if($removeOld){
          unlink('uploads'.$ds.$image);
        }
      }
    }
  }
  
}