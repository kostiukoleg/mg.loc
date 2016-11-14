<?php MG::enableTemplate(); ?>
<div class="products-wrapper brands">   
    <?php
    $currency = MG::getSetting('currency');
    $item = brand::getProductsByBrand($_GET['brand']);
    $items = $item['items'];
    $brand = $item['brand'];
    $pager = $item['pager'];
    $data['meta_title']= 'Бренд '.$brand['brand'];
    mgSEO($data);
    ?>
<?php if (!empty($brand)) { ?>
      <h1 class="new-products-title"><?php echo $brand['brand'] ?></h1> 
      <div class="cat-desc">	        
          <div class="cat-desc-img">
              <img src="<?php echo $brand['url'] ?>" alt="<?php echo $brand['brand'] ?>" title="<?php echo $brand['brand'] ?>" >
          </div>
          <div class="cat-desc-text"><?php echo $brand['desc'] ?></div>	
          <div class="clear"></div>
      </div>
    <?php } ?>
    <?php
    foreach ($items as $item) {
      $imagesUrl = explode("|", $item['image_url']);
      if (!empty($imagesUrl[0])) {
        $item["image_url"] = $imagesUrl[0];
      }
        ?>
        <div class="product-wrapper">
            <div class="product-image">
                <?php
                echo $item['recommend'] ? '<span class="sticker-recommend"></span>' : '';
                echo $item['new'] ? '<span class="sticker-new"></span>' : '';
                ?> 
                <a href="<?php echo $item['link'] ?>">
                <?php echo mgImageProduct($item); ?> 
                </a>
            </div>
            <div class="product-name">
                <a href="<?php echo $item['link'] ?>"><?php echo $item["title"] ?></a>
            </div>       
            <span class="product-price"><?php echo priceFormat($item["price"]) ?> <?php echo $currency; ?></span>
            <!--Кнопка, кототорая меняет свое значение с "В корзину" на "Подробнее"-->
             <?php echo $item['buyButton']; ?>
        </div>
        <?php
      
    }
    ?>
    <div class="clear"></div> 
    <?php echo $pager; ?>
     <div class="clear"></div> 
</div>
