<?php
mgSEO($data['category']);
if (!class_exists('Blog')) {
  echo "Плагин не подключен!";
  return false;
}?>
<div class="mg-main-news-block">
  <?php if(intval($data['selectedCat']) > 0):?>
  <a href="<?php echo SITE;?>/blog/"><?php echo $options['root_category_title']?></a>
  <?php endif;?>
  <a href="<?php echo SITE . $data['category']['url'] . "rss"; ?>" title="rss" target="_blank" class="rss">Подписаться на RSS</a>
  <h1 class="mg-news-title">
    <?php echo $data['category']['title']; ?>
  </h1>
  <?php if ($data['category']['image_url']): ?>
    <div class="mg-cat-news-img">
      <img src="<?php echo SITE.$data['img_path'].$data['category']['image_url']?>" alt="<?php echo $data['category']['title'] ?>" title="<?php echo $data['category']['title'] ?>">
    </div>
  <?php endif; ?>
  <?php if ($data['category']['description']): ?>
    <div class="mg-category-desc">
      <?php echo $data['category']['description']; ?>
    </div>
  <?php endif; ?>
  <div class="clear"></div>
  <?php foreach ($data["entity"] as $arItem):?>
  <div class="mg-main-news-item">
    <?php if ($arItem['image_url']): ?>
      <a href="<?php echo SITE . $arItem['path']; ?>" class="mg-list-news-img">
        <img src="<?php echo SITE . $data['img_path'] . 'thumbs/30_' . $arItem['image_url']; ?>" alt="<?php echo $arItem['title']; ?>" title="<?php echo $arItem['title']; ?>">
      </a>
    <?php endif; ?>
    <div class="mg-news-info">
      <div class="mg-news-date"><span class="mg-date-icon"></span> <?php echo $arItem['date_active_from']; ?></div>
      <h3 class="mg-news-title">
        <a href="<?php echo SITE . $arItem['path']; ?>"><?php echo $arItem['title']; ?></a>
      </h3>
      <div class="mg-news-main-desc">
        <?php echo $arItem['previewText']; ?>
      </div>
      <?php if(!empty($arItem['tags'])):?>
      <div class="tags">
        Теги:
        <?php foreach($arItem['tags'] as $cell=>$tag){
          if($cell > 0) 
            echo ', ';
          echo '<a href="'.$tag['url'].'">'.$tag['value'].'</a>';
        }?>
      </div>
      <?php endif;?>
    </div>
    <div class="clear"></div>
  </div>
  <?php endforeach;?>
  <?php echo $data['pagination'];?>
</div>
