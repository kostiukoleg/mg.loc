<?php

/**
 * Класс Storage - предназначен для кэширования блоков данных (объектов, массивов, строк), используемых для генерации страницы. Позволяет сохранять работать с сервером memcache.
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Libraries
 */
class Storage{

  static private $_instance = null;
  static private $cacheTime = null;
  static private $sessionLifeTime = null;
  static private $sessionToDB = false;
  static public $noCache = null;
  static public $cacheMode = null;
  static public $memcache_obj = null;
  static public $max_allowed_packet = null;
  static public $cachePrefix = null;

  public function sessionOpen($savePath, $sessionName){
    if (!self::$sessionToDB) {
      global $sess_save_path, $sess_session_name;
      $sess_save_path = $savePath;
      $sess_session_name = $session_name;
    }
    
    return true;
  }

  public function sessionClose(){
    $this->sessionGc(self::$sessionLifeTime);
    DB::close();
    return true;
  }

  public function sessionRead($id){
    if (self::$sessionToDB) {
      //чтение из базы
      $res = DB::query("SELECT `session_data` FROM `".PREFIX."sessions`
                              WHERE `session_id` = ".DB::quote($id));

      if($row = DB::fetchArray($res)){
        return $row[0];
      }
    } else {
      global $sess_save_path, $sess_session_name;
      $sess_file = "$sess_save_path/sess_$id";
      
      if($fp = @fopen($sess_file, "r")){
        $sess_data = fread($fp, filesize($sess_file));
        return($sess_data);
      }
    }
    
    return "";
  }

  public function sessionWrite($id, $sess_data){
    if (isset($_POST['a']) && $_POST['a'] == 'ping') {
      $this->sessionGc(self::$sessionLifeTime);
      return false;
    }
    
    if (self::$sessionToDB) {
      //Запись в базу
      DB::query("
        REPLACE INTO `".PREFIX."sessions` (session_id,session_expires,session_data) 
          VALUES(".DB::quote($id).",UNIX_TIMESTAMP(now()),".DB::quote($sess_data).")");

      if(DB::affectedRows()){
        return $sess_data;
      }
    }

    return(false);
  }

  public function sessionDestroy($id){
    if (self::$sessionToDB) {
      // удаление из базы файла
      DB::query("DELETE FROM ".PREFIX."sessions WHERE session_id = ".DB::quote($id)); 

      if(DB::affectedRows()) {
        return true;
      }
    }
    
    return false; 
  }

  /**
   * Чистильщик мусора
   * @param int life time (sec.)
   * @return bool
   * @see session.gc_divisor      100
   * @see session.gc_maxlifetime 1440
   * @see session.gc_probability    1
   * @usage execution rate 1/100
   *        (session.gc_probability/session.gc_divisor)
   */
  public function sessionGc($maxlifetime){
    if (self::$sessionToDB) {
      DB::query("
      DELETE FROM ".PREFIX."sessions
      WHERE `session_expires`+".$maxlifetime." <= UNIX_TIMESTAMP(now())");

      return DB::affectedRows();
    }
  }
  
  public static function getSessionExpired($id) {
    if (self::$sessionToDB) {
      $res = DB::query("SELECT `session_expires` FROM `".PREFIX."sessions`
                            WHERE `session_id` = ".DB::quote($id));

      if ($row = DB::fetchArray($res)) {
        return $row['session_expires'];
      }
    } else {
      $sess_save_path = session_save_path();
      $sessFile = $sess_save_path."/sess_".$id;
      return filemtime($sessFile);
    }
    
    return "";
  }

  private function __construct(){
    $result = DB::query("
      SELECT `option`, `value`
      FROM `".PREFIX."setting`
      WHERE `option` IN ('cacheObject','cacheMode','cacheTime','cacheHost','cachePort','cachePrefix','sessionToDB','sessionLifeTime')
      ");
    $settings = array();

    while($row = DB::fetchAssoc($result)){
      $settings[$row['option']] = $row['value'];
    }
    
    $sessLifeTime = ini_get("session.gc_maxlifetime");
    self::$sessionLifeTime = (empty($sessLifeTime)) ? 1440 : $sessLifeTime;
    
    if ($settings['sessionToDB']=='true') {
      self::$sessionToDB = true;
      self::$sessionLifeTime = ($settings['sessionLifeTime'] < 1440) ? 1440 : 
          $settings['sessionLifeTime'];
      session_set_save_handler(
        array($this, "sessionOpen"), 
        array($this, "sessionClose"), 
        array($this, "sessionRead"),
        array($this, "sessionWrite"), 
        array($this, "sessionDestroy"), 
        array($this, "sessionGc")
      );
    } else if (isset($_POST['a']) && $_POST['a'] == 'ping') {
      session_set_save_handler(
        array($this, "sessionOpen"), 
        array($this, "sessionClose"), 
        array($this, "sessionRead"),
        array($this, "sessionWrite"), 
        array($this, "sessionDestroy"), 
        array($this, "sessionGc")
      );
    }

    if($settings['cacheObject']=='true'){
      define(CACHE, true);
    }else{
      define(CACHE, false);
    }
    if($cacheMode = $settings['cacheMode']){
      define(CACHE_MODE, $cacheMode);
    };
    if($cacheTime = $settings['cacheTime']){
      define(CACHE_TIME, $cacheTime);
    };
    if($cacheHost = $settings['cacheHost']){
      define(CACHE_HOST, $cacheHost);
    };
    if($cachePort = $settings['cachePort']){
      define(CACHE_PORT, $cachePort);
    };
    if($cachePrefix = $settings['cachePrefix']){
      define(CACHE_PREFIX, $cachePrefix);
    }


    self::$noCache = !CACHE;
    self::$cacheMode = CACHE_MODE; // DB or FILE or MEMCACHE
    self::$cacheTime = CACHE_TIME;
    self::$cachePrefix = (CACHE_PREFIX=="CACHE_PREFIX")?'':CACHE_PREFIX;

    if(self::$cacheMode=='MEMCACHE'){
      if(class_exists('Memcache')){
        self::$memcache_obj = new Memcache;
        self::$memcache_obj->connect(CACHE_HOST, CACHE_PORT) or die('Ошибка подключения к серверу MEMCACHE');
      }
    }

    if(self::$cacheMode=='DB'){
      $result = DB::query("SHOW VARIABLES LIKE 'max_allowed_packet' ");
      if($row = DB::fetchAssoc($result)){
        self::$max_allowed_packet = $row['Value'];
      }
    }
  }

  private function __clone(){
    
  }

  private function __wakeup(){
    
  }

  /**
   * Возвращает единственный экземпляр данного класса.
   * @return obj объект класса Storage
   */
  static public function getInstance(){
    if(is_null(self::$_instance)){
      self::$_instance = new self;
    }
    return self::$_instance;
  }

  /**
   * Инициализирует единственный объект данного класса.
   * @return obj объект класса Storage
   */
  public static function init(){
    self::getInstance();
  }

  /**
   * Сохраняет данные в формате ключ-значение.
   * @param string $name ключ
   * @param string $value значение
   * @return boolean true или false
   */
  public static function save($name, $value){
    if(self::$noCache){
      return false;
    }

    if(is_array($value)||is_string($value)){

      if(self::$cacheMode=='FILE'){
        if(function_exists('apc_add')){
          apc_add(self::$dirCache.$name, addslashes(serialize($value)));
          return true;
        }
      }

      if(self::$cacheMode=='DB'){
        $cacheArray = array(
          'date_add'=>time(), // 20 минут 
          'lifetime'=>time()+self::$cacheTime, // 20 минут 
          'name'=>$name,
          'value'=>addslashes(serialize($value)),
        );

        $sql = '
          INSERT INTO `'.PREFIX.'cache` SET '.DB::buildPartQuery($cacheArray).'
          ON DUPLICATE KEY UPDATE 
            lifetime = '.$cacheArray['lifetime'].',
            value = "'.$cacheArray['value'].'"';

        if((strlen($sql)+1024)<self::$max_allowed_packet){
          DB::query($sql);
        }else{
          echo "<div style='padding: 10px;color: #A94442;border: 1px solid #EBCCD1;background: #F2DEDE; font-size: 14px;position:fixed; left: 10px;right: 10px;bottom: 10px; z-index: 111;border-radius: 3px;line-height: 21px;'>Значение директивы <strong>max_allowed_packet = ".self::$max_allowed_packet."</strong> на вашем MySQL слишком мало! Кеширование в базу невозможно! Для устранения ошибки увеличьте <strong>max_allowed_packet</strong> или используйте тип кеширования <strong>memcache</strong> (рекомендуется)</div>";
        }
      }

      if(self::$cacheMode=='MEMCACHE'){
        if(class_exists('Memcache')){
          self::$memcache_obj->set(self::$cachePrefix.$name, $value, MEMCACHE_COMPRESSED, self::$cacheTime);
        }
      }
    }else{
      //echo 'Ошибка: невозможно создать кэш объекта!';
      return false;
    }

    return true;
  }

  /**
   * Возвращает сохраненный ранее объект из кэша.
   * @param string $name ключ.
   * @return obj закэшированное представление объекта или false.
   */
  public static function get($name){

    if(self::$noCache){
      return null;
    }

    if(self::$cacheMode=='FILE'){

      if(function_exists('apc_fetch')){
        apc_fetch($name);
        return apc_fetch($name);
      }
    }

    if(self::$cacheMode=='MEMCACHE'){
      if(class_exists('Memcache')){
        return self::$memcache_obj->get(self::$cachePrefix.$name);
      }
    }

    if(self::$cacheMode=='DB'){
      $result = DB::query('
        SELECT `value` 
        FROM `'.PREFIX.'cache`
        WHERE name='.DB::quote($name)."
        AND `lifetime` >= ".time());

      if($row = DB::fetchAssoc($result)){
        $res = unserialize(stripslashes($row['value']));
        return $res;
      }
    }
    return null;
  }

  /**
   * Очищает кэш для всех объектов.
   * @param boolean true
   */
  public static function clear(){

    if(self::$cacheMode=='FILE'){
      if(function_exists('apc_clear_cache')){
        apc_clear_cache();
      }
    }

    if(self::$cacheMode=='MEMCACHE'){
      if(class_exists('Memcache')){
        self::$memcache_obj->flush();
      }
    }

    if(self::$cacheMode=='DB'){
      $result = DB::query("
      UPDATE  `".PREFIX."cache`
      SET  `value` =  '' ");
    }

    // вместе с кэшем блоков, скидываем и кеш стилей с js.
    MG::clearMergeStaticFile(PATH_TEMPLATE.'/cache/');

    return true;
  }

  /**
   * Закрывает соединение с сервером memcache.
   */
  public static function close(){

    if(self::$cacheMode=='MEMCACHE'){
      self::$memcache_obj->close();
    }
    return true;
  }
  
  public static function getSessionLifeTime() {
    return self::$sessionLifeTime;
  }
}
