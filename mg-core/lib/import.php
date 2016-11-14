<?php

/**
 * Класс Import - предназначен для импорта товаров в каталог магазина. Поддерживает две структуры файлов  в формате CSV. Упрощенная - с артикулами и ценами, а также полная со всей информацией о каждом товаре.
 *
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Libraries
 */
class Import{
  private $typeCatalog = 'MogutaCMS';
  private $currentRowId = null;
  private $validError = null;  
  public static $fullProduct = array();
  private static $notUpdate = array();
  public static $maskArray = array(
    'MogutaCMS' => array(
      'Категория',
      'URL категории',
      'Товар',
      'Вариант',
      'Описание',
      'Цена',
      'URL',
      'Изображение',
      'Артикул',
      'Количество',
      'Активность',
      'Заголовок [SEO]',
      'Ключевые слова [SEO]',
      'Описание [SEO]',
      'Старая цена',
      'Рекомендуемый',
      'Новый',
      'Сортировка',
      'Вес',
      'Связанные артикулы',
      'Смежные категории',
      'Ссылка на товар',
      'Валюта',
      'Свойства', 
      'id',
    ),
    'MogutaCMSUpdate' => array(
      'Артикул',
      'Цена',
      'Старая цена',
      'Количество',
      'Активность',              
    ),
    'Category' => array(
      'Название категории',
      'URL категории',
      'id родительской категории',
      'URL родительской категории',
      'Описание категории',
      'Изображение',
      'Заголовок [SEO]',
      'Ключевые слова [SEO]',
      'Описание [SEO]',
      'SEO Описание',
      'Наценка',
      'Не выводить в меню',
      'Активность',
      'Не выгружать в YML',
      'Сортировка',
      'Внешний идентификатор',
      'id',
    ),
  );
  public static $fields = array(
    'MogutaCMS' => array(
      'cat_id',
      'cat_url',
      'title',
      'variant',      
      'description',
      'price',
      'url',
      'image_url',
      'code',
      'count',
      'activity',
      'meta_title',
      'meta_keywords',
      'meta_desc',
      'old_price',
      'recommend',
      'new',
      'sort',
      'weight',
      'related',
      'inside_cat',
      'link_electro',
      'currency_iso',
      'property',   
      'id',
    ),
    'MogutaCMSUpdate' => array(
      'code',
      'price',    
      'old_price',
      'count',
      'activity',
    ),
  );

  public function __construct($typeCatalog = "MogutaCMS") {
    $this->typeCatalog = $typeCatalog;  
    self::$notUpdate = explode(',', MG::getOption('csvImport-'.$typeCatalog.'-notUpdateCol'));
  }
  
  public function setTypeCatalog($type){
      $this->typeCatalog = $type;
  }

  public function setNotUpdateFields($notUpdate){
    self::$notUpdate = $notUpdate;
  }
  
  public function getValidError(){
    return $this->validError;
  }
  
  public static function getTitleList(){
    $titleList = array();
    $file = new SplFileObject("uploads/importCatalog.csv");
            
    if(!$file->eof()){
      $data = $file->fgetcsv(";");
      
      foreach($data as $cell=>$value){
        $value = str_replace(' ',' ',iconv("WINDOWS-1251", "UTF-8", $value));
        $titleList[$cell] = $value;
      }
    }
    
    return $titleList;
  }
  
  /**
   * Запускает загрузку товаров с заданной строки.
   * @param int $rowId - id строки для старта
   * @return array
   */
  public function startCategoryUpload($rowId = false) {
    if(!$rowId){
      $rowId = 1;
    }
    
    if(empty($_SESSION['stopProcessImportCsv'])){
      $data = $this->importFromCsv($rowId, "default");

      if($data===false){
        $msg = 'Ошибка в CSV файле! '.$this->validError.' line:'.((int)$this->currentRowId+1);
        
        return array(
          'status' => 'error',
          'msg' => $msg
        );
      }
      
      return array(
        'percent' => $data['percent'],
        'status' => 'run',
        'rowId' => $data['rowId']       
      );
    } else{
      unset($_SESSION['stopProcessImportCsv']);
      
      return array(
        'percent' => 0,
        'status' => 'canseled',
        'rowId' => $rowId
      );
    }
  }

  /**
   * Запускает загрузку товаров с заданной строки.
   * @param type $rowId - id строки для старта
   * @return type
   */
  public function startUpload($rowId = false, $schemeType = 'default') {    
    if(!$rowId){
      $rowId = 1;
    }
    if(empty($_SESSION['stopProcessImportCsv'])){

      $data = $this->importFromCsv($rowId, $schemeType);

      if($data===false){
        $msg = 'Ошибка в CSV файле! '.$this->validError.' line:'.((int)$this->currentRowId+1).'<br />Попробуйте использовать свою схему импорта данных.';
        return
        array(
          'status' => 'error',
          'msg' => $msg
        );
      }
      
      return
        array(
          'percent' => $data['percent'],
          'status' => 'run',
          'rowId' => $data['rowId']       
      );
    } else{
      unset($_SESSION['stopProcessImportCsv']);
      return
        array(
          'percent' => 0,
          'status' => 'canseled',
          'rowId' => $rowId
      );
    }
  }

  /**
   * Останавливает процесс импорта.
   * @return type
   */
  public function stopProcess() {
    $_SESSION['stopProcessImportCsv'] = true;
  }

  /**
   * Вычисляет разделитель в CSV файле.
   * @return type
   */
  public function getDelimetr() {
    $delimert = ';';
    return $delimert;
  }

  public function importFromCsv($rowId, $schemeType) {
    $this->maxExecTime = min(30, @ini_get("max_execution_time"));
    
    if(empty($this->maxExecTime)){
      $this->maxExecTime = 30;
    }
    
    $startTimeSql = microtime(true);
    $delimert = $this->getDelimetr();
    $infile = false;

    $file = new SplFileObject("uploads/importCatalog.csv");
    
    if($rowId === 1 || empty($rowId)){
      $rowId = 0;
    }
    
    $this->currentRowId = $rowId;
    $file->seek($rowId);
    $validFormat = true;
  
    while(!$file->eof()){
      
      $infile = true;
      $data = $file->fgetcsv(";");           
      
      if($rowId === 0){
        
        if($schemeType == 'default'){
          $validFormat = $this->validateFormate(
            $data,
            self::$maskArray[$this->typeCatalog]
          );
          if(!$validFormat){
            break;          
          }
        }        
        
        $rowId = 1;
        continue;
      }
      
      if((microtime(true) - $startTimeSql) > $this->maxExecTime - 5){
        break;
      }            
      
      $cData = array(); 
      $complianceArray = self::getCompliance($this->typeCatalog, $schemeType);

      foreach(self::$maskArray[$this->typeCatalog] as $key=>$title){
        if (empty($complianceArray)) {
          $v = trim($data[$key]);
        } else {
          $v = trim($data[$complianceArray[$key]]);
        }
        

        if(!empty($v) || $v == 0){          
          $cData[$key] = str_replace(' ',' ',iconv("WINDOWS-1251", "UTF-8", $v));
        }else{
          $cData[$key] = '';
        }
      }
      
      $data = $cData;
      self::$fullProduct = $cData;
      
      $this->currentRowId = $rowId;
      switch($this->typeCatalog){
        case "MogutaCMS":
          if(!$this->formateMogutaCMS($data)){
            return false;
          }
          break;
        case "MogutaCMSUpdate":
          if(!$this->formateMogutaCMSUpdate($data)){
            return false;
          }
          break;
        case "Category":
          if(!$this->formateCategoryMogutaCMS($data)){
            return false;
          }
        default:
          if(!$this->formateMogutaCMS($data)){
            return false;
          }
      }
      
      $rowId++;
      
    }
    
    if(!$validFormat){
      $this->validError = 'Нарушен порядок столбцов или кодировка!';
      return false;
    }
    
    $file = null;

    $percent100 = count(file("uploads/importCatalog.csv"));
    $percent = $rowId;
    $percent = ($percent * 100) / $percent100;

    if(!$infile){
      $percent = 100;
    }

    $data = array(
      'rowId' => $rowId,
      'percent' => floor($percent),
    );

    return $data;
  }
  
  function getCompliance($importType, $scheme){
    $data = array();
    
    if($scheme != 'default'){
      $data = MG::getOption('csvImport-last'.$importType.'ColComp');
      $data = unserialize(stripslashes($data));
    }else{
      foreach(Import::$maskArray[$importType] as $id=>$title){
        $data[$id] = $id;
      }
    }
    
    return $data;
  } 
  
  /**
   * Проверка валидности файла.
   */
  public function validateFormate($data,$maskArray) {
    $result = true;
    
    if(!empty($maskArray[24])){
      unset($maskArray[24]);
      }
    // Проверим на соответствие заголовки столбцов.
    foreach($data as $k => $v){
      $v = str_replace(' ',' ',iconv("WINDOWS-1251", "UTF-8", $v));
      
      if(isset($maskArray[$k])){
        if($maskArray[$k]!=$v) {
          $result = false;        
          $this->validError = 'Столбец "'.$maskArray[$k].'" не обнаружен!';
          
          break;
        }      
      }
    }
    
    return $result;    
  }
  
  /**
   * Импорт или обновление категории.
   */
  public function formateCategoryMogutaCMS($data) {
    $arFields = array(
      'title',
      'url',      
      'parent',
      'parent_url',
      'html_content',
      'image_url',
      'meta_title',
      'meta_keywords',
      'meta_desc',
      'seo_content',
      'rate',
      'invisible',
      'activity',
      'export',
      'sort',
      '1c_id',
      'id'
    );
    $itemsIn = array();
    
    foreach ($arFields as $key => $field) {
      $itemsIn[$field] = $data[$key];
    }
    
    $category = new Category();
    $category->updateCategory($itemsIn);
    
    return true;
  }
  
  /**
   * Полная выгрузка по формату Moguta.CMS.
   */    
  public function formateMogutaCMS($data, $new = false) { 
    foreach($data as $cell => $value){
      if(in_array($cell, self::$notUpdate) && !$new && $_POST['schemeType'] != 'default'){
        continue;
      }
      
      $itemsIn[self::$fields[$this->typeCatalog][$cell]] = trim($value);      
    }            
    
    if(!empty($data[3])){
      if(strpos($data[3], '[:param:]')!==false){
        $variant = explode('[:param:]', $data[3]);
        $itemsIn['variant'] = $variant[0];
        $itemsIn['image'] = str_replace(array('[src=', ']'),'', $variant[1]);
      }else{
        $itemsIn['variant'] = $data[3];
      }     
    }   

    if(empty($itemsIn['cat_id'])){
      $itemsIn['cat_id'] = -1;
    }
    
    $itemsIn['price'] = str_replace(',','.',$itemsIn['price']);
    $itemsIn['old_price'] = str_replace(',','.',$itemsIn['old_price']);

    $this->prepareLineCsv($itemsIn);
    return true;
  }
    
  
  /**
   * Выгрузка для обновления цен имеющихся товаров по их артикулам.
   */
  public function formateMogutaCMSUpdate($data) {     
    
    foreach($data as $cell => $value){
      if($cell == 0 || (in_array($cell, self::$notUpdate) && $_POST['schemeType'] != 'default') 
              || self::$fields[$this->typeCatalog][$cell] == 'code'){
        if(self::$fields[$this->typeCatalog][$cell] == 'code'){
          $data[0] = $data[$cell];
        }
        
        continue;
      }
      
      if(in_array($cell, array(1,2))){
        $value = str_replace(',','.',$value);
      }
      
      $itemsIn[self::$fields[$this->typeCatalog][$cell]] = trim($value);      
    }                 
 
    if((count($data) < count($itemsIn)+1) && count($data)>1){
      $this->validError = 'Нарушена целостность строки!';
      return false;
    }            
    
    DB::query('
      UPDATE `'.PREFIX.'product`
      SET '.DB::buildPartQuery($itemsIn).'
      WHERE code = '.DB::quote($data[0])
    );
    
    DB::query('
      UPDATE `'.PREFIX.'product_variant`
      SET '.DB::buildPartQuery($itemsIn).'
      WHERE code = '.DB::quote($data[0])
    );
    
    $model = new Models_Product();
    $currencyShopIso = MG::getSetting('currencyShopIso');    
    
    $res = DB::query('
      SELECT id
      FROM `'.PREFIX.'product`
      WHERE code = '.DB::quote($data[0])
    );
    
    if($row = DB::fetchAssoc($res)){     
      $model->updatePriceCourse($currencyShopIso, array($row['id']));    
    }else{
      $res = DB::query('
        SELECT product_id
        FROM `'.PREFIX.'product_variant`
        WHERE code = '.DB::quote($data[0])
      );
      
      if($row = DB::fetchAssoc($res)){     
        $model->updatePriceCourse($currencyShopIso, array($row['product_id']));    
      }
    }
    
    return true;    
  }
    
  
 
  
  /**
   * Парсит категории, создает их и продукт.
   * @param type $itemsIn - массив собранный из строки файла.
   */
  public function prepareLineCsv($itemsIn) {
    $categories = $this->parseCategoryPath($itemsIn['cat_id']);
    $catId = null;
    
    if($itemsIn['cat_id']==-1){
      $catId = -1;
    }    
    //Первая проверка на корректность URL категории. 
    //Необходимо чтобы  количество разделителей-слэшей в первой колонке, соответствовало количеству 
    //слэшей во второй колонке с URL.
    // Если это условие не выполняется, значит не будем учитывать заданный URL категории, и будет создан правильный URL    
    if($itemsIn['cat_id']!=-1 && substr_count($itemsIn['cat_id'], '/')==substr_count($itemsIn['cat_url'], '/') && !empty($itemsIn['cat_url'])){     
     
      // Проверим на этом этапе, существует ли категория с url = $itemsIn['cat_url'];
      // Если существует, то не будем создавать новую и в последствии будем использовать ее id;
      $url = URL::parsePageUrl($itemsIn['cat_url']);
      $parentUrl = URL::parseParentUrl($itemsIn['cat_url']);
      if($parentUrl=="/"){
        $parentUrl="";
      }
	
      $category = MG::get('category')->getCategoryByUrl($url,$parentUrl);
	 
      if(!empty($category)){
        $catId = $category['id'];   
      }else{
        $this->createCategory($categories);
      }
    }else{ 
      $this->createCategory($categories);   
    }
    
    $this->createProduct($itemsIn, $catId);
    // Вычисляем  ID категории если она есть.
  }

  /**
   * Создает продукт в БД если его не было.
   * @param type $product - массив с данными о продукте.
   * @param type $catId - категория к которой относится продукт.
   */
  public function createProduct($product, $catId = null) {
    $model = new Models_Product();
    $variant = $product['variant'];
    $img_var = $product['image'];
    $property = $product['property'];
    $product['price'] = MG::numberDeFormat($product['price']);
    $product['old_price'] = MG::numberDeFormat($product['old_price']);
    unset($product['cat_url']);
    unset($product['variant']);
    unset($product['image']);
    unset($product['property']);
    
    // Парсим изображение, его alt и title.
    if (strpos($product['image_url'], '[:param:]')!==false) {
      $images = $this->parseImgSeo($product['image_url']);
      $product['image_url'] = $images[0];  
      $product['image_alt'] = $images[1];
      $product['image_title'] = $images[2];         
    }

    if($catId === null){
      // 1 находим ID категории по заданному пути.
      $product['cat_id'] = MG::translitIt($product['cat_id'], 1);
      $product['cat_id'] = URL::prepareUrl($product['cat_id']);

      if($product['cat_id']){
        $product['cat_id'] = (empty($product['cat_id'])) ? $product['cat_url'] : $product['cat_id'];
        
        $url = URL::parsePageUrl($product['cat_id']);
        $parentUrl = URL::parseParentUrl($product['cat_id']);
        $parentUrl = $parentUrl != '/' ? $parentUrl : '';                
        
        $cat = MG::get('category')->getCategoryByUrl($url, $parentUrl);     
        $product['cat_id'] = $cat['id'];
      }
    }else{
      $product['cat_id'] = $catId;
    }

    if($catId == -1){
      unset($product['cat_id']);
    }else{
      $product['cat_id'] = !empty($product['cat_id']) ? $product['cat_id'] : 0;
    }    
    
    if(!empty($product['id'])){
      $dbRes = DB::query('SELECT `id`, `url`, `title` FROM `'.PREFIX.'product` WHERE `id` = '.DB::quote($product['id'], 1));
      
      if($res = DB::fetchArray($dbRes)){        
        if($res['title'] == $product['title']){
          $product['url'] = $res['url'];
        }       
        
        unset($product['id']);
      }else{
        if(empty($_SESSION['csv_import_full'])){ 
          $_SESSION['csv_import_full'] = 'y';
          $this->formateMogutaCMS(self::$fullProduct, true); 
          return;
        }else{
          unset($_SESSION['csv_import_full']);
        }        
        $arrProd = $model->addProduct($product);               
      }             
    }    
    
    if(empty($arrProd)){
      // 2 если URL не задан в файле, то транслитирируем его из названия товара.
      $product['url'] = !empty($product['url'])?$product['url']:preg_replace('~-+~','-',MG::translitIt($product['title'], 0));
	  $product['url'] = str_replace(array(':', '/'),array('', '-'),$product['url']);
      $product['url'] = URL::prepareUrl($product['url'], true);    

      
      if($_POST['identifyType'] == 'name'){
        if(empty($product['cat_id']) || $product['cat_id'] == 0){
          $alreadyProduct = $model->getProductByUrl($product['url']);
        }else{
          $alreadyProduct = $model->getProductByUrl($product['url'], $product['cat_id']);
        }
      }elseif(!empty($product['code'])){
        $res = DB::query('
          SELECT id, url
          FROM `'.PREFIX.'product`
          WHERE code = '.DB::quote($product['code'])
        );
        
        $alreadyProduct = DB::fetchAssoc($res);
        
        if(!$alreadyProduct){
          $res = DB::query('
            SELECT p.id, p.url
            FROM `'.PREFIX.'product` p
              LEFT JOIN `'.PREFIX.'product_variant` pv
                ON pv.product_id = p.id
            WHERE pv.code = '.DB::quote($product['code'])
          );
          
          $alreadyProduct = DB::fetchAssoc($res);
        } 
        
        if(empty($alreadyProduct)){
          if($product['cat_id'] == 0){
            $alreadyProduct = $model->getProductByUrl($product['url']);
          }else{
            $alreadyProduct = $model->getProductByUrl($product['url'], $product['cat_id']);
          }
        }
      }   

      // Если в базе найден этот продукт, то при обновлении будет сохранен ID и URL. 
      if(!empty($alreadyProduct['id'])){
        $product['id'] = $alreadyProduct['id'];
        $product['url'] = $alreadyProduct['url'];
      }
      
      if(empty($_SESSION['csv_import_full']) && (empty($alreadyProduct['id']) || !empty($variant))){ 
        $_SESSION['csv_import_full'] = 'y';
        $this->formateMogutaCMS(self::$fullProduct, true); 
        return;
      }else{
        unset($_SESSION['csv_import_full']);
      }
      
      // обновляем товар, если его не было то метод вернет массив с параметрами вновь созданного товара, в том числе и ID. Иначе  вернет true       
      $arrProd = $model->updateProduct($product);
    }
        
    $product_id = $product['id']?$product['id']:$arrProd['id'];   
    $categoryId = $product['cat_id'];
    $productId = $product_id;
    $listProperty = $property;
    $arrProperty = $this->parseListProperty($listProperty);

    foreach($arrProperty as $key => $value){
      $type = 'string';
      $data = '';
      // Если характеристика сложная, то выделим параметры - тип, значение, наценки.
      if ($value[0]=='['&&$value[strlen($value)-1]==']'&&stristr($value, 'type')!== FALSE
        &&stristr($value, 'value')!== FALSE&&stristr($value, 'product_margin')!== FALSE) {
        if(preg_match("/type=([^&]*)value/", $value, $matches))  {
          $type = trim($matches[1]);
        }
        if(preg_match("/value=([^&]*)product_margin/", $value, $matches))  {
          $value_prop = trim($matches[1]);
        }
        if(preg_match("/product_margin=([^&]*)]/", $value, $matches))  {
          $data = trim($matches[1]);
        }
        $value = $value_prop;
      }
      $this->createProperty($key, $value, $categoryId, $productId, $type, $data);
    }

    if(!$variant){
      return true;
    }
    
    $var = $model->getVariants($product['id'], $variant);
    $varUpdate = null;
    
    if(!empty($var)){
      foreach($var as $k => $v){
        if($v['title_variant'] == $variant && $v['product_id'] == $product_id){
          $varUpdate = $v['id'];
        }
      }
    }

    // Иначе обновляем существующую запись в таблице вариантов.
    $varFields = array(      
      'price',
      'old_price',
      'count',
      'code',  
      'weight',
      'activity',
      'currency_iso'
    );
    
    $newVariant = array(
      'product_id' => $product_id,
      'title_variant' => $variant,
    );
    
    if($img_var){
      $newVariant['image'] = $img_var;
    }
    
    if($_POST['schemeType'] != 'default'){
      foreach(self::$notUpdate as $id){
        $notUpdateFields[$id] = self::$fields[$this->typeCatalog][$id];
      }   
    }       
    
    foreach($varFields as $field){
      if(isset($product[$field])){
        if(!in_array($field, $notUpdateFields)){
          $newVariant[$field] = $product[$field];
        }        
      }
    }
    
    $model->importUpdateProductVariant($varUpdate, $newVariant, $product_id);

    // Обновляем продукт по первому варианту.
    $res = DB::query('
      SELECT  pv.*
      FROM `'.PREFIX.'product_variant` pv    
      WHERE pv.product_id = '.DB::quote($product_id).'
      ORDER BY sort
    ');
    if($row = DB::fetchAssoc($res)){

      if(!empty($row)){
        if($product['title']){
          $row['title'] = $product['title'];
        }        
        
        $row['id'] = $row['product_id'];
        unset($row['image']);
        unset($row['sort']);
        unset($row['title_variant']);
        unset($row['product_id']);
        $model->updateProduct($row);
      }
    }
  }

  /**
   * Создает категории в БД если их небыло.
   * @param array $categories - массив категорий полученный из записи вида категория/субкатегория/субкатегория2.
   */
  public function createCategory($categories) {

    foreach($categories as $category){

      $category['parent_url'] = $category['parent_url'] != '/'?$category['parent_url']:'';

      if($category['parent_url']){
        $pUrl = URL::parsePageUrl($category['parent_url']);
        $parentUrl = URL::parseParentUrl($category['parent_url']);
        $parentUrl = $parentUrl != '/'?$parentUrl:'';
      } else{
        $pUrl = $category['url'];
        $parentUrl = $category['parent_url'];
      }

      // Вычисляем  ID родительской категории если она есть.
      $alreadyParentCat = MG::get('category')->getCategoryByUrl(
        $pUrl, $parentUrl
      );

      // Если нашлась  ID родительская категория назначаем parentID для новой.
      if(!empty($alreadyParentCat)){
        $category['parent'] = $alreadyParentCat['id'];
      }

      // Проверяем, вдруг такая категория уже существует.
      $alreadyExist = MG::get('category')->getCategoryByUrl(
        $category['url'], $category['parent_url']
      );      
     

      if(!empty($alreadyExist)){
        $category = $alreadyExist;
      }

      MG::get('category')->updateCategory($category);
    }
  }

  /**
   * Восстанавливает привязки характеристик для новых категорий из таблицы import_cat_prop.
   */
  public function recoveryTableCatProp() {

    DB::query("
      INSERT INTO ".PREFIX."category_user_property( category_id, property_id ) 
      SELECT c.id as 'category_id', ip.property_id
      FROM ".PREFIX."import_cat_prop AS ip
      RIGHT JOIN ".PREFIX."category AS c ON  ip.url = c.url AND ip.parent_url = c.parent_url   
    ");
  }

  /**
   * Парсит путь категории возвращает набор категорий.
   * @param type $path список категорий через / слэш.
   */
  public function parseCategoryPath($path) {

    $i = 1;

    $categories = array();
    if(!$path || $path == -1){
      return $categories;
    }

    $parent = $path;
    $parentForUrl = str_replace(array('«', '»'), '', $parent);    
    $parentTranslit = MG::translitIt($parentForUrl, 1);
    $parentTranslit = URL::prepareUrl($parentTranslit);

    $categories[$parent]['title'] = URL::parsePageUrl($parent);
    $categories[$parent]['url'] = URL::parsePageUrl($parentTranslit);
    $categories[$parent]['parent_url'] = URL::parseParentUrl($parentTranslit);
    $categories[$parent]['parent'] = 0;

    while($parent != '/'){
      $parent = URL::parseParentUrl($parent);
      $parentForUrl = str_replace(array('«', '»'), '', $parent);
      $parentTranslit = MG::translitIt($parentForUrl, 1);
      $parentTranslit = URL::prepareUrl($parentTranslit);
      if($parent != '/'){
        $categories[$parent]['title'] = URL::parsePageUrl($parent);
        $categories[$parent]['url'] = URL::parsePageUrl($parentTranslit);
        $categories[$parent]['parent_url'] = URL::parseParentUrl($parentTranslit);
        $categories[$parent]['parent_url'] = $categories[$parent]['parent_url'] != '/'?$categories[$parent]['parent_url']:'';
        $categories[$parent]['parent'] = 0;
      }
    }

    $categories = array_reverse($categories);

    return $categories;
  }

  /**
   * Сравнивает создаваемую категорию, с имеющимися ранее.
   * Если обнаруживает, что аналогичная категория раньше существовала,то возвращает ее старый ID.   
   * @param string $title название товара.
   * @param string $path путь.
   */
  public function getCategoryId($title, $path) {
    $path = trim($path, '/');

    $sql = '
      SELECT cat_id
      FROM `'.PREFIX.'import_cat`
      WHERE `title` ='.DB::quote($title)." AND `parent` = ".DB::quote($path);

    $res = DB::query($sql);
    if($row = DB::fetchAssoc($res)){
      return $row['cat_id'];
    }
    return null;
  }

  /**
   * Возвращает старый ID для товара.
   * то возвращает ее старый ID.
   * @param string $title - название товара.
   * @param int $cat_id - id категории.
   */
  public function getProductId($title, $cat_id) {
    $path = trim($path, '/');

    $sql = '
      SELECT product_id
      FROM `'.PREFIX.'import_prod`
      WHERE `title` ='.DB::quote($title)." AND `category_id` = ".DB::quote($cat_id);

    $res = DB::query($sql);
    if($row = DB::fetchAssoc($res)){
      return $row['product_id'];
    }
    return null;
  }

  /**
   * Создает временную таблицу import_cat_prop, для сохранения связей характеристик и категорий.
   */
  public function greateTempTableImport() {
    DB::query("DROP TABLE IF EXISTS ".PREFIX."import_prod");
    DB::query("DROP TABLE IF EXISTS ".PREFIX."import_cat");
    DB::query("
     CREATE TABLE IF NOT EXISTS ".PREFIX."import_cat (
      `cat_id` int(11) NOT NULL,  
      `title` varchar(2048) NOT NULL,
      `parent` varchar(2048) NOT NULL 
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

    DB::query("
     CREATE TABLE IF NOT EXISTS ".PREFIX."import_prod (
      `product_id` int(11) NOT NULL,  
      `title` varchar(2048) NOT NULL,    
      `url_cpu_cat` varchar(2048) NOT NULL,
      `category_id` int(11) NOT NULL,
      `variant` int(1) NOT NULL
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

    $sql = '
      SELECT    
        c.id as category_id,
        c.title as category_title,
        CONCAT(c.parent_url,c.url) as category_url,
        p.url as product_url,
        p.*
      FROM `'.PREFIX.'product` p
      LEFT JOIN `'.PREFIX.'category` c
        ON c.id = p.cat_id';
    $res = DB::query($sql);


    $product = new Models_Product();

    while($row = DB::fetchAssoc($res)){

      $parent = $row['category_url'];

      // Подставляем вместо URL названия разделов.
      $resultPath = '';      
      while($parent){     
        $url = URL::parsePageUrl($parent);
        $parent = URL::parseParentUrl($parent);
        $parent = $parent != '/'?$parent:'';
        $alreadyParentCat = MG::get('category')->getCategoryByUrl(
          $url, $parent
        );
        $resultPath = $alreadyParentCat['title'].'/'.$resultPath;
      }

      $resultPath = trim($resultPath, '/');
      $variants = $product->getVariants($row['id']);
      $variant = 0;

      DB::query("
       INSERT INTO `".PREFIX."import_prod` 
         (`product_id`, `title`, `url_cpu_cat`, `category_id`, `variant`) 
       VALUES (".DB::quote($row['id']).", ".DB::quote($row['title']).", ".DB::quote($row['category_url']).", ".DB::quote($row['category_id']).", ".$variant.")");
    }

    //Наполняем таблицу для категорий.
    $sql = '
      SELECT `id`, `title`, `parent_url`, url,
       CONCAT(parent_url,url) as category_url
      FROM `'.PREFIX.'category` c';
    $res = DB::query($sql);

    while($row = DB::fetchAssoc($res)){

      $parent = $row['parent_url'];

      // Подставляем вместо URL названия разделов.
      $resultPath = ''; 
      while($parent){    
        $url = URL::parsePageUrl($parent);
        $parent = URL::parseParentUrl($parent);
        $parent = $parent != '/'?$parent:'';
        $alreadyParentCat = MG::get('category')->getCategoryByUrl(
          $url, $parent
        );

        $resultPath = $alreadyParentCat['title'].'/'.$resultPath;
      }

      $resultPath = trim($resultPath, '/');

      DB::query("
       INSERT INTO `".PREFIX."import_cat` 
         (`cat_id`, `title`, `parent`) 
       VALUES (".DB::quote($row['id']).", ".DB::quote($row['title']).", ".DB::quote($resultPath).")");
    }
  }

  /**
   * Возвращает массив из входящей строки с характеристиками
   * @param type $listProperty пример $listProperty = 'Производитель=Индия&Цвет=красный&высота=1024';
   * пример сложных характеристик Операционная система=[type=checkbox value=Windows 7 product_margin=iOS#0#|Android#0#|Windows 8#0#|Windows 7#0#]
   * @return type
   */
  function parseListProperty($listProperty) {
    $listProperty = str_replace('&amp;', '[[amp]]', $listProperty);

    $params = explode('&', $listProperty);
    $paramsarr = array();
    foreach($params as $value){
      $value = str_replace('[[amp]]', '&', $value);
      if (stristr($value, '=[')!== FALSE&&$value[strlen($value)-1]==']'&&stristr($value, 'type')!== FALSE
        &&stristr($value, 'value')!== FALSE&&stristr($value, 'product_margin')!== FALSE) {
        $tmp = explode('=[', $value);
        $tmp[1] = '['.$tmp[1];
      } else {
        $tmp = explode('=', $value);
      }      
      $paramsarr[$tmp[0]] = $tmp[1];
    }

    return $paramsarr;
  }

  /**
   * Создает свойства продукта.
   * @param string $key название характеристики.
   * @param string $value значение.
   * @param int $categoryId категория.
   * @param int $productId продукт.
   * @return type
   */
  function createProperty($key, $value, $categoryId, $productId, $type = 'string', $data='') {
    if(empty($key)){
      return false;
    }
    // 0. Очистим продукт от всех ранее имеющихся свойств.
    
    $propertyId = '';
    // 1. Проверяем, наличие такой (название и тип) характеристики у данной категории.
    $res = DB::query(  
      'SELECT * 
        FROM `'.PREFIX.'property`
        LEFT JOIN `'.PREFIX.'category_user_property` as `cup`
          ON `cup`.`property_id`=`'.PREFIX.'property`.`id` 
        WHERE `name` = '.DB::quote($key).' AND `cup`.`property_id`='.DB::quote($categoryId).' 
          AND `'.PREFIX.'property`.`type`='.DB::quote($type)
    );  
    $row = DB::fetchAssoc($res);
    // Если полного соответствия нет, то выбираем характеристику по соответствию названия и типа.
    if (empty($row)) {
      $res = DB::query(  
      'SELECT * 
        FROM `'.PREFIX.'property`
        LEFT JOIN `'.PREFIX.'category_user_property` as `cup`
          ON `cup`.`property_id`=`'.PREFIX.'property`.`id` 
        WHERE `name` = '.DB::quote($key).'
          AND `'.PREFIX.'property`.`type`='.DB::quote($type)
      );  
      $row = DB::fetchAssoc($res);
    }
    if(empty($row)){
  
      // Если нет характеристики до создадим ее.
      DB::query('
       INSERT INTO `'.PREFIX.'property`
         (`name`, `type`,  `activity`, `data`)
       VALUES ('.DB::quote($key).','.DB::quote($type).',1,'.DB::quote($data).')'
      );      
      $propertyId = DB::insertId();
      // Установка  сортировки.
      DB::query(
        'UPDATE `'.PREFIX.'property`
        SET `sort` = '.DB::quote($propertyId).'
        WHERE `id` = '.DB::quote($propertyId)
      );
    } else{
      
      // Если найдена уже характеристика, получаем ее id.
      $propertyId = $row['id'];
     
      // Добавляем привязку, если ее небыло раньше, для действующей категории.
      $res = DB::query('
       SELECT * 
       FROM `'.PREFIX.'category_user_property` 
       WHERE `property_id` = '.DB::quote($propertyId).' 
         AND `category_id` = '.DB::quote($categoryId)
      );
      $rowCup = DB::fetchAssoc($res);
      if(empty($rowCup)){
       DB::query('
         INSERT INTO `'.PREFIX.'category_user_property`
          (`category_id`, `property_id`)
         VALUES ('.DB::quote($categoryId).','.DB::quote($propertyId).')'
       );
      }
     
    }


    // 2. Привязываем к продукту.
    $res = DB::query('
     SELECT * 
     FROM `'.PREFIX.'product_user_property` 
     WHERE `property_id` = '.DB::quote($propertyId).'
       AND `product_id` = '.DB::quote($productId)
    );
    $row = DB::fetchAssoc($res);
    if(empty($row)){
      DB::query('
        INSERT INTO `'.PREFIX.'product_user_property`
         (`product_id`, `property_id`, `value`,`product_margin`)
        VALUES ('.DB::quote($productId).','.DB::quote($propertyId).','.DB::quote($value).','.DB::quote($data).')'
      );
    } else{

      DB::query('
        UPDATE `'.PREFIX.'product_user_property`
        SET `value` = '.DB::quote($value).',
          `product_margin` = '.DB::quote($data).'
        WHERE `product_id` = '.DB::quote($productId).'
          AND `property_id` = '.DB::quote($propertyId)
      );
    }
    // 3. Привязываем к категории.
    $res = DB::query('
     SELECT * 
     FROM `'.PREFIX.'category_user_property` 
     WHERE `property_id` = '.DB::quote($propertyId)
    );
    $row = DB::fetchAssoc($res);
    if(empty($row)){
      // Если нет характеристики до создадим ее.
      DB::query('
     INSERT INTO `'.PREFIX.'category_user_property`
      (`category_id`, `property_id`)
     VALUES ('.DB::quote($categoryId).','.DB::quote($propertyId).')'
      );
    }
  }
  /**
   * Возвращает массив из изображений и seo-настройки к ним - alt и title
   * @param type $listImg пример $listImg = 'noutbuk.png[:param:][alt=ноутбук][title=ноутбук]|noutbuk-Dell-Inspiron-N411Z-oneside.png[:param:][alt=ноутбук черного цвета][title=ноутбук черного цвета]';
   * @return type
   */
  function parseImgSeo($listImg) {
    $images_alt = '';
    $images_title = '';
    $images = explode('|', $listImg);
    foreach ($images as $value) {
      $item = explode('[:param:]', $value);
      $images_url .= $item[0].'|';
      $seo = explode(']', $item[1]);
      $images_alt .= str_replace('[alt=','', $seo[0]).'|';
      $images_title .= str_replace('[title=','', $seo[1]).'|';  
    }
    $result= array (substr($images_url, 0, -1), substr($images_alt, 0, -1), substr($images_title, 0, -1));
    return $result;
  }

}