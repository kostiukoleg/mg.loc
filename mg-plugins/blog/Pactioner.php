<?php

/**
 * Класс Pactioner наследник стандарного Actioner
 * Предназначен для выполнения действий,  AJAX запросов плагина 
 *
 * @author Avdeev Mark <mark-avdeev@mail.ru>
 */
class Pactioner extends Actioner{

  private $pluginName = 'blog';

  /**
   * Добавление сущности в таблицу БД
   * @param type $array - массив полей и значений
   * @return array возвращает входящий массив
   */
  private function addEntity($array){
    USER::AccessOnly('1,4','exit()');
    unset($array['id']);
    $result = array();
    $arCategories = array();
    
    $arCats = $array['category_id'];
    unset($array['category_id']);
    $newCat = $array['new_category'];
    unset($array['new_category']);
    
    if(!empty($array['date_active_from'])){
      $dateTime = explode(' ', $array['date_active_from']);
      $arDate = explode('.', $dateTime[0]);
      $arTime = explode(':', $dateTime[1]);
      $array['date_active_from'] = date('Y-m-d H:i:s', mktime($arTime[0], $arTime[1], 0, $arDate[1], $arDate[0], $arDate[2]));
    }
	else
	{
	  $array['date_active_from'] = date('Y-m-d H:i:s');
	}

    if(!empty($array['date_active_to'])){
      $dateTime = explode(' ', $array['date_active_to']);
      $arDate = explode('.', $dateTime[0]);
      $arTime = explode(':', $dateTime[1]);
      $array['date_active_to'] = date('Y-m-d H:i:s', mktime($arTime[0], $arTime[1], 0, $arDate[1], $arDate[0], $arDate[2]));
    }
    
    $array["activity"] = 1;
    
    if(DB::buildQuery('INSERT INTO `'.PREFIX.$this->pluginName.'_items` SET ', $array)){
      $entityId = DB::insertId();
      
      if($arCats){
          
        if(is_array($arCats)){
          $arCategories = $arCats;
        }else{
          $arCategories[] = $arCats;
        }
        
        $catInfo = $this->getCategoryUrl($arCategories[0]);
        $array["cat_url"] = $catInfo['url'];
        $array["cat_name"] = $catInfo['title'];
      }elseif($newCat){
        $arFields = array(
          'newCat' => 1,
          'title' => $newCat,
          'url' => MG::translitIt($newCat),
        );
        $arCategories[] = $this->addCategory($arFields);
        
        $catInfo = $this->getCategoryUrl($arCategories[0]);
        $array["cat_url"] = $catInfo['url'];
        $array["cat_name"] = $catInfo['title'];
      }

      $array["id"] = $entityId;
      $array["date_create"] = date("d.m.Y H:i");
      
      if(!empty($arCategories)){
        $this->setItem2Category($entityId, $arCategories);
      }
      
      $result = $array;
    }
    
    return $result;
  }

  /**
   * Обновление сущности в таблице БД
   * @param type $array - массив полей и значений
   * @return array возвращает входящий массив
   */
  private function updateEntity($array){
    USER::AccessOnly('1,4','exit()');
    $id = $array['id'];
    $result = false;
    $arCategories = array();
    if(!empty($id)){
      $arCats = $array['category_id'];
      unset($array['category_id']);
      $newCat = $array['new_category'];
      unset($array['new_category']);
      
      if(!empty($array['date_active_from'])){
        $dateTime = explode(' ', $array['date_active_from']);
        $arDate = explode('.', $dateTime[0]);
        $arTime = explode(':', $dateTime[1]);
        $array['date_active_from'] = date('Y-m-d H:i:s', mktime($arTime[0], $arTime[1], 0, $arDate[1], $arDate[0], $arDate[2]));
      }else{
        $array['date_active_from'] = '';
      }

      if(!empty($array['date_active_to'])){
        $dateTime = explode(' ', $array['date_active_to']);
        $arDate = explode('.', $dateTime[0]);
        $arTime = explode(':', $dateTime[1]);
        $array['date_active_to'] = date('Y-m-d H:i:s', mktime($arTime[0], $arTime[1], 0, $arDate[1], $arDate[0], $arDate[2]));
      }else{
        $array['date_active_to'] = '';
      }
      
      if(DB::query('
        UPDATE `'.PREFIX.$this->pluginName.'_items`
        SET '.DB::buildPartQuery($array).'
        WHERE id = '.DB::quote($id))) {
        
        $array['date_active_from'] = date('d.m.Y H:i',strtotime($array['date_active_from']));
        $array['date_active_to'] = date('d.m.Y H:i',strtotime($array['date_active_to']));
        
        if($arCats){
          
          if(is_array($arCats)){
            $arCategories = $arCats;
          }else{
            $arCategories[] = $arCats;
            if($catInfo = $this->getCategoryUrl($arCategories[0])){
              $array["cat_url"] = $catInfo['url'];
              $array["cat_name"] = $catInfo['title'];
            }
          }
          
        }elseif($newCat){
          $arFields = array(
            'newCat' => 1,
            'title' => $newCat,
            'url' => MG::translitIt($newCat),
          );
          $arCategories[] = $this->addCategory($arFields);
          if($catInfo = $this->getCategoryUrl($arCategories[0])){
            $array["cat_url"] = $catInfo['url'];
            $array["cat_name"] = $catInfo['title'];
          }
        }else{
          if(!$this->deleteItem2Category($id)){
            return false;
          }
        }

        if(!empty($arCategories)){
          $this->setItem2Category($id, $arCategories);
        }
        
        $result = $array;
      }
      
    }else{
      $result = $this->addEntity($array);
    }
    return $result;
  }
  
  private function deleteItem2Category($id){
    if(DB::query('
      DELETE FROM `'.PREFIX.$this->pluginName.'_item2category` 
      WHERE `item_id`= '.DB::quote($id))){
        return true;
      }
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
    
    self::deleteImageNews(array('id'=>$id), false);
    
    if(DB::query('
      DELETE FROM `'.PREFIX.$this->pluginName.'_items` 
      WHERE `id`= '.DB::quote($id)) && 
      DB::query('
      DELETE FROM `'.PREFIX.$this->pluginName.'_item2category` 
      WHERE `item_id`= '.DB::quote($id))){
      return true;
    }
    return false;
  }

  /**
   * Получает сущность
   * @return boolean
   */
  public function getEntity(){
    USER::AccessOnly('1,4','exit()');
    $id = $_POST['id'];
    $res = DB::query('
      SELECT i.*, GROUP_CONCAT(i2c.category_id ORDER BY i2c.category_id ASC) as categories
      FROM `'.PREFIX.$this->pluginName.'_items` i 
      LEFT JOIN `'.PREFIX.$this->pluginName.'_item2category` i2c ON i.id = i2c.item_id
      WHERE i.id = '.DB::quote($id).'
      GROUP BY i.id');

    if($row = DB::fetchAssoc($res)){
      
      $row['date_active_from'] = date('d.m.Y H:i',strtotime($row['date_active_from']));
      
      if(!strtotime($row['date_active_to']) || $row['date_active_to'] == '0000-00-00 00:00:00'){
        $row['date_active_to'] = '';
      }else{
        $row['date_active_to'] = date('d.m.Y H:i',strtotime($row['date_active_to']));
      }
	  
      $cats = explode(",", $row["categories"]);
      if(!empty($cats)){
        $row["categories"] = $cats;
      }
      $this->data = $row;
      return true;
    }else{
      return false;
    }

    return false;
  }

  public function getPreview(){
    USER::AccessOnly('1,4','exit()');
    $data = $_POST;
    $data['date_create'] = date("d.m.Y H:i");
    $data['catPath'] = "/blog/";
    $data['img_path'] = '/uploads/'.$this->pluginName.'/';
    $text = explode("<!--end-preview-->", $data['description']);
    
    if(count($text) > 1){
      $data['previewText'] = $text[0];
      $data['detailText'] = $text[1];
      unset($data['description']);
    }else{
      $data['detailText'] = $data['description'];
    }
    
    $realDocumentRoot = str_replace(DIRECTORY_SEPARATOR.'mg-plugins'.DIRECTORY_SEPARATOR.$this->pluginName, '', dirname(__FILE__));
    ob_start();
    include($realDocumentRoot.'/mg-pages/'.$this->pluginName.'/article.php');
    $result = ob_get_contents();
    ob_end_clean();
    
    $this->data = $result;
    
    return true;
  }
  
  /**
   * Сохраняет и обновляет параметры записи.
   * @return type
   */
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
    if (!empty($arFields['id'])) {
      unset($arFields['pluginHandler']);
      $this->updateEntity($arFields);
    }

    if ($arFields['activity']) {
      return true;
    }

    return false;
  }

  /**
   * Сохраняет  опции плагина
   * @return boolean
   */
  public function saveBaseOption(){
    USER::AccessOnly('1,4','exit()');
    $this->messageSucces = $this->lang['SAVE_BASE'];
    $this->messageError = $this->lang['NOT_SAVE_BASE'];
    
    if (!empty($_POST['data'])) {
      MG::setOption(array('option' => 'blog-option', 'value' => addslashes(serialize($_POST['data']))));
    }
    
    return true;
  }
  
  /**
   * Получает запись по его URL.
   *
   * @param string $url запрашиваемой страницы.
   * @return array массив с данными о запрашиваемой страницы.
   */
  public function getItemByUrl($url){
    USER::AccessOnly('1,4','exit()');
    $result = array();
    $res = DB::query('
      SELECT *
      FROM `'.PREFIX.$this->pluginName.'_items`  
      WHERE url="'.DB::quote(URL::getRoute(),1).'.html" OR url="'.DB::quote(URL::getRoute(),1).'"
    ', $url);

    if(!empty($res)){
      if($news = DB::fetchArray($res)){
        $result = $news;
      }
    }

    return $result;
  }
  
  /**
   * Добавление категории
   * @param array $arFields 
   * @return int id созданно категории
   */
  private function addCategory($arFields=array()){
    USER::AccessOnly('1,4','exit()');
    $result = array();
    
    if(!$arFields['newCat']){
      $arFields = $_POST;
      unset($arFields['pluginHandler']);
    }else{
      unset($arFields['newCat']);
    }
    
    $arFields['url'] = MG::translitIt($arFields['title']);
    
    if(strlen($arFields['url']) > 60){
      $arFields['url'] = substr($arFields['url'], 0, 60);
    }
    
    if(DB::buildQuery('INSERT INTO `'.PREFIX.$this->pluginName.'_categories` SET ', $arFields)){
      $id = DB::insertId();
      
      if(DB::query('UPDATE `'.PREFIX.$this->pluginName.'_categories` SET `sort` = '.$id.' WHERE `id` = '.$id)){
        return $id;
      }
    }
    
  }
  
  /**
   * Получение информации о категории
   * @return boolean
   */
  public function getCategory(){
    USER::AccessOnly('1,4','exit()');
    $id = $_POST['id'];
    
    $sql = '
      SELECT * 
      FROM `'.PREFIX.$this->pluginName.'_categories`
      WHERE id = '.DB::quote($id);
    
    $res = DB::query($sql);
    
    if($row = DB::fetchAssoc($res)){
      $this->data = $row;
      return true;
    }else{
      return false;
    }

  }
  
  /**
   * Обновление информации о категории
   * @param array $arFields
   * @return array Данные новой категории или обновленные данные старой
   */
  private function updateCategory($arFields){
    USER::AccessOnly('1,4','exit()');
    $id = $arFields['id'];
    $result = false;
    $arCategories = array();
    if (!empty($id)) {
      
      if(DB::query('
        UPDATE `'.PREFIX.$this->pluginName.'_categories`
        SET '.DB::buildPartQuery($arFields).'
        WHERE id = '.DB::quote($id))){
        $result = true;
      }
    } else {
      $result = $this->addCategory($arFields);
    }
    
    return $result;
  }
  
  /**
   * Сохранение категории
   * @return boolean
   */
  public function saveCategory(){
    USER::AccessOnly('1,4','exit()');
    $this->messageSucces = $this->lang['ENTITY_SAVE'];
    $this->messageError = $this->lang['CATEGORY_SAVE_NOT'];

    $arFields = $_POST;
    unset($arFields['pluginHandler']);

    if (!empty($arFields['id'])) {  // если передан ID, то обновляем
      $this->updateCategory($arFields);
      $this->data = $arFields;
    } else {
      // если  не передан ID, то создаем
      $this->data = array(
        'id' => $this->addCategory($arFields),
        'title' => $arFields['title'],
        'url' => $arFields['url'],
      );
    }
    
    return true;
  }
  
  /**
   * Получает url категории по её id
   * @param type $id
   * @return boolean
   */
  private function getCategoryUrl($id){
    USER::AccessOnly('1,4','exit()');
    
    $sql = '
      SELECT title, url 
      FROM `'.PREFIX.$this->pluginName.'_categories`
      WHERE id = '.DB::quote($id);
    
    $res = DB::query($sql);
    
    if($row = DB::fetchAssoc($res)){
      return $row;
    }else{
      return false;
    }
    
  }
  
  /**
   * Устанавливает количество отображаемых записей в разделе новостей
   * @return boolean
   */
  public function setCountPrintRowsNews(){

    $count = 20;
    if(is_numeric($_POST['count'])&& !empty($_POST['count'])){
      $count = $_POST['count'];
    }

    MG::setOption(array('option'=>'countPrintRowsBlog', 'value'=>$count));
    return true;
  }
  
  /**
   * Удаление категории
   * @return boolean
   */
  public function deleteCategory(){
    USER::AccessOnly('1,4','exit()');
    $this->messageSucces = $this->lang['CATEGORY_DEL'];
    $this->messageError = $this->lang['CATEGORY_DEL_NOT'];
    $id = $_POST['id'];
    
    if(DB::query('
      DELETE FROM `'.PREFIX.$this->pluginName.'_categories` 
      WHERE `id`= '.DB::quote($id)) && 
      DB::query('
      DELETE FROM `'.PREFIX.$this->pluginName.'_item2category` 
      WHERE `category_id`= '.DB::quote($id))){
      return true;
    }
    
    return false;
  }
  
  /**
   * Устанавливает связь между статьёй и категориями
   * @param int $itemId идентификатор элемента
   * @param array $categoryIds массив со списком категорий
   */
  private function setItem2Category($itemId, $categoryIds){
    USER::AccessOnly('1,4','exit()');
    $sql = '
      DELETE FROM `'.PREFIX.$this->pluginName.'_item2category`
      WHERE `item_id` = '.DB::quote($itemId);
    DB::query($sql);
    
    $sql = '
      INSERT INTO `'.PREFIX.$this->pluginName.'_item2category` (item_id, category_id) 
      VALUES ';
    
    $minCount = 0;
    foreach($categoryIds as $count=>$category){
      
      if($category == 0){
        $minCount++;
        continue;
      }
      if($count>$minCount){
        $sql .= ',';
      }
      
      $sql .= '('.$itemId.', '.$category.')';
    }
    
    DB::query($sql);
  }
  
   /**
   * Добавляет картинку товара.
   * @return boolean
   */
  public function addImageNews(){
    USER::AccessOnly('1,4','exit()');
    $path = 'uploads/'.$this->pluginName.'/';
    
    $validFormats = array('jpeg', 'jpg', 'png', 'gif');
    if(isset($_POST) && 'POST' == $_SERVER['REQUEST_METHOD']){
      
      if(!empty($_FILES['photoimg'])){
        $file_array = $_FILES['photoimg'];
      }else{
        $file_array = $_FILES['edit_photoimg'];
      }

      $name = $file_array['name'];
      $size = $file_array['size'];

      if(strlen($name)){
        //list($txt, $ext) = explode('.', $name);
        $fullName = explode('.', $name);
        $ext = array_pop($fullName);
        $name = implode('.', $fullName);
        if(in_array($ext, $validFormats)){
          if($size < (1024 * 1024)){
            $name = time();
            $actualImageName = $name.'.'.$ext;
            $tmp = $file_array['tmp_name'];
            //подготовка миниатюр 70% и 30% от оригинала
            $this->reSizeImage($name, $ext, $tmp, 0.3);
            $this->reSizeImage($name, $ext, $tmp, 0.7);
            
            if(move_uploaded_file($tmp, $path.$actualImageName)){

              $this->data = array('img' => $actualImageName);
              $this->messageSucces = $this->lang['ACT_IMG_UPLOAD'];
              return true;
            }else{
              $this->messageError = $this->lang['ACT_IMG_NOT_UPLOAD'];
              return false;
            }
          }else{
            $this->messageError = $this->lang['ACT_IMG_NOT_UPLOAD1'];
            return false;
          }
        }else{
          $this->messageError = $this->lang['ACT_IMG_NOT_UPLOAD2'];
          return false;
        }
      }else{
        $this->messageError = $this->lang['ACT_IMG_NOT_UPLOAD3'];
        return false;
      }
    }
    return false;
  }

  /**
   * Удаляет изображение новости из папки  uploads/blog/
   * @param arFields - массив, содержащий id статьи и имя файла для удаления
   * @return bool
   */
  public function deleteImageNews($arFields=array(), $addMess=true){
    USER::AccessOnly('1,4','exit()');
    
    if(empty($arFields)){
      $arFields = $_POST;
    }
    
    if(empty($arFields['msgImg']) && $addMess){
      $this->messageSucces = $this->lang['ACT_IMG_DEL'];
    }
    
    $this->messageError = 'Ошибка';
    
    if(empty($arFields['imgFile'])&&!empty($arFields['id'])){
      $sql = '
        SELECT image_url
        FROM `'.PREFIX.$this->pluginName.'_items`
        WHERE id = '.DB::quote($arFields['id']);
      $dbRes = DB::query($sql);
      
      if($res = DB::fetchAssoc($dbRes)){
        $arFields['imgFile'] = $res['image_url'];
      }
    }

    // удаление ссылки на картинку из БД
    $array['id'] = $arFields['id'];
    $array['image_url'] = '';
    if(!empty($array['id'])){
     $this->updateEntity($array);
    }

    // удаление картинки с сервера
        
    $documentroot = str_replace(DIRECTORY_SEPARATOR.'mg-plugins'.DIRECTORY_SEPARATOR.$this->pluginName,'',dirname(__FILE__));
    $documentroot = str_replace("\\", "/", $documentroot);
    
    if(is_file($documentroot."/uploads/".$this->pluginName."/".basename($arFields['imgFile']))){
      unlink($documentroot."/uploads/".$this->pluginName."/".basename($arFields['imgFile']));
      if(is_file($documentroot."/uploads/".$this->pluginName."/thumbs/30_".basename($arFields['imgFile'])))
        unlink($documentroot."/uploads/".$this->pluginName."/thumbs/30_".basename($arFields['imgFile']));
      if(is_file($documentroot."/uploads/".$this->pluginName."/thumbs/70_".basename($arFields['imgFile'])))
        unlink($documentroot."/uploads/".$this->pluginName."/thumbs/70_".basename($arFields['imgFile']));
      
      return true;
    }


    return false;
  }
  
  /**
   * Функция для ресайза картинки
   * @param string $name имя файла без расширения
   * @param string $ext расшерения файла
   * @param string $tmp исходный временный файл
   * @paramint $koef коэффициент сжатия изображения
   * @return void
   */
  public function reSizeImage($name, $ext, $tmp, $koef, $dirUpload='uploads/blog/thumbs/'){
    USER::AccessOnly('1,4','exit()');
    $percent = $koef * 100;
    // получение новых размеров
    list($width_orig, $height_orig) = getimagesize($tmp);
    $width = $koef * $width_orig;
    $height = $koef * $height_orig;
    // ресэмплирование
    $image_p = imagecreatetruecolor($width, $height);
    // вывод
    switch($ext){
      case 'png':
        $image = imagecreatefrompng($tmp);
        //делаем фон изображения белым, иначе в png при прозрачных рисунках фон черный
        $color = imagecolorallocate($image_p, 255, 255, 255);
        imagefill($image_p, 0, 0, $color);

        imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
        imagepng($image_p, $dirUpload.$percent.'_'.$name.'.'.$ext);
        break;

      case 'gif':
        $image = imagecreatefromgif($tmp);
        imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
        imagegif($image_p, $dirUpload.$percent.'_'.$name.'.'.$ext, 100);
        break;

      default:
        $image = imagecreatefromjpeg($tmp);
        imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
        imagejpeg($image_p, $dirUpload.$percent.'_'.$name.'.'.$ext, 100);
    }
    imagedestroy($image_p);
    imagedestroy($image);
  }
}