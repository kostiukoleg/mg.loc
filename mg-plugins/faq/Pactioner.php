<?php

/**
 * Класс Pactioner наследник стандарного Actioner
 * Предназначен для выполнения действий,  AJAX запросов плагина 
 *
 * @author Avdeev Mark <mark-avdeev@mail.ru>
 */
class Pactioner extends Actioner {

  private $pluginName = 'faq';

  /**
   * Добавление сущности в таблицу БД
   * @param type $array - массив полей и значений
   * @return array возвращает входящий массив
   */
  public function addEntity($array) {
    //доступно только модераторам и админам.
    USER::AccessOnly('1,4', 'exit()');
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
    //доступно только модераторам и админам.
    USER::AccessOnly('1,4', 'exit()');
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
    //доступно только модераторам и админам.
    USER::AccessOnly('1,4', 'exit()');
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
   * Устанавливает количество отображаемых записей в разделе вопросов-ответов
   * @return boolean
   */
  public function setCountPrintRowsQuest() {
    //доступно только модераторам и админам.
    USER::AccessOnly('1,4', 'exit()');
    $count = 20;
    if (is_numeric($_POST['count']) && !empty($_POST['count'])) {
      $count = $_POST['count'];
    }
    MG::setOption(array('option' => 'countPrintRowsQuest', 'value' => $count));
    return true;
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

    unset($_POST['pluginHandler']);

    if (!empty($_POST['id'])) {  // если передан ID, то обновляем
      if (DB::query('
        UPDATE `'.PREFIX.$this->pluginName.'`
        SET '.DB::buildPartQuery($_POST).'
        WHERE id ='.DB::quote($_POST['id']))) {
        $this->data['row'] = $_POST; 
        } 
      else {
        return false;
      }
    } else {
      // если  не передан ID, то создаем
      if (DB::buildQuery('INSERT INTO `'.PREFIX.$this->pluginName.'` SET ', $_POST)) {
        $_POST['id'] = DB::insertId();
        $this->updateEntity(array('id' => $_POST['id'], 'sort' => $_POST['id']));
        $this->data['row'] = $_POST;
      } else {
        return false;
      }
    }
    return true;
  }

}
