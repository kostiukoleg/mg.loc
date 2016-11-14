<?php

class Controllers_Catalog extends BaseController
{
    
    function __construct()
    {
        $settings                    = MG::get('settings');
        $lang                        = MG::get('lang');
        $_REQUEST['category_id']     = URL::getQueryParametr('category_id');
        $_REQUEST['inCartProductId'] = intval($_REQUEST['inCartProductId']);
        
        if (!empty($_REQUEST['inCartProductId'])) {
            $cart = new Models_Cart;
            if (empty($_POST) || (isset($_POST['updateCart']) && isset($_POST['inCartProductId']) && count($_POST) == 2)) {
                
                $modelProduct = new Models_Product;
                $product      = $modelProduct->getProduct($_REQUEST['inCartProductId']);
                
                if (empty($product)) {
                    MG::redirect('/404');
                    exit;
                }
                
                $blockVariants = $modelProduct->getBlockVariants($product['id']);
                $blockedProp   = $modelProduct->noPrintProperty();
                
                $propertyFormData = $modelProduct->createPropertyForm($param = array(
                    'id' => $product['id'],
                    'maxCount' => $product['count'],
                    'productUserFields' => $product['thisUserFields'],
                    'action' => "/catalog",
                    'method' => "POST",
                    'ajax' => true,
                    'blockedProp' => $blockedProp,
                    'noneAmount' => false,
                    'titleBtn' => MG::getSetting('buttonBuyName'),
                    'blockVariants' => $blockVariants,
                    'currency_iso' => $product['currency_iso']
                ));
                
                $_POST                    = $propertyFormData['defaultSet'];
                $_POST['inCartProductId'] = $product['id'];
            }
            
            $property = $cart->createProperty($_POST);
            
            $cart->addToCart($_REQUEST['inCartProductId'], $_REQUEST['amount_input'], $property);
            
            SmalCart::setCartData();
            MG::redirect('/cart');
        }
        
        if (!empty($_REQUEST['fastsearch'])) {
            $this->getSearchData();
        }
        
        $countatalogProduct = $settings['countatalogProduct'];
        $page               = 1;
        
        if (isset($_REQUEST['p'])) {
            $page = $_REQUEST['p'];
        }
        
        $model = new Models_Catalog;
        
        $number = URL::getQueryParametr('cnt');
        
        if (!$number) {
            if (isset($_SESSION["cnt"])) {
                $number = $_SESSION["cnt"];
            } else {
                $number          = $settings['countatalogProduct'];
                $_SESSION["cnt"] = $number;
            }
        } else {
            $_SESSION["cnt"] = $number;
        }
        
        if ($number == "all")
            $number = 10000000;
        
        $filters = URL::getQueryParametr('filters');
        
        if (empty($filters) && !isset($_REQUEST["sorter"])) {
            $filter  = explode("|", MG::getSetting('filterSort'));
            $filters = $filter[0] . "|";
            
            if ($filter[1] == "asc") {
                $filters .= "-1";
            } else {
                $filters .= "1";
            }
        }
        
        if (!$filters) {
            if (isset($_SESSION["filters"]) && !isset($_REQUEST["sorter"])) {
                $filters = $_SESSION["filters"];
            } elseif (isset($_REQUEST["sorter"])) {
                $filters             = $_REQUEST["sorter"];
                $_SESSION["filters"] = $filters;
            }
        } else {
            $_SESSION["filters"] = $filters;
        }
        
        $keyword = MG::defenderXss_decode(urldecode(URL::getQueryParametr('search')));
        
        if (!empty($keyword)) {
            $keyword    = $this->convertLang($keyword);
            $items      = $model->getListProductByKeyWord($keyword, false, true, false, 'groupBy');
            $searchData = array(
                'keyword' => URL::getQueryParametr('search'),
                'count' => $items['numRows']
            );
        } else {
            $_REQUEST['category_id'] = intval($_REQUEST['category_id']);
            if (empty($_REQUEST['category_id'])) {
                $_REQUEST['category_id'] = 0;
            }
            
            $model->categoryId = MG::get('category')->getCategoryList($_REQUEST['category_id']);
            
            $model->categoryId[] = $_REQUEST['category_id'];
            
            $_REQUEST['category_ids'] = $model->categoryId;
            if (!$number) {
                $countatalogProduct = $settings['countatalogProduct'];
                $number             = $countatalogProduct;
            }
            $items = $model->getList($settings['countatalogProduct'], false, true);
        }
        if (!empty($_REQUEST['getcount']) && !empty($_REQUEST['filter'])) {
            echo $items['totalCountItems'] ? $items['totalCountItems'] : 0;
            exit();
        }
        
        $settings = MG::get('settings');
        if (empty($items['catalogItems'])) {
            $items['catalogItems'] = array();
        } else {
            foreach ($items['catalogItems'] as $item) {
                if ($item['id']) {
                    $productIds[] = $item['id'];
                }
            }
            
            $product        = new Models_Product;
            $blocksVariants = empty($productIds) ? null : $product->getBlocksVariantsToCatalog($productIds);
            
            $blockedProp  = $product->noPrintProperty();
            $actionButton = MG::getSetting('actionInCatalog') === "true" ? 'actionBuy' : 'actionView';
            
            foreach ($items['catalogItems'] as $k => $item) {
                $imagesUrl                              = explode("|", $item['image_url']);
                $items['catalogItems'][$k]["image_url"] = "";
                
                if (!empty($imagesUrl[0])) {
                    $items['catalogItems'][$k]["image_url"] = $imagesUrl[0];
                }
                
                $items['catalogItems'][$k]['title'] = MG::modalEditor('catalog', $item['title'], 'edit', $item["id"]);
                if (($items['catalogItems'][$k]['count'] == 0 && empty($items['catalogItems'][$k]['variants'])) || (!empty($items['catalogItems'][$k]['variants']) && $items['catalogItems'][$k]['variants'][0]['count'] == 0)) {
                    $buyButton = $items['catalogItems'][$k]['actionView'];
                } else {
                    $buyButton = $items['catalogItems'][$k][$actionButton];
                    if (!empty($items['catalogItems'][$k]['variants'])) {
                        foreach ($items['catalogItems'][$k]['variants'] as $variant) {
                            if ($variant['count'] == 0) {
                            }
                        }
                    }
                }
                $liteFormData = $product->createPropertyForm($param = array(
                    'id' => $item['id'],
                    'maxCount' => $item['count'],
                    'productUserFields' => null,
                    'action' => "/catalog",
                    'method' => "POST",
                    'ajax' => true,
                    'blockedProp' => $blockedProp,
                    'noneAmount' => true,
                    'titleBtn' => " ",
                    'blockVariants' => $blocksVariants[$item['id']],
                    'buyButton' => $buyButton
                ));
                
                $items['catalogItems'][$k]['liteFormData'] = $liteFormData['html'];
                $buyButton                                 = $items['catalogItems'][$k]['liteFormData'];
                $items['catalogItems'][$k]['buyButton']    = $buyButton;
            }
        }
        
        $categoryDescRes = MG::get('category')->getDesctiption($_REQUEST['category_id']);
        
        if ($_REQUEST['category_id']) {
            $categoryDesc    = MG::inlineEditor(PREFIX . 'category', "html_content", $_REQUEST['category_id'], $categoryDescRes['html_content']);
            $categoryDescSeo = MG::inlineEditor(PREFIX . 'category', "seo_content", $_REQUEST['category_id'], $categoryDescRes['seo_content']);
        }
        
        $catImg = MG::get('category')->getImageCategory($_REQUEST['category_id']);
        
        $pageCat = URL::get("page") ? URL::get("page") : 1;
        $data    = array(
            'items' => $items['catalogItems'],
            'titeCategory' => $model->currentCategory['title'],
            'cat_desc' => $pageCat > 1 && (MG::getSetting('duplicateDesc') == 'false') ? '' : $categoryDesc,
            'cat_img' => $pageCat > 1 && (MG::getSetting('duplicateDesc') == 'false') ? '' : $catImg,
            'cat_id' => intval($_REQUEST['category_id']) ? intval($_REQUEST['category_id']) : 0,
            'filterBar' => $items['filterBarHtml'],
            'applyFilter' => $items['applyFilterList'],
            'totalCountItems' => $items['totalCountItems'],
            'pager' => $items['pager'],
            'searchData' => empty($searchData) ? '' : $searchData,
            'meta_title' => $model->currentCategory['meta_title'],
            'meta_keywords' => $model->currentCategory['meta_keywords'],
            'meta_desc' => $model->currentCategory['meta_desc'],
            'currency' => $settings['currency'],
            'actionButton' => $actionButton,
            'cat_desc_seo' => $pageCat > 1 && (MG::getSetting('duplicateDesc') == 'false') ? '' : $categoryDescSeo
        );
        if (URL::isSection('catalog') || (((MG::getSetting('catalogIndex') == 'true') && (URL::isSection('index') || URL::isSection(''))))) {
            $html                  = MG::get('pages')->getPageByUrl('catalog');
            $html['html_content']  = MG::inlineEditor(PREFIX . 'page', "html_content", $html['id'], $html['html_content']);
            $data['meta_title']    = $html['meta_title'] ? $html['meta_title'] : $html['title'];
            $data['meta_title']    = $data['meta_title'] ? $data['meta_title'] : $model->currentCategory['title'];
            $data['meta_keywords'] = $html['meta_keywords'];
            $data['meta_desc']     = $html['meta_desc'];
            $data['cat_desc']      = $html['html_content'];
            $data['cat_desc_seo']  = $html['seo_content'];
            $data['titeCategory']  = $html['title'];
        }
        if ($keyword) {
            $data['meta_title'] = '  : ' . URL::getQueryParametr('search');
        }
        
        $seoTmpl = Seo::getMetaByTemplate('catalog', $data);
        $seoData = Urlrewrite::getSeoDataFotUrl();
        
        if (!empty($seoTmpl) || !empty($seoData)) {
            if (empty($seoData)) {
                $seoData = $seoTmpl;
            }
            
            foreach ($seoData as $key => $value) {
                if (!empty($value)) {
                    $data[$key] = empty($model->currentCategory[$key]) ? $value : $model->currentCategory[$key];
                } elseif (empty($model->currentCategory[$key])) {
                    switch ($key) {
                        case 'meta_title':
                            $data[$key] = $model->currentCategory['title'];
                            break;
                        case 'meta_keywords':
                            $data[$key] = $model->currentCategory['title'] . "," . $lang['META_BUY'];
                            break;
                        case 'meta_desc':
                            $desc       = strip_tags($data['cat_desc']);
                            $data[$key] = substr($desc, 0, 160);
                            break;
                    }
                }
            }
        }
        $currencyRate = MG::getSetting('currencyRate');
        foreach ($data['items'] as $key => $product) {
            if (!empty($product['variants'])) {
                $data['items'][$key]["price"]        = MG::numberFormat($product['variants'][0]["price_course"]);
                $data['items'][$key]["old_price"]    = $product['variants'][0]["old_price"] * $currencyRate[$product['currency_iso']];
                $data['items'][$key]["count"]        = $product['variants'][0]["count"];
                $data['items'][$key]["code"]         = $product['variants'][0]["code"];
                $data['items'][$key]["weight"]       = $product['variants'][0]["weight"];
                $data['items'][$key]["price_course"] = $product['variants'][0]["price_course"];
            }
        }
        $this->data = $data;
    }
    
    public function convertLang($text)
    {
        
        $php = explode('.', phpversion());
        
        if ($php[0] < 5) {
            return $text;
        }
        if ($php[1] < 3) {
            return $text;
        }
        
        require_once(CORE_JS . 'langcorrect/ReflectionTypeHint.php');
        require_once(CORE_JS . 'langcorrect/UTF8.php');
        require_once(CORE_JS . 'langcorrect/Text/LangCorrect.php');
        
        $corrector = new Text_LangCorrect();
        $text      = $corrector->parse($text, 2);
        return $text;
    }
    
    public function getSearchData()
    {
        $keyword = MG::defenderXss_decode(URL::getQueryParametr('text'));
        if (!empty($keyword)) {
            $keyword = $this->convertLang($keyword);
            
            $catalog = new Models_Catalog;
            $items   = $catalog->getListProductByKeyWord($keyword, true, true, false, 'groupBy');
            
            foreach ($items['catalogItems'] as $key => $value) {
                $items['catalogItems'][$key]['image_url'] = mgImageProductPath($value["image_url"], $value['id'], 'small');
            }
            
            $searchData = array(
                'status' => 'success',
                'item' => array(
                    'keyword' => URL::getQueryParametr('text'),
                    'count' => $items['numRows'],
                    'items' => $items
                ),
                'currency' => MG::getSetting('currency')
            );
        }
        
        echo json_encode($searchData);
        exit;
    }
    
}
