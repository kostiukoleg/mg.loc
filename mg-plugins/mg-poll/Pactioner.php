<?php

/**
 * Класс Pactioner наследник стандарного Actioner
 * Предназначен для выполнения действий,  AJAX запросов плагина 
 *
 * @author Avdeev Mark <mark-avdeev@mail.ru>
 */
class Pactioner extends Actioner {

  static $pluginName = 'mg-poll';

  /**
   * Сохраняет  опции плагина
   * @return boolean
   */
  public function saveBaseOption() {
    $this->messageSucces = $this->lang['SAVE_BASE'];
    $this->messageError = $this->lang['NOT_SAVE_BASE'];
    if (!empty($_POST['data'])) {
      MG::setOption(array('option' => self::$pluginName.'-option', 'value' => addslashes(serialize($_POST['data']))));
    }   
    return true;
  }

  public function saveEntity(){
    USER::AccessOnly('1,4','exit()');
    $this->messageSucces = $this->lang['ENTITY_SAVE'];
    $this->messageError = $this->lang['ENTITY_SAVE_NOT'];
    
    $arFields = $_POST;
    unset($arFields['pluginHandler']);

    if (!empty($arFields['id'])) {  // если передан ID, то обновляем
      $this->data = $this->updateEntity($arFields);
    } else {
      // если  не передан ID, то создаем
      $this->data = $this->addEntity($arFields);
    }
    return true;
  }
  
  private function addEntity($array){
    USER::AccessOnly('1,4','exit()');
    
    $answers = $array['new_answers'];
    unset($array['new_answers']);
    unset($array['id']);
    
    if(!empty($array['date_active_from'])){
      $array['date_active_from'] = $this->dateFormatToDB($array['date_active_from']);
    }else{
      $array['date_active_from'] = $this->dateFormatToDB(date('d.m.Y H:i'));
    }

//    if(!empty($array['date_active_to'])){
//      $array['date_active_to'] = $this->dateFormatToDB($array['date_active_to']);
//    }
    
    if(DB::buildQuery('INSERT INTO `'.PREFIX.'poll_question` SET ', $array)){
      $id = DB::insertId();
      
      if(!empty($answers)){
        $this->createAnswers($answers, $id);
      }
      
      $array['id'] = $id;
    }
    
    return $array;    
  }
  
  private function updateEntity($array){
    USER::AccessOnly('1,4','exit()');
    
    $id = $array['id'];
    $answers = $array['answers'];
    $newAnswers = $array['new_answers'];
    $delAnswers = $array['del_answers'];
    unset($array['answers']);
    unset($array['new_answers']);
    unset($array['del_answers']);
    
    if(!empty($array['date_active_from'])){
      $array['date_active_from'] = $this->dateFormatToDB($array['date_active_from']);
    }
//    else{
//      $array['date_active_from'] = '';
//    }
//
//    if(!empty($array['date_active_to'])){
//      $array['date_active_to'] = $this->dateFormatToDB($array['date_active_to']);
//    }else{
//      $array['date_active_to'] = '';
//    }
    
    if(DB::query('
      UPDATE `'.PREFIX.'poll_question`
      SET '.DB::buildPartQuery($array).'
      WHERE id = '.DB::quote($id))){
      
      if(!$this->updateAnswers($answers)){
        return false;
      }
    
      if(!empty($newAnswers)){
        $this->createAnswers($newAnswers, $id);
      }
      
      if(!empty($delAnswers)){
        $this->deleteAnswers($delAnswers, $id);
      }
    }
    
    return $array;
  }
  
  private function deleteAnswers($arAnswerIds, $questionId){
    USER::AccessOnly('1,4','exit()');
    
    if(count($arAnswerIds) > 1){
      $answerIds = implode(',', $arAnswerIds);
    }else{
      $answerIds = $arAnswerIds[0];
    }
    
    $delSql = '
      DELETE FROM `'.PREFIX.'poll_answer` 
      WHERE `question_id` = '.DB::quote($questionId, true).'
        AND `id` IN ('.$answerIds.')';
    
    DB::query($delSql);
  }
  
  private function createAnswers($arAnswers, $questionId){
    USER::AccessOnly('1,4','exit()');
    
    $insertSql = 'INSERT INTO `'.PREFIX.'poll_answer` (`question_id`, `answer`) VALUES ';
    
    foreach($arAnswers as $answer){
      if(!empty($answer)){
        $insertSql .= '('.$questionId.', '.DB::quote($answer).'),';
      }
    }
    
    $insertSql = substr($insertSql, 0, -1);
    
    DB::query($insertSql);
  }
  
  private function updateAnswers($arAnswers){
    $updateSql  = '';
    
    foreach($arAnswers as $id=>$answer){
      if(empty($answer)){
        continue;
      }
      
      if(!DB::query('UPDATE `'.PREFIX.'poll_answer` SET `answer` = '.DB::quote($answer).' WHERE `id` = '.DB::quote($id, true))){
        return false;
      }
    }
    
    return true;
  }
  
  private function dateFormatToDB($date){
    USER::AccessOnly('1,4','exit()');
    
    $dateTime = explode(' ', $date);
    $arDate = explode('.', $dateTime[0]);
    $arTime = explode(':', $dateTime[1]);
    
    return date('Y-m-d H:i:s', mktime($arTime[0], $arTime[1], 0, $arDate[1], $arDate[0], $arDate[2]));
  }
  
  public function getEntity(){
    USER::AccessOnly('1,4','exit()');
    
    if($id = $_POST['id']){
      $dbRes = DB::query('
        SELECT * 
        FROM `'.PREFIX.'poll_question` 
        WHERE `id` = '.DB::quote($id));
      
      if($row = DB::fetchAssoc($dbRes)){
        
        $row['date_active_from'] = date('d.m.Y H:i',strtotime($row['date_active_from']));
      
        if(!strtotime($row['date_active_to']) || $row['date_active_to'] == '0000-00-00 00:00:00'){
          $row['date_active_to'] = '';
        }else{
          $row['date_active_to'] = date('d.m.Y H:i',strtotime($row['date_active_to']));
        }
        
        $row['answers'] = array();
        
        $dbRes = DB::query('
          SELECT * 
          FROM `'.PREFIX.'poll_answer` 
          WHERE `question_id` = '.DB::quote($id, true).' 
          ORDER BY `id` ASC');
        
        while($answer = DB::fetchAssoc($dbRes)){
          $row['answers'][] = $answer;
        }
        
        $this->data = $row;
        return true;
      }
    }
    
    return false;
  }
  
  public function addVote(){
    if($id = $_POST['id']){
      $sql = '
        UPDATE `'.PREFIX.'poll_answer` 
        SET `votes` = `votes`+1 
        WHERE `id` = '.DB::quote($id, true);
      
      if(DB::query($sql)){
        $qId = $_POST['question_id'];
        $data = $this->getPollResults($qId);
        $data['ajax'] = 1;
        
        $ds = DIRECTORY_SEPARATOR;
        $realDocumentRoot = str_replace($ds.'mg-plugins'.$ds.self::$pluginName, '', dirname(__FILE__));
        ob_start();
        include($realDocumentRoot.$ds.PLUGIN_DIR.self::$pluginName.$ds.'views'.$ds.'poll-result.php');
        $this->data['html'] = ob_get_contents();
        ob_end_clean();
        
        setcookie('MG_POLL_QUESTION_'.$qId, 1, time()+3600*24*365);
        
        return true;
      }
    }
    
    return false;
  }
  
  private function getPollResults($id){
    $result = array();
    
    if(!empty($id)){
      $sql = '
        SELECT * 
        FROM `'.PREFIX.'poll_answer` 
        WHERE `question_id` = '.DB::quote($id, true).' 
        ORDER BY `id` ASC';
      
      $result['answers'] = array();
      $dbRes = DB::query($sql);
      $votes = 0;
      while($res = DB::fetchAssoc($dbRes)){
        $result['answers'][] = $res;
        $votes += $res['votes'];
      }
      
      $result['votes'] = $votes;
    }
    
    return $result;
  }
  
  /**
   * Устанавливает флаг  активности  
   * @return type
   */
  public function visibleEntity(){
    USER::AccessOnly('1,4','exit()');
    
    $this->messageSucces = $this->lang['ACT_V_ENTITY'];
    $this->messageError = $this->lang['ACT_UNV_ENTITY'];

    $arFields = $_POST;
    //обновление
    if(!empty($arFields['id'])){
      unset($arFields['pluginHandler']);
      $this->updateEntity($arFields);
    }

    if($arFields['activity']){
      return true;
    }

    return false;
  }
  
  /**
   * Удаление сущности
   * @return boolean
   */
  public function deleteEntity(){
    USER::AccessOnly('1,4','exit()');
    
    $this->messageSucces = $this->lang['ENTITY_DEL'];
    $this->messageError = $this->lang['ENTITY_DEL_NOT'];
    $id = $_POST['id'];
    
    if(DB::query('
      DELETE FROM `'.PREFIX.'poll_question` 
      WHERE `id`= '.DB::quote($id, true)) && 
      DB::query('
      DELETE FROM `'.PREFIX.'poll_answer` 
      WHERE `question_id`= '.DB::quote($id, true))){
      return true;
    }
    
    return false;
  }
}