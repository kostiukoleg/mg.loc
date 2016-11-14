<?php

class Controllers_Cart extends BaseController {

  public function __construct() {

    if (!empty($_REQUEST['updateCart'])) {
      $this->updateCart();
      exit;
    }

    if (!empty($_REQUEST['delFromCart'])) {
      $this->delFromCart();
      exit;
    }

    if (!empty($_POST['coupon'])) {
      $this->applyCoupon();
    }

    $model = new Models_Cart;

    if (!empty($_REQUEST['refresh'])) {
      $update = array();
      $refreshData = $_REQUEST;

      foreach ($refreshData as $key => $val) {
        $id = '';
        if ('item_' == substr($key, 0, 5)) {
          $id = substr($key, 5);
          //  propertyReal   ID .
          $propertyReal = array();
          $variantId = array();
          if (!empty($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $item) {
              if ($item['id'] == $id) {
                $propertyReal[] = $item['propertyReal'];
                $variantId[] = $item['variantId'];
              }
            }
          }

          if (!empty($val)) {
            $product = new Models_Product();

            foreach ($val as $k => $count) {
              $propertySetId = $refreshData['property_' . $id][$k];

              if ($count > 0) {

                $tempProduct = $product->getProduct($id);
                $countMax = $tempProduct['count'];

                if ($variantId[$k]) {
                  $tempProdVar = $product->getVariants($id);
                  $countMax = $tempProdVar[$variantId[$k]]['count'];
                }

                if ($count > $countMax && $countMax > 0) {
                  $count = $countMax;
                }

                $update[] = array(
                  'id' => $id,
                  'count' => ($count >= 0) ? $count : 0,
                  'property' => $_SESSION['propertySetArray'][$propertySetId],
                  'propertyReal' => $propertyReal[$k],
                  'propertySetId' => $propertySetId,
                  'variantId' => $variantId[$k]
                );
              } else {
                if (!empty($_SESSION['propertySetArray'][$propertySetId])) {
                  unset($_SESSION['propertySetArray'][$propertySetId]);
                }
              }
            }
          }
        } elseif ('del_' == substr($key, 0, 4)) {
          $id = substr($ItemId, 4);
          $count = 0;
        }
      }

      $model->refreshCart($update);
      if (!empty($_REQUEST['count_change'])) {
        $data = SmalCart::getCartData();
        $data['cart'] = $_SESSION['cart'];
        $response = array(
          'status' => 'success',
          'data' => $data,
          );
        echo json_encode($response);
        exit;
      }

      header('Location: ' . SITE . '/cart');
      exit;
    }
    
    if (!empty($_REQUEST['clear'])) {
      $model->clearCart();
      SmalCart::setCartData();
      header('Location: ' . SITE . '/cart');
      exit;
    }

    $settings = MG::get('settings');
    $cartData = $model->getItemsCart();
    
    foreach ($cartData['items'] as $item) {
      $related .= ',' . $item['related'];
      $relatedCat .= ',' . $item['related_cat'];
    }

    if (!empty($related)) {
      $codes = explode(',', $related);
      $codes = array_unique($codes);
      $related = implode(',', $codes);
            
    }
     if (!empty($relatedCat)) {
      $cat_id = explode(',', $relatedCat);
      $cat_id = array_unique($cat_id);
      $relatedCat = implode(',', $cat_id); 
    }
    if (!empty($related)||$relatedCat) {
      $product = new Models_Product();
      $related = $product->createRelatedForm(array('product'=>$related, 'category'=>$relatedCat),
                  '    ', 'layout_relatedcart');
    }
    

    $this->data = array(
      'isEmpty' => $model->isEmptyCart(),
      'productPositions' => $cartData['items'],
      'totalSumm' => $cartData['totalSumm'],
      'related' => $related,
      'meta_title' => '',
      'meta_keywords' => !empty($model->currentCategory['meta_keywords']) ? $model->currentCategory['meta_keywords'] : ",,, ",
      'meta_desc' => !empty($model->currentCategory['meta_desc']) ? $model->currentCategory['meta_desc'] : "         .",
      'currency' => $settings['currency']
    );
  }

  public function updateCart() {

    $cart = new Models_Cart;
    //    ,    ID.
    $variantId = null;
    if (!empty($_POST["variant"])) {
      $variantId = $_POST["variant"];
      unset($_POST["variant"]);
    }

    if (isset($_POST['propertySetId'])) {
      foreach ($_SESSION['cart'] as $key => $item) {
        if ($item['propertySetId'] == $_POST['propertySetId'] && $item['id'] == $_POST['inCartProductId']) {
          $_SESSION['cart'][$key]['count'] = (is_numeric($_REQUEST['amount_input'])) ? 
                  intval($_REQUEST['amount_input']) : 1;
        }
      }
      $response = array(
        'status' => 'success',
        'data' => SmalCart::getCartData()
      );

      echo json_encode($response);
      exit;
    }
 
    if (empty($_POST)&&isset($_REQUEST['inCartProductId']) || (isset($_POST['updateCart']) && isset($_POST['inCartProductId']) && (count($_POST) == 3 || count($_POST) == 2) )) {

      $modelProduct = new Models_Product;
      $product = $modelProduct->getProduct(intval($_REQUEST['inCartProductId']));
      $blockVariants = $modelProduct->getBlockVariants($product['id']);

      if (!$variantId) {
        $variants = $modelProduct->getVariants($product['id']);
        $variantsKey = array_keys($variants);
        $variantId = $variantsKey[0];
      }
      $blockedProp = $modelProduct->noPrintProperty();

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
        'currency_iso' => $product['id'],
      ));
      $_POST = $propertyFormData['defaultSet'];
      $_POST['inCartProductId'] = $product['id'];
    } elseif (empty($_POST)) {
      header('Location: ' . SITE . '/cart');
      exit;
    }

    $property = $cart->createProperty($_POST);
    $result = $cart->addToCart($_REQUEST['inCartProductId'], intval($_REQUEST['amount_input']), $property, $variantId);
    if ($result) {
      $response = array(
        'status' => 'success',
        'data' => SmalCart::getCartData()
      );
      echo json_encode($response);
      exit;
    } 
    
  }

  public function delFromCart() {
    $cart = new Models_Cart;
    $property = $_SESSION['propertySetArray'][$_POST['property']];
    $cart->delFromCart($_POST['itemId'], $property, $_POST['variantId']);

    $response = array(
      'status' => 'success',
      'data' => SmalCart::getCartData()
    );
    echo json_encode($response);
    exit;
  }

  public function applyCoupon() {
    $_SESSION['couponCode'] = $_POST['couponCode'];
  }

}

