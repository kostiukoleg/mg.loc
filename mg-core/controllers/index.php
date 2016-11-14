<?php

class Controllers_Index extends BaseController
{
    
    function __construct()
    {
        $settings                    = MG::get('settings');
        $_REQUEST['category_id']     = URL::getQueryParametr('category_id');
        $_REQUEST['inCartProductId'] = intval($_REQUEST['inCartProductId']);
        
        if (!empty($_REQUEST['inCartProductId'])) {
            $cart     = new Models_Cart;
            $property = $cart->createProperty($_POST);
            $cart->addToCart($_REQUEST['inCartProductId'], $_REQUEST['amount_input'], $property);
            SmalCart::setCartData();
            MG::redirect('/cart');
        }
        
        $countatalogProduct = $settings['countatalogProduct'];
        $page               = 1;
        
        if (isset($_REQUEST['p'])) {
            $page = $_REQUEST['p'];
        }
        
        $model = new Models_Catalog;
        
        $model->categoryId = MG::get('category')->getCategoryList($_REQUEST['category_id']);
        
        $model->categoryId[] = $_REQUEST['category_id'];
        
        $countatalogProduct = 100;
        if (MG::getSetting('mainPageIsCatalog') == 'true') {
            $printCompareButton = MG::getSetting('printCompareButton');
            $actionButton       = MG::getSetting('actionInCatalog') === "true" ? 'actionBuy' : 'actionView';
            $dataGroupProducts  = Storage::get(md5('dataGroupProductsIndexConroller'));
            
            $currencyRate    = MG::getSetting('currencyRate');
            $currencyShopIso = MG::getSetting('currencyShopIso');
            $randomProdBlock = MG::getSetting('randomProdBlock') == "true" ? true : false;
            
            if ($dataGroupProducts == null) {
                $onlyInCount = '';
                
                if (MG::getSetting('printProdNullRem') == "true") {
                    $onlyInCount = 'AND p.count != 0';
                }
                DB::query('SELECT `system_set` FROM `' . PREFIX . 'product`');
                $sort              = $randomProdBlock ? "RAND()" : "sort";
                $recommendProducts = $model->getListByUserFilter(MG::getSetting('countRecomProduct'), ' p.recommend = 1 and p.activity=1 ' . $onlyInCount . ' ORDER BY ' . $sort . ' ASC');
                foreach ($recommendProducts['catalogItems'] as &$item) {
                    $imagesUrl         = explode("|", $item['image_url']);
                    $item["image_url"] = "";
                    if (!empty($imagesUrl[0])) {
                        $item["image_url"] = $imagesUrl[0];
                    }
                    $item['currency_iso'] = $item['currency_iso'] ? $item['currency_iso'] : $currencyShopIso;
                    $item['old_price']    = $item['old_price'] ? MG::priceCourse($item['old_price']) : 0;
                    $item['price']        = MG::priceCourse($item['price_course']);
                    if ($printCompareButton != 'true') {
                        $item['actionCompare'] = '';
                    }
                    if ($actionButton == 'actionBuy' && $item['count'] == 0) {
                        $item['actionBuy'] = $item['actionView'];
                    }
                }
                
                $newProducts = $model->getListByUserFilter(MG::getSetting('countNewProduct'), ' p.new = 1 and p.activity=1 ' . $onlyInCount . ' ORDER BY ' . $sort . ' ASC');
                
                foreach ($newProducts['catalogItems'] as &$item) {
                    $imagesUrl         = explode("|", $item['image_url']);
                    $item["image_url"] = "";
                    if (!empty($imagesUrl[0])) {
                        $item["image_url"] = $imagesUrl[0];
                    }
                    $item['currency_iso'] = $item['currency_iso'] ? $item['currency_iso'] : $currencyShopIso;
                    $item['old_price']    = $item['old_price'] ? MG::priceCourse($item['old_price']) : 0;
                    $item['price']        = MG::priceCourse($item['price_course']);
                    if ($printCompareButton != 'true') {
                        $item['actionCompare'] = '';
                    }
                    if ($actionButton == 'actionBuy' && $item['count'] == 0) {
                        $item['actionBuy'] = $item['actionView'];
                    }
                }
                
                $saleProducts = $model->getListByUserFilter(MG::getSetting('countSaleProduct'), ' (p.old_price>0 || pv.old_price>0) and p.activity=1 ' . $onlyInCount . ' ORDER BY ' . $sort . ' ASC');
                
                foreach ($saleProducts['catalogItems'] as &$item) {
                    $imagesUrl         = explode("|", $item['image_url']);
                    $item["image_url"] = "";
                    if (!empty($imagesUrl[0])) {
                        $item["image_url"] = $imagesUrl[0];
                    }
                    $item['currency_iso'] = $item['currency_iso'] ? $item['currency_iso'] : $currencyShopIso;
                    $item['old_price']    = $item['old_price'] ? MG::priceCourse($item['old_price']) : 0;
                    $item['price']        = MG::priceCourse($item['price_course']);
                    if ($printCompareButton != 'true') {
                        $item['actionCompare'] = '';
                    }
                    if ($actionButton == 'actionBuy' && $item['count'] == 0) {
                        $item['actionBuy'] = $item['actionView'];
                    }
                    
                }
                
                $dataGroupProducts['recommendProducts'] = $recommendProducts;
                $dataGroupProducts['newProducts']       = $newProducts;
                $dataGroupProducts['saleProducts']      = $saleProducts;
                Storage::save(md5('dataGroupProductsIndexConroller'), $dataGroupProducts);
            }
            
            $recommendProducts = $dataGroupProducts['recommendProducts'];
            $newProducts       = $dataGroupProducts['newProducts'];
            $saleProducts      = $dataGroupProducts['saleProducts'];
        }
        $html = MG::get('pages')->getPageByUrl('index');
        
        if (!empty($html)) {
            $html['html_content'] = MG::inlineEditor(PREFIX . 'page', "html_content", $html['id'], $html['html_content']);
        } else {
            $html['html_content'] = '';
        }
        $this->data = array(
            'recommendProducts' => !empty($recommendProducts['catalogItems']) && MG::getSetting('countRecomProduct') ? $recommendProducts['catalogItems'] : array(),
            'newProducts' => !empty($newProducts['catalogItems']) && MG::getSetting('countNewProduct') ? $newProducts['catalogItems'] : array(),
            'saleProducts' => !empty($saleProducts['catalogItems']) && MG::getSetting('countSaleProduct') ? $saleProducts['catalogItems'] : array(),
            'titeCategory' => $html['meta_title'],
            'cat_desc' => $html['html_content'],
            'meta_title' => $html['meta_title'],
            'meta_keywords' => $html['meta_keywords'],
            'meta_desc' => $html['meta_desc'],
            'currency' => $settings['currency'],
            'actionButton' => $actionButton
        );
    }
    
}