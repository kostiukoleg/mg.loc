<!--
Доступны переменные:
  $pluginName - название плагина
  $lang - массив фраз для выбранной локали движка
  $options - набор данного плагина хранимый в записи таблиц mg_setting
  $arCategories - набор категорий(записи из таблицы mg_blog_categories)
-->

<div class="section-<?php echo $pluginName;?> category"><!-- $pluginName - задает название секции для разграничения JS скрипта -->
  <!-- Тут начинается Верстка модального окна -->
  <div class="b-modal hidden-form">
    <div class="product-table-wrapper add-news-form">
      <div class="widget-table-title">
        <h4 class="pages-table-icon" id="modalTitle"><?php echo $lang['CAT_MODAL_TITLE'];?></h4>
        <div class="b-modal_close tool-tip-bottom" title="<?php echo $lang['T_TIP_CLOSE_MODAL'];?>"></div>
      </div>
      <div class="widget-table-body">
        <div class="add-product-form-wrapper">

          <div class="add-img-form">
            <div class="product-text-inputs">
              <label for="title"><span class="custom-text"><?php echo $lang['NAME'];?>:</span><input type="text" name="title" class="product-name-input tool-tip-right" title="<?php echo $lang['T_TIP_CAT_NAME'];?>"><div class="errorField"><?php echo $lang['ERROR_SPEC_SYMBOL'];?></div></label>
              <label for="url"><span class="custom-text"><?php echo $lang['CODE'];?>:</span><input type="text" name="url" class="product-name-input qty tool-tip-right" title="<?php echo $lang['T_TIP_CAT_CODE'];?>"><div class="errorField"><?php echo $lang['ERROR_EMPTY'];?></div></label>
            </div>
              
            <div class="product-upload-img">
              <p class="add-text add-img-text"><?php echo $lang['IMAGE_PRODUCT'];?></p>
              <div class="product-img-prev">
                <div class="img-loader" style="display:none"></div>
                <div class="prev-img"><img src="<?php echo SITE ?>/mg-admin/design/images/no-img.png" alt="" /></div>


                <form id="imageform" method="post" noengine="true" enctype="multipart/form-data">
                    <a href="javascript:void(0);" class="add-img-wrapper">
                        <span>Загрузить</span>
                        <input type="file" name="photoimg" id="photoimg" class="add-img"/>
                        <input type="hidden" name="photoImgName" value=""/>
                    </a>
                </form>
                  <a href="javascript:void(0);" class="cancel-img-upload tool-tip-top" title="<?php echo $lang['T_TIP_DEL_IMG_PROD'];?>"><span>Удалить</span></a>
                <div class="clear"></div>
              </div>
            </div>
            <div class="clear"></div>
            <div class="product-desc-wrapper">
              <span class="add-text"><?php echo $lang['CAT_DESCRIPTION'];?>:</span>
              <div style="background:#FFF">
                <textarea class="product-desc-field" name="html_content_blog"  style="width:785px;"></textarea>
              </div>
            </div>
            <span class="seo-title"><?php echo $lang['SEO_BLOCK']?></span>
            <div class="seo-wrapper">
              <label><span class="add-text"><?php echo $lang['META_TITLE'];?>:</span><input type="text" name="meta_title" class="product-name-input meta-data tool-tip-bottom" title="<?php echo $lang['T_TIP_META_TITLE'];?>"></label>
              <label><span class="add-text"><?php echo $lang['META_KEYWORDS'];?>:</span><input type="text" name="meta_keywords" class="product-name-input meta-data tool-tip-bottom" title="<?php echo $lang['T_TIP_META_KEYWORDS'];?>"></label>

              <label>
                  <ul class="meta-list">
                    <li><span class="add-text"><?php echo $lang['META_DESC'];?>:</span></li>
                    <li><span class="symbol-left"><?php echo $lang['LENGTH_META_DESC'];?>: <span class="symbol-count"></span></li>
                  </ul>
                  <textarea class="product-meta-field  meta-data tool-tip-bottom" name="meta_desc" title="<?php echo $lang['T_TIP_META_DESC'];?>"></textarea>
              </label>
            </div>
            <div class="clear"></div>

            <button class="save-button tool-tip-bottom" title="<?php echo $lang['T_TIP_SAVE_CAT'];?>"><span><?php echo $lang['SAVE'];?></span></button>

            <div class="clear"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- Тут заканчивается Верстка модального окна -->
    
  <div class="widget-table-body">
    <div class="widget-table-action">
      <a class="back-to-item-list custom-btn"><span><?php echo $lang['BACK_TO_ARTICLE'];?></span></a>
      <div class="add-new-button "><span><?php echo $lang['ADD_CATEGORY'];?></span></div>
      <div class="clear"></div>
    </div>
      
    <div class="wrapper-entity-setting">

      <!-- Тут начинается верстка таблицы сущностей  -->
      <div class="entity-table-wrap">        
        <div class="entity-settings-table-wrapper">
          <table class="widget-table">
            <thead>
              <tr>
                <th style="width:40px">№</th>
                <th style="width:100px;"><?php echo $lang['CATEGORY_NAME'];?></th>
                <th style="width:100px;"><?php echo $lang['CODE'];?></th>
                <th style="width:100px;"><?php echo $lang['ENTITY_ACTIONS'];?></th>
              </tr>
            </thead>
            <tbody class="entity-table-tbody"> 
              <?php if (empty($arCategories)): ?>
                <tr class="no-results">
                  <td colspan="4" align="center"><?php echo $lang['ENTITY_NONE'];?></td>
                </tr>
                <?php else: ?>
                  <?php foreach ($arCategories as $row):?>
                  <tr data-id="<?php echo $row['id'];?>">
                    <td><?php echo $row['id'];?></td>
                    <td>
                      <?php echo $row['title'];?>
                      <a class="link-to-site tool-tip-bottom" title="<?php echo $lang['VIEW_SITE'];?>" href="<?php echo SITE.'/'.$pluginName.'/'.$row['url']?>"  target="_blank" >
                        <img src="<?php echo SITE?>/mg-admin/design/images/icons/link.png" alt="" />
                      </a>
                    </td>
                    <td><?php echo $row['url'];?></td>
                    <td class="actions">
                      <ul class="action-list"><!-- Действия над записями плагина -->
                        <li class="edit-row" 
                            data-id="<?php echo $row['id']?>" 
                            data-type="<?php echo $row['type'];?>">
                          <a class="tool-tip-bottom" href="javascript:void(0);" 
                             title="<?php echo $lang['EDIT'];?>"></a>
                        </li>
                        <li class="delete-row" 
                            data-id="<?php echo $row['id']?>">
                          <a class="tool-tip-bottom" href="javascript:void(0);"  
                             title="<?php echo $lang['DELETE'];?>"></a>
                        </li>
                      </ul>
                    </td>
                  </tr>
                <?php endforeach;?>
              <?php endif;?>
            </tbody>
          </table>
        </div>
      </div>
      <div class="clear"></div>
    </div>
  </div>
</div>
<script>
admin.sortable('.entity-table-tbody','<?php echo $pluginName.'_categories'?>');
</script>