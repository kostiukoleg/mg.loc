<?php
mgSEO($data['category']);
if (!class_exists('Blog')) {
  echo "Плагин не подключен!";
  return false;
}?>
<div class="news-block">
	<div class="news-header">
            <div class="title"><?php echo $data['category']['title']; ?></div>
                            <a href="<?php echo SITE; ?>/blog" class="show-all">Все новости</a>
             <a href="<?php echo SITE . $data['category']['url'] . "rss"; ?>" title="rss" target="_blank" class="rss">Подписаться на RSS</a>
	</div>
	
	<div class="news-body">  
              <?php foreach ($data["entity"] as $arItem):?>
		<div class="news-item">
                        <?php if ($arItem['image_url']): ?>
                          <a href="<?php echo SITE . $arItem['path']; ?>" class="news-img">
                            <img src="<?php echo SITE . $data['img_path'] . 'thumbs/30_' . $arItem['image_url']; ?>" alt="<?php echo $arItem['title']; ?>" title="<?php echo $arItem['title']; ?>">
                          </a>
                        <?php endif; ?>
			<div class="news-details">
				<div class="mg-news-date"><span class="mg-date-icon"></span><?php echo $arItem['date_active_from']; ?></div>
				<a href="<?php echo SITE . $arItem['path']; ?>" class="news-text">
                                    <?php echo $arItem['title']; ?>
				</a>
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
                                <div class="mg-news-main-desc">
                                    <?php echo $arItem['previewText']; ?>
                                </div>
			</div>
		</div>
            <?php endforeach;?>
            <div class="news-footer"> 
                <?php echo $data['pagination'];?>
            </div> 
	</div> 
</div>