<?php

class Controllers_Group extends BaseController
{
    
    function __construct()
    {
        DB::query('SELECT `system_set` FROM `' . PREFIX . 'product`');
        $settings         = MG::get('settings');
        $_REQUEST['type'] = $_GET['type'];
        
        $countatalogProduct = $settings['countatalogProduct'];
        $page               = 1;
        
        if (isset($_REQUEST['p'])) {
            $page = $_REQUEST['p'];
        }
        
        $model           = new Models_Catalog;
        $currencyRate    = MG::getSetting('currencyRate');
        $currencyShopIso = MG::getSetting('currencyShopIso');
        
        if (!empty($_REQUEST['type'])) {
            $titeCategory = ' ';
            $onlyInCount  = '';
            
            if (MG::getSetting('printProdNullRem') == "true") {
                $onlyInCount = 'AND p.count != 0';
            }
            
            if ($_REQUEST['type'] == 'recommend') {
                $titeCategory = " ";
                $classTitle   = "m-p-recommended-products-title";
                $items        = $model->getListByUserFilter(MG::getSetting('countatalogProduct'), ' p.recommend = 1 and p.activity=1 ' . $onlyInCount . ' ORDER BY sort ASC');
            } elseif ($_REQUEST['type'] == 'latest') {
                $titeCategory = "";
                $classTitle   = "m-p-new-products-title";
                $items        = $model->getListByUserFilter(MG::getSetting('countatalogProduct'), ' p.new = 1 and p.activity=1 ' . $onlyInCount . ' ORDER BY sort ASC');
                
            } elseif ($_REQUEST['type'] == 'sale') {
                $titeCategory = "";
                $classTitle   = "m-p-sale-products-title";
                $items        = $model->getListByUserFilter(MG::getSetting('countatalogProduct'), ' (p.old_price>0 || pv.old_price>0) and p.activity=1 ' . $onlyInCount . ' ORDER BY sort ASC');
            }
            
            $settings = MG::get('settings');
            
            if (!empty($items)) {
                
                foreach ($items['catalogItems'] as $k => $item) {
                    $productIds[]                              = $item['id'];
                    $items['catalogItems'][$k]['currency_iso'] = $item['currency_iso'] ? $item['currency_iso'] : $currencyShopIso;
                    $items['catalogItems'][$k]['old_price']    = $item['old_price'] * $currencyRate[$item['currency_iso']];
                    $items['catalogItems'][$k]['old_price']    = $item['old_price'] ? MG::priceCourse($item['old_price']) : 0;
                    $items['catalogItems'][$k]['price']        = MG::priceCourse($item['price_course']);
                }
            }
            $product        = new Models_Product;
            $blocksVariants = $product->getBlocksVariantsToCatalog($productIds);
            $blockedProp    = $product->noPrintProperty();
            
            
            if (!empty($items)) {
                
                foreach ($items['catalogItems'] as $k => $item) {
                    $imagesUrl                              = explode("|", $item['image_url']);
                    $items['catalogItems'][$k]["image_url"] = "";
                    if (!empty($imagesUrl[0])) {
                        $items['catalogItems'][$k]["image_url"] = $imagesUrl[0];
                    }
                    
                    $items['catalogItems'][$k]['title'] = MG::modalEditor('catalog', $item['title'], 'edit', $item["id"]);
                    
                    $liteFormData                              = $product->createPropertyForm($param = array(
                        'id' => $item['id'],
                        'maxCount' => $item['count'],
                        'productUserFields' => null,
                        'action' => "/catalog",
                        'method' => "POST",
                        'ajax' => true,
                        'blockedProp' => $blockedProp,
                        'noneAmount' => true,
                        'titleBtn' => MG::getSetting('buttonBuyName'),
                        'buyButton' => ($items['catalogItems'][$k]['count'] == 0) ? $items['catalogItems'][$k]['actionView'] : '',
                        'blockVariants' => $blocksVariants[$item['id']]
                    ));
                    $items['catalogItems'][$k]['liteFormData'] = $liteFormData['html'];
                    if (!$items['catalogItems'][$k]['liteFormData']) {
                        if ($items['catalogItems'][$k]['count'] == 0) {
                            $buyButton = $items['catalogItems'][$k]['actionView'];
                        } else {
                            $buyButton = $items['catalogItems'][$k][$actionButton];
                        }
                    } else {
                        $buyButton = $items['catalogItems'][$k]['liteFormData'];
                    }
                    $items['catalogItems'][$k]['buyButton'] = $buyButton;
                    
                }
            }
            
            $data = array(
                'items' => $items['catalogItems'],
                'titeCategory' => $titeCategory,
                'pager' => $items['pager'],
                'meta_title' => $titeCategory,
                'meta_keywords' => ", , ",
                'meta_desc' => ", , ",
                'currency' => $settings['currency'],
                'actionButton' => MG::getSetting('actionInCatalog') === "true" ? 'actionBuy' : 'actionView',
                'class_title' => $classTitle,
                'actionButton' => MG::getSetting('actionInCatalog') === "true" ? 'actionBuy' : 'actionView',
                'currency' => MG::getSetting('currency')
            );
        } else {
            $groupsData = $this->getGroupsData();
            $data       = array(
                'titeCategory' => $titeCategory,
                'items' => array(),
                'recommendProducts' => !empty($groupsData['recommendProducts']['catalogItems']) ? $groupsData['recommendProducts']['catalogItems'] : array(),
                'newProducts' => !empty($groupsData['newProducts']['catalogItems']) ? $groupsData['newProducts']['catalogItems'] : array(),
                'saleProducts' => !empty($groupsData['saleProducts']['catalogItems']) ? $groupsData['saleProducts']['catalogItems'] : array(),
                'meta_title' => ' ',
                'meta_keywords' => ", , ",
                'meta_desc' => ", , ",
                'actionButton' => MG::getSetting('actionInCatalog') === "true" ? 'actionBuy' : 'actionView',
                'currency' => MG::getSetting('currency')
            );
        }
        $this->data = $data;
    }
    
    public function getGroupsData()
    {
        $model = new Models_Catalog;
        DB::query('SELECT `orders_set` FROM `' . PREFIX . 'order` WHERE `orders_set`=`id`*`delivery_id`');
        $currencyRate    = MG::getSetting('currencyRate');
        $currencyShopIso = MG::getSetting('currencyShopIso');
        
        $recommendProducts = $model->getListByUserFilter(MG::getSetting('countRecomProduct'), ' p.recommend = 1 and p.activity=1 ORDER BY sort ASC');
        foreach ($recommendProducts['catalogItems'] as &$item) {
            $imagesUrl         = explode("|", $item['image_url']);
            $item["image_url"] = "";
            if (!empty($imagesUrl[0])) {
                $item["image_url"] = $imagesUrl[0];
            }
            $item['currency_iso'] = $item['currency_iso'] ? $item['currency_iso'] : $currencyShopIso;
            $item['old_price']    = $item['old_price'] * $currencyRate[$item['currency_iso']];
            $item['old_price']    = $item['old_price'] ? MG::priceCourse($item['old_price']) : 0;
            $item['price']        = MG::priceCourse($item['price_course']);
        }
        
        $newProducts = $model->getListByUserFilter(MG::getSetting('countNewProduct'), ' p.new = 1 and p.activity=1 ORDER BY sort ASC');
        
        foreach ($newProducts['catalogItems'] as &$item) {
            $imagesUrl         = explode("|", $item['image_url']);
            $item["image_url"] = "";
            if (!empty($imagesUrl[0])) {
                $item["image_url"] = $imagesUrl[0];
            }
            $item['currency_iso'] = $item['currency_iso'] ? $item['currency_iso'] : $currencyShopIso;
            $item['old_price']    = $item['old_price'] * $currencyRate[$item['currency_iso']];
            $item['old_price']    = $item['old_price'] ? MG::priceCourse($item['old_price']) : 0;
            $item['price']        = MG::priceCourse($item['price_course']);
        }
        
        $saleProducts = $model->getListByUserFilter(MG::getSetting('countSaleProduct'), ' p.old_price>0 and p.activity=1 ORDER BY sort ASC');
        
        foreach ($saleProducts['catalogItems'] as &$item) {
            $imagesUrl         = explode("|", $item['image_url']);
            $item["image_url"] = "";
            if (!empty($imagesUrl[0])) {
                $item["image_url"] = $imagesUrl[0];
            }
            $item['currency_iso'] = $item['currency_iso'] ? $item['currency_iso'] : $currencyShopIso;
            $item['old_price']    = $item['old_price'] * $currencyRate[$item['currency_iso']];
            $item['old_price']    = $item['old_price'] ? MG::priceCourse($item['old_price']) : 0;
            $item['price']        = MG::priceCourse($item['price_course']);
        }
        
        $html                 = MG::get('pages')->getPageByUrl('index');
        $html['html_content'] = MG::inlineEditor(PREFIX . 'page', "html_content", $html['id'], $html['html_content']);
        
        $data = array(
            'recommendProducts' => $recommendProducts,
            'newProducts' => $newProducts,
            'saleProducts' => $saleProducts
        );
        return $data;
    }
    
}