<?php

function mgAddAction($hookName, $userFunction, $countArg = 0, $priority = 10){
  MG::addAction($hookName, $userFunction, $countArg, $priority);
}

function mgAddCustomPriceAction($userFunction, $priority = 10){
  MG::addPriceCustomFunction($userFunction, $priority);
}

function mgAddShortcode($shortcode, $userFunction){
  MG::addShortcode($shortcode, $userFunction);
}

function mgPageThisPlugin($plugin, $userFunction){
  MG::addAction($plugin, $userFunction);
}

function mgActivateThisPlugin($dirPlugin, $userFunction){
  MG::activateThisPlugin($dirPlugin, $userFunction);
}

function mgDeactivateThisPlugin($dirPlugin, $userFunction){
  MG::deactivateThisPlugin($dirPlugin, $userFunction);
}

function mgCreateHook($hookName){
  MG::createHook($hookName);
}

function mgAddMeta($data, $onlyController = 'all'){
  if (stristr($data,'mg-core/script/zoomsl-3.0.js')!==FALSE && MG::getSetting('connectZoom')=='false'){
    return false;
  }
  if (stristr($data,'mg-core/script/jquery.maskedinput.min.js')!==FALSE && MG::getSetting('usePhoneMask')=='false'){
    return false;
  }
  $register = MG::get('register')?MG::get('register'):array();

  if($onlyController!='all'){
    $onlyController = 'controllers_'.$onlyController;
  }
  if(!empty($register[$onlyController])){
    if(!in_array($data, $register[$onlyController])){
      $register[$onlyController][] = $data;
    }
  }
  else{
    $register[$onlyController][] = $data;
  }

  MG::set('register', $register);
  MG::set('userMeta', MG::get('userMeta')."".$data);
}

function setOption($data){
  //     : setOption('option', 'value');
  if(func_num_args()==2){
    $arg = func_get_args();
    $data = array();
    $data['option'] = $arg[0];
    $data['value'] = $arg[1];
  }
  MG::setOption($data);
}

function getOption($option, $data = false){
  return MG::getOption($option, $data);
}

function mgMenu(){
  echo MG::getMenu();
}

function mgMenuFull($type = 'top'){
  echo MG::getMenu($type);
}

function mgGetCart(){
  return MG::getSmalCart();
}

function mgMeta(){
  echo '[mg-meta]';
  mgAddShortcode('mg-meta', 'mgMetaInsert');
}

function mgMetaInsert(){
  return MG::meta();
}

function mgSEO($data){
  MG::seoMeta($data);
}

function mgTitle($title){
  MG::titlePage($title);
}

function viewData($data){
  echo "<pre>";
  echo htmlspecialchars(print_r($data, true));
  echo "</pre>";
}

function mgDeclensionNum($number, $titles){
  return MG::declensionNum($number, $titles);
}

function isStaticPage(){
  return MG::get('isStaticPage');
}

function mgSmallCartBlock($data){
  echo MG::layoutManager('layout_cart', $data);
}

function mgSearchBlock(){
  echo MG::layoutManager('layout_search', null);
}

function mgContactBlock(){
  echo MG::layoutManager('layout_contacts', null);
}

function mgImageProduct($data){
  $product = new Models_Product();
  $data["image_url"] = basename($data["image_url"]);
  $imagesData = $product->imagesConctruction($data["image_url"], $data["image_title"], $data["image_alt"], $data['id']);  
  $src = SITE."/uploads/no-img.jpg";
  $dir = floor($data["id"]/100).'00';
  $imagesData["image_url"] = basename($imagesData["image_url"]);
	$srcLarge = mgImageProductPath($data["image_url"], $data["id"]);
  if(file_exists(URL::$documentRoot.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'product'.DIRECTORY_SEPARATOR.$dir.DIRECTORY_SEPARATOR.$data["id"].DIRECTORY_SEPARATOR.'thumbs'.DIRECTORY_SEPARATOR.'70_'.$imagesData["image_url"])){
    $src = SITE.'/uploads/product/'.$dir.'/'.$data['id'].'/thumbs/70_'.$imagesData["image_url"];
  }elseif(file_exists(URL::$documentRoot.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'thumbs'.DIRECTORY_SEPARATOR.'70_'.$imagesData["image_url"])){
    $src = SITE.'/uploads/thumbs/70_'.$imagesData["image_url"];
  }
       
  return '<img class="mg-product-image" itemprop="logo" data-transfer="true" data-product-id="'.$data["id"].'" src="'.$src.'" alt="'.$imagesData["image_alt"].'" title="'.$imagesData["image_title"].'" data-magnify-src="'.$srcLarge.'">';
}

function mgImageProductPath($image, $productId, $size = 'orig'){
  $src = SITE.'/uploads/no-img.jpg';
  
  if(empty($image)){
    return $src;
  }
  
  $image = basename($image);
  
  if(strpos($image, '30_') === 0 || strpos($image, '70_') === 0){
    $image = str_replace(array('30_', '70_'), '', $image);
  }
  
  $dir = floor($productId/100).'00';
  $ds = DIRECTORY_SEPARATOR;
  $prefix = '';
  
  if($size == 'small'){
    $prefix = '30_';
  }elseif($size == 'big'){
    $prefix = '70_';
  }
  
  if(empty($size) || $size == 'orig'){
    if(file_exists(URL::$documentRoot.$ds.'uploads'.$ds.'product'.$ds.$dir.$ds.$productId.$ds.$image)){
      $src = SITE.'/uploads/product/'.$dir.'/'.$productId.'/'.$image;
    }elseif(file_exists(URL::$documentRoot.$ds.'uploads'.$ds.$image)){
      $src = SITE.'/uploads/'.$image;
    }
  }else{
    //  5.7.0.            .
    if(file_exists(URL::$documentRoot.$ds.'uploads'.$ds.'product'.$ds.$dir.$ds.$productId.$ds.'thumbs'.$ds.$prefix.$image)){
      $src = SITE.'/uploads/product/'.$dir.'/'.$productId.'/thumbs/'.$prefix.$image;
    }elseif(file_exists(URL::$documentRoot.$ds.'uploads'.$ds.'thumbs'.$ds.$prefix.$image)){
      $src = SITE.'/uploads/thumbs/'.$prefix.$image;
    }
  }
  
  return $src;
}

function mgSubCategory($catId){
  $data = MG::get('category')->getHierarchyCategory($catId, true);
  echo MG::layoutManager('layout_subcategory', $data);
}

function mgGalleryProduct($data){
  echo MG::layoutManager('layout_images', $data);
}

function mgLogo($alt = '', $title = '', $style = ''){
  if(!$title&&!$alt){
    $title = MG::getSetting('shopName');
    $alt = $title;
  }
  $logo = (MG::getSetting('shopLogo')!='')?MG::getSetting('shopLogo'):"/mg-templates/".MG::getSetting('templateName')."/images/logo.png";

  return '<img src='.SITE.$logo.' alt="'.htmlspecialchars($alt).
    '" title="'.htmlspecialchars($title).'" '.$style.'>';
}

function layout($layout, $data = null){
  if(in_array($layout, array('cart', 'auth', 'contacts', 'search'))){
    $data = MG::get('templateData');
  }

  if($layout=='topmenu'){
    echo Menu::getMenuFull('top');
    return true;
  }

  if($layout=='leftmenu'){
    echo MG::get('category')->getCategoriesHTML();
    return true;
  }

  if($layout=='horizontmenu'){
    echo MG::get('category')->getCategoriesHorHTML();
    return true;
  }

  if($layout=='content'){
    $data = MG::get('templateData');
    echo $data['content'];
    return true;
  }

  if($layout=='widget'){
    echo MG::getSetting('widgetCode');
    return true;
  }

  if($layout=='logo'){
    $logo = (MG::getSetting('shopLogo')!='')?MG::getSetting('shopLogo'):"/mg-templates/".MG::getSetting('templateName')."/images/logo.png";
    echo '<img src="'.SITE.$logo.'" alt="">';
    return true;
  }

  echo MG::layoutManager('layout_'.$layout, $data);
  return true;
}

function priceFormat($number){
  return $number;
}

function filterCatalog($userStyle = false){
  if(!$userStyle){
    if(MG::get('controller')=='controllers_catalog'){
      mgAddMeta('<link type="text/css" href="'.SCRIPT.'standard/css/jquery.ui.slider.css" rel="stylesheet"/>');
      mgAddMeta('<link type="text/css" href="'.SCRIPT.'standard/css/filter.css" rel="stylesheet"/>');
      mgAddMeta('<script type="text/javascript" src="'.SCRIPT.'standard/js/filter.js"></script>');
    }
  }
  echo MG::get('catalogfilter');
}

function copyrightMoguta(){
  $html = '';
  if (MG::getSetting('copyrightMoguta')=='true') { 
    $html = '<div class="powered">    : 
      <a href="http://moguta.ru" target="_blank">
      <img src="'.PATH_SITE_TEMPLATE.'/images/footer-logo.png" 
      alt="Moguta.CMS -  !" title="Moguta -  CMS  -!"></a></div>';
  }
  echo $html;
}

function backgroundSite() {
  
  $backgr = (MG::getSetting('backgroundSite')!='')? SITE.MG::getSetting('backgroundSite'): '';
  if ($backgr) {
    $html = 'style="background: url('.SITE.(MG::getSetting('backgroundSite')).') no-repeat fixed center center /100% auto #fff;" ';
  }
  echo $html;
}

function isIndex() {
  return (MG::get('controller') == 'controllers_index') ? true: false;
}

function isCatalog() {
  return (MG::get('controller') == 'controllers_catalog') ? true: false;
}

function isCart() {
  return (MG::get('controller') == 'controllers_cart') ? true: false;
}

function isOrder() {
  return (MG::get('controller') == 'controllers_order') ? true: false;
}

function isSearch() {
  return !empty($_GET['search']) ? true: false;
}

function horizontMenu() {
  if (MG::getSetting('horizontMenu') == "true"){
    return layout('horizontmenu');
  }
  return false;
}

function horizontMenuDisable() {
  if (MG::getSetting('horizontMenu') == "false"){
    return true;
  }
  return false;
}

function catalogToIndex() {
  if (MG::getSetting('catalogIndex') == 'true'){
    return true;
  }
  return false;
}

function mgGetPaymentRateTitle($rate){
  $rateTitle = '';
  
  if(!empty($rate)){
    $paymentRate = (abs($rate)*100).'%';

    if($rate > 0){
      $rateTitle .= ' ( '.$paymentRate.')';
    }else{
      $rateTitle .= ' ( '.$paymentRate.')';
    }        
  }
  return $rateTitle;
}