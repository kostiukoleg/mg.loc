<?php

/**
 * Класс Pactioner наследник стандарного Actioner
 * Предназначен для выполнения действий,  AJAX запросов плагина 
 *
 * @author Avdeev Mark <mark-avdeev@mail.ru>
 */
class Pactioner extends Actioner {

  private $pluginName = 'brand';

  /**
   * Сохраняет  опции плагина
   * @return boolean
   */
  public function saveBaseOption() {
    USER::AccessOnly('1,4', 'exit()');
    if (!empty($_POST['data'])) {
      MG::setOption(array('option' => 'brand', 'value' => addslashes(serialize($_POST['data']))));
    }
    return true;
  }

  /**
   * Получает список характеристик
   * @return array 
   */
  public function getAllCharact() {
    USER::AccessOnly('1,4', 'exit()');
    $property = array();
    $res = DB::query('SELECT `id`, `name` FROM `'.PREFIX.'property` WHERE `id` <> '.DB::quote($_POST['id']));
    while ($row = DB::fetchArray($res)) {
      $property[] = $row;
    }
    $this->data = $property;
    return true;
  }

  /**
   * Получает список характеристик
   * @return array 
   */
  public function copyProperty() {
    $this->messageSucces = $this->lang['EXPORT_DONE'];
    $this->messageError = $this->lang['EXPORT_NOT_DONE'];
    USER::AccessOnly('1,4', 'exit()');
    // получаем настройки характеристики 
    $res = DB::query('SELECT * FROM `'.PREFIX.'property` WHERE `id` = '.DB::quote($_POST['from']));
    if ($row = DB::fetchArray($res)) {
      $property = $row;
    }
    if ($property) {
      
      // получаем значения характеристики 
      $data = $property['data'];
      $brand = array();
      if (!$data) {
        $res = DB::query('SELECT DISTINCT `value` FROM `'.PREFIX.'product_user_property` WHERE `property_id`='.$_POST['from']);
        while ($row = DB::fetchArray($res)) {
          $data .= $row['value'].'|';
          $brand[] = $row['value'];
        }
        $data = substr($data, 0, strlen($data) - 1);
      } else {
        $brand = explode('|', $data);
      }
      // обновляем созданную характеристику и деактивируем старую
      DB::query(
        "UPDATE `".PREFIX."property` 
          SET `data`=".DB::quote($data).",
          `all_category`=".DB::quote($property['all_category']).",`sort`=".DB::quote($property['sort']).", `description`=".DB::quote($property['description']).",
          `1c_id`=".DB::quote($property['1c_id'])." WHERE `id` = ".DB::quote($_POST['to']));
      DB::query('UPDATE `'.PREFIX.'property` SET `activity`=0,`filter`=0 WHERE `id`='.DB::quote($_POST['from']));
      // присваиваем категориям и товарам новую характеристику, вместо старой, предварительно удаляем из категорий созданную 
      DB::query('
        DELETE FROM `'.PREFIX.'category_user_property`
        WHERE property_id = '.DB::quote($_POST['to']));
      $res = DB::query('SELECT * FROM `'.PREFIX.'category_user_property` WHERE property_id = '.DB::quote($_POST['from']));
      while ($row = DB::fetchArray($res)) {
        DB::query("
            INSERT IGNORE INTO `".PREFIX."category_user_property`
            VALUES (".DB::quote($row['category_id']).", ".DB::quote($_POST['to']).")");
      }
      // копируем свойство продуктов старой характеристики для всех товаров 
      DB::query(
        "INSERT INTO  `".PREFIX."product_user_property` (`product_id`, `property_id`, `value`, `product_margin`, `type_view` ) 
        SELECT  `product_id` , ".DB::quote($_POST['to']).",  `value` ,  `product_margin` ,  `type_view` 
        FROM  `".PREFIX."product_user_property` 
        WHERE  `property_id` =".DB::quote($_POST['from']));
      if (count($brand)) {
        foreach ($brand as $value) {
          if ($value != '') {
            DB::query('INSERT INTO `'.PREFIX.'brand-logo` (`brand`) VALUES ('.DB::quote($value).')');
            $id = DB::insertId();
            DB::query('UPDATE `'.PREFIX.'brand-logo` SET `sort`='.DB::quote($id).' WHERE `id` ='.DB::quote($id));
          }
        }
      }
      // обновление настроек
      $array = Array(
        'propertyId' => $_POST['to'],
        'first' => 'false',
      );
      MG::setOption(array('option' => 'brand', 'value' => addslashes(serialize($array))));
      return true;
    }
    return false;
  }

  /**
   * Удаление сущности, пока только из бд, не из продуктов
   * @return boolean
   */
  public function deleteBrand() {
    //доступно только модераторам и админам.
    USER::AccessOnly('1,4', 'exit()');
    $this->messageSucces = $this->lang['ENTITY_DEL'];
    $this->messageError = $this->lang['ENTITY_DEL_NOT'];
    $option = MG::getSetting('brand');
    $option = stripslashes($option);
    $options = unserialize($option);
    $brand = $_POST['brand'];
    if (DB::query('DELETE FROM `'.PREFIX.$this->pluginName.'-logo` WHERE `id`= '.DB::quote($_POST['id']))) {
      $prop = DB::query('SELECT `data` FROM `'.PREFIX.'property` WHERE `id`='.DB::quote($options['propertyId']));
      if ($res = DB::fetchArray($prop)) {
        $count = substr_count($res['data'], $brand.'|');
        if ($count > 0) {
          $newData = str_replace($brand.'|', '', $res['data']);
        } else {
          $newData = str_replace('|'.$brand, '', $res['data']);
        }
        DB::query('UPDATE `'.PREFIX.'property` SET `data`='.DB::quote($newData).' WHERE `id`='.DB::quote($options['propertyId']));
      }
      return true;
    }
    return false;
  }

  /**
   * Получает сущность 
   * @return boolean
   */
  public function getEntity() {
    USER::AccessOnly('1,4', 'exit()');
    $res = DB::query('
      SELECT * 
      FROM `'.PREFIX.$this->pluginName.'-logo`
      WHERE `id` = '.DB::quote($_POST['id']));
    if ($row = DB::fetchAssoc($res)) {
      $this->data = $row;
      return true;
    }
    return false;
  }

  /**
   * Сохраняет и обновляет параметры записи.
   * @return type
   */
  public function saveEntity() {
    //доступно только модераторам и админам.
    USER::AccessOnly('1,4', 'exit()');

    $this->messageSucces = $this->lang['ENTITY_SAVE'];
    $this->messageError = $this->lang['ENTITY_SAVE_NOT'];
    unset($_POST['mguniqueurl']);
    unset($_POST['pluginHandler']);

    if (!empty($_POST['id'])) {  // если передан ID, то обновляем
      if (DB::query('
        UPDATE `'.PREFIX.$this->pluginName.'-logo`
        SET '.DB::buildPartQuery($_POST).'
        WHERE id ='.DB::quote($_POST['id']))) {
        $this->data['row'] = $_POST;
      } else {
        return false;
      }
    } else {
      $option = MG::getSetting('brand');
      $option = stripslashes($option);
      $options = unserialize($option);
      // если  не передан ID, то создаем
      if (DB::buildQuery('INSERT INTO `'.PREFIX.$this->pluginName.'-logo` SET ', $_POST)) {
        $_POST['id'] = DB::insertId();
        DB::query('
        UPDATE `'.PREFIX.$this->pluginName.'-logo`
        SET `sort`='.DB::quote($_POST['id']).'
        WHERE id ='.DB::quote($_POST['id']));
        $this->data['row'] = $_POST;
        // добавляем новый пункт в характеристику
        $prop = DB::query('SELECT `data` FROM `'.PREFIX.'property` WHERE `id`='.DB::quote($options['propertyId']));
        if ($res = DB::fetchArray($prop)) {
          $data = $res['data'].'|'.$_POST['brand'];
          DB::query('UPDATE `'.PREFIX.'property` SET `data`='.DB::quote($data).' WHERE `id`='.DB::quote($options['propertyId']));
        }
      } else {
        return false;
      }
    }
    return true;
  }
    /**
   * Копирует новые значения строковой характеристики 
   * @return array 
   */
  public function copyNewProperty() {
    $this->messageSucces = $this->lang['EXPORT_DONE'];
    $this->messageError = $this->lang['EXPORT_NOT_DONE'];
    USER::AccessOnly('1,4', 'exit()');
    // получаем настройки характеристики 
    $res = DB::query('SELECT `category_id` 
        FROM  `'.PREFIX.'category_user_property` 
        WHERE  `property_id` =  '.DB::quote($_POST['from']));
    while ($row = DB::fetchArray($res)) {
      $cat[] = $row['category_id'];
    }
    $res = DB::query('SELECT `category_id` 
      FROM  `'.PREFIX.'category_user_property` 
      WHERE  `property_id` =  '.DB::quote($_POST['to']));
    while ($row = DB::fetchArray($res)) {
      $catBrand[] = $row['category_id'];
    }
    $newCat = array_diff($cat, $catBrand);
    if (!empty($newCat)) {
      foreach ($newCat as $cat_id) {
      $res = DB::query('INSERT INTO `'.PREFIX.'category_user_property`
        (`category_id`, `property_id`) VALUES ('.DB::quote($cat_id).','.DB::quote($_POST['to']).')');     
      } 
    }
      // деактивируем старую
    DB::query('UPDATE `'.PREFIX.'property` SET `activity`=0,`filter`=0 WHERE `id`='.DB::quote($_POST['from']));
    // в переменную сохраняем все существующие значения из характеристики бренд
    $res = DB::query('SELECT `data` FROM `'.PREFIX.'property` WHERE `id` = '.DB::quote($_POST['to']));  
    $brands = '';
    if ($row = DB::fetchArray($res)) {
      $brands = $row['data'];
    }
    // выбираем новые значения, которых еще нет в характеристике
    $res = DB::query('SELECT  DISTINCT `value` FROM `'.PREFIX.'product_user_property` WHERE `property_id` = '.DB::quote($_POST['from']));  
    while ($row = DB::fetchArray($res)) {
      if (stristr($brands, $row['value']) === FALSE && $row['value']!='') {
        $brands.= ($brands ? '|' : '').$row['value'];        
        DB::query('INSERT INTO `'.PREFIX.'brand-logo` (`brand`) VALUES ('.DB::quote($row['value']).')');
        $id = DB::insertId();
        DB::query('UPDATE `'.PREFIX.'brand-logo` SET `sort`='.DB::quote($id).' WHERE `id` ='.DB::quote($id));
      }
    }
    // получаем переменную со всеми значениями характеристики с разделителем |
    DB::query('UPDATE `'.PREFIX.'property` SET `data`='.DB::quote($brands).' WHERE `id`='.DB::quote($_POST['to']));  
    $res = DB::query('SELECT `value`, `product_id`, `type_view` FROM `'.PREFIX.'product_user_property` WHERE `property_id` = '.DB::quote($_POST['from']));
    
      while ($row = DB::fetchArray($res)) {
        $result = DB::query('SELECT `product_id` FROM `'.PREFIX.'product_user_property`
           WHERE `property_id` = '.DB::quote($_POST['to']).' AND `product_id`='.DB::quote($row['product_id']));
        if (!($resultLast = DB::fetchArray($result))) {
          DB::query("
            INSERT IGNORE INTO `".PREFIX."product_user_property`
            (`product_id`, `property_id`, `value`, `type_view`) VALUES 
            (".DB::quote($row['product_id']).",".DB::quote($_POST['to']).",".DB::quote($row['value']).",".DB::quote($row['type_view']).")");
      } 
      }
      // обновление настроек
      $array = Array(
        'propertyId' => $_POST['to'],
        'first' => 'false',
      );
      MG::setOption(array('option' => 'brand', 'value' => addslashes(serialize($array))));
      return true;
    
  }
  /**
   * Устанавливает количество отображаемых записей в разделе логотипов
   * @return boolean
   */
  public function countPrintRowsEntity() {
    //доступно только модераторам и админам.
    USER::AccessOnly('1,4', 'exit()');
    $count = 20;
    if (is_numeric($_POST['count']) && !empty($_POST['count'])) {
      $count = $_POST['count'];
    }
    MG::setOption(array('option' => 'countPrintRowsBrand', 'value' => $count));
    return true;
  }

}
