<?php mgAddMeta('<link href="'.SCRIPT.'standard/css/layout.related.css" rel="stylesheet" type="text/css" />'); ?>
<?php mgAddMeta('<script type="text/javascript" src="'.SCRIPT.'jquery.bxslider.min.js"></script>'); ?>

<div class="mg-recent-products">
    <div class="title"><span><?php echo $data['title'] ?></span></div>
    <div class="m-p-products-slider">
        <div class="m-p-products-slider-start">
            <?php foreach ($data['products'] as $item):?>
                <div class="product-wrapper">
                    <div class="product-stickers">
                        <?php
                        echo $item['recommend']?'<span class="sticker-recommend">Хит!</span>':'';
                        echo $item['new']?'<span class="sticker-new">Новинка</span>':'';
                        ?>
                    </div>
                    <div class="product-image">
                        <a href="<?php echo $item["url"]?>">
                            <?php 
                            $item['image_url'] = $item['img'];
                            echo mgImageProduct($item); 
                            ?>
                        </a>
                    </div>
                    <div class="product-code">Артикул: <?php echo $item["code"] ?></div>
                    <div class="product-name">
                        <a href="<?php echo $item["url"]?>"><?php echo $item["title"] ?></a>
                    </div>
                    <div class="product-footer">
                        <span class="product-price">
                            <?php echo priceFormat($item["price"]) ?> <?php echo $data['currency']; ?>
                        </span>
                        <div class="product-buttons">
                            <!--Кнопка, кототорая меняет свое значение с "В корзину" на "Подробнее"-->
                            <?php echo $item[$data['actionButton']]?>
                            <?php echo $item['actionCompare']?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>