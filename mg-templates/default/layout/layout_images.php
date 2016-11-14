<?php mgAddMeta('<link type="text/css" href="'.SCRIPT.'standard/css/jquery.fancybox.css" rel="stylesheet"/>'); ?>
<?php mgAddMeta('<link type="text/css" href="'.SCRIPT.'standard/css/layout.images.css" rel="stylesheet"/>'); ?>
<?php mgAddMeta('<script type="text/javascript" src="'.SCRIPT.'jquery.fancybox.pack.js"></script>'); ?>
<?php mgAddMeta('<script type="text/javascript" src="'.SCRIPT.'jquery.bxslider.min.js"></script>'); ?>
<?php mgAddMeta('<script type="text/javascript" src="'.SCRIPT.'standard/js/layout.images.js"></script>'); ?>
<?php mgAddMeta('<script type="text/javascript" src="'.SCRIPT.'zoomsl-3.0.js"></script>'); ?>
<style>
/*
.magnify {
  position: relative;
  display: inline-block;
}

.magnify .magnify-lens.loading {
  background: #333 !important;
  opacity: .75;
}
.magnify .magnify-lens.loading:after {

  position: absolute;
  top: 45%;
  left: 0;
  width: 100%;
  color: #fff;
  content: 'Loading...';
  font: italic normal 16px/1 Calibri, sans-serif;
  text-align: center;
  text-shadow: 0 0 2px rgba(51, 51, 51, .8);
  text-transform: none;
}
*/
</style>
<div class="mg-product-slides">
    <?php
    echo $data['recommend']?'<span class="sticker-recommend"></span>':'';
    echo $data['new']?'<span class="sticker-new"></span>':'';
    ?>

  
    <ul class="main-product-slide">
        <?php         
        foreach ($data["images_product"] as $key=>$image){?>
            <li  class="product-details-image"><a href="<?php echo mgImageProductPath($image, $data["id"]) ?>" rel="gallery" class="fancy-modal">
            <?php
            $item["image_url"] = $image;
            $item["id"] = $data["id"];
            $item["title"] = $data["title"];
            $item["image_alt"] = $data["images_alt"][$key];
            $item["image_title"] = $data["images_title"][$key];
            echo mgImageProduct($item);
            ?></a>
            <a class="zoom" href="javascript:void(0);"></a>
	
            </li>
        <?php 
		
		}?>
    </ul>


    <?php if(count($data["images_product"])>1){?>
    <div class="slides-slider">
        <div class="slides-inner">
            <?php foreach ($data["images_product"] as $key=>$image){
              $src = mgImageProductPath($image, $data["id"], 'big');
            ?>
              <a data-slide-index="<?php echo $key?>" class="slides-item" href="javascript:void(0);">
                <img class="mg-peview-foto"  src="<?php echo $src ?>" alt="<?php echo $data["images_alt"][$key];?>" title="<?php echo $data["images_title"][$key];?>"/>
              </a>
            <?php }?>
        </div>
    </div>
    <?php }?>
</div>