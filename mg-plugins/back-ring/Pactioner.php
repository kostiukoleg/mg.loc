<?php

/**
 * Класс Pactioner наследник стандарного Actioner
 * Предназначен для выполнения действий,  AJAX запросов плагина 
 *
 * @author Avdeev Mark <mark-avdeev@mail.ru>
 */
class Pactioner extends Actioner {

  private $pluginName = 'back-ring';

  /**
   * Добавление сущности в таблицу БД
   * @param type $array - массив полей и значений
   * @return array возвращает входящий массив
   */
  public function addEntity($array) {
    unset($array['id']);
    $result = array();
    DB::buildQuery('INSERT INTO `'.PREFIX.$this->pluginName.'` SET ', $array);
    return $result;
  }

  /**
   * Обновление сущности в таблице БД
   * @param type $array - массив полей и значений
   * @return array возвращает входящий массив
   */
  public function updateEntity($array) {
    $id = $array['id'];
    $result = false;
    if (!empty($id)) {
      if (DB::query('
        UPDATE `'.PREFIX.$this->pluginName.'`
        SET '.DB::buildPartQuery($array).'
        WHERE id = %d
      ', $id)) {
        $result = true;
      }
    } else {
      $result = $this->addEntity($array);
    }
    return $result;
  }

  /**
   * Удаление сущности
   * @return boolean
   */
  public function deleteEntity() {
    $this->messageSucces = $this->lang['ENTITY_DEL'];
    $this->messageError = $this->lang['ENTITY_DEL_NOT'];
    if (DB::query('DELETE FROM `'.PREFIX.$this->pluginName.'` WHERE `id`= '.$_POST['id'])) {
      return true;
    }
    return false;
  }

  /**
   * Удаление получает сущность
   * @return boolean
   */
  public function getEntity() {
    $res = DB::query('
      SELECT * 
      FROM `'.PREFIX.$this->pluginName.'`
      WHERE `id` = '.$_POST['id']);

    if ($row = DB::fetchAssoc($res)) {
      $this->data = $row;
      return true;
    } else {
      return false;
    }

    return false;
  }

  /**
   * Сохраняет и обновляет параметры записи.
   * @return type
   */
  public function saveEntity() {

    $this->messageSucces = $this->lang['ENTITY_SAVE'];
    $this->messageError = $this->lang['ENTITY_SAVE_NOT'];

    unset($_POST['pluginHandler']);

    if (!empty($_POST['id'])) {  // если передан ID, то обновляем
      if (DB::query('
        UPDATE `'.PREFIX.$this->pluginName.'`
        SET '.DB::buildPartQuery($_POST).'
        WHERE id = %d
      ', $_POST['id'])) {
        $this->data['row'] = $_POST;    
      } else {
        return false;
      }
    } else {      
      // если  не передан ID, то создаем
      if (DB::buildQuery('INSERT INTO `'.PREFIX.$this->pluginName.'` SET ', $_POST)) {
        $_POST['id'] = DB::insertId();
        $this->updateEntity(array('id' => $_POST['id'], 'sort'=>$_POST['id'])); 
        $this->data['row'] = $_POST;   
      } else {
        return false;
      }
    }
    return true;
  }

  /**
   * Устанавливает флаг  активности  
   * @return type
   */
  public function visibleEntity() {
    $this->messageSucces = $this->lang['ACT_V_ENTITY'];
    $this->messageError = $this->lang['ACT_UNV_ENTITY'];

    //обновление
    if (!empty($_POST['id'])) {
      unset($_POST['pluginHandler']);
      $this->updateEntity($_POST);
    }

    if ($_POST['invisible']) {
      return true;
    }

    return false;
  }

  /**
   * Сохраняет  опции плагина
   * @return boolean
   */
  public function saveBaseOption() {
    $this->messageSucces = $this->lang['SAVE_BASE'];
    $this->messageError = $this->lang['NOT_SAVE_BASE'];
    if (!empty($_POST['data'])) {
      MG::setOption(array('option' => 'back-ringOption', 'value' => addslashes(serialize($_POST['data']))));
    }   
    return true;
  }

   /**
   * Сохраняет заявку на перезвон
   * @return boolean
   */
  public function sendOrderRing() {
    $this->messageSucces = $this->lang['SAVE_BASE'];
    $this->messageError = $this->lang['NOT_SAVE_BASE'];
    if (!empty($_POST['data'])) {
      
    }   
    return true;
  }
}