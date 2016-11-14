<?php if(!empty($data['categories'])):?>
    <div class="mg-blog-categories">
        <h2>Категории блога</h2>
        <ul>
            <?php foreach($data['categories'] as $category):?>
                <li><a href="<?php echo SITE."/blog/".$category['url']?>" <?php echo($category['url']==$data['selected_category'])?'class="selected"':''?>>
                  <?php echo $category['title']; echo ($data['showCnt'])?'('.$category['cnt'].')':''?>
                </a></li>
            <?php endforeach;?>
        </ul>
    </div>
<?php endif;?>