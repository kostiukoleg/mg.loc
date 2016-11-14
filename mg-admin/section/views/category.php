<div class="section-category">
  <div class="widget-table-wrapper">
    <div class="widget-table-title">
      <h4 class="category-table-icon"><?php echo $lang['TITLE_CATEGORIES']; ?></h4>
       <p class="produc-count">Всего: <strong><?php echo $countCategory?></strong> шт.</p>
    </div>

    <!-- Верстка модального окна -->

    <div class="mg-modal b-modal hidden-form add-category-popup" id="add-category-wrapper">
      <div class="product-table-wrapper">
        <div class="widget-table-title">
          <h4 class="category-table-icon" id="modalTitle"><?php echo $lang['NEW_CATEGORY']; ?></h4>
          <div class="b-modal_close tool-tip-bottom" title="<?php echo $lang['T_TIP_CLOSE_MODAL']; ?>"></div>
        </div>
        <div class="widget-table-body">
          <div class="add-product-form-wrapper">
            <div class="add-category-form">
              <label><span class="custom-text"><?php echo $lang['CAT_NAME']; ?>:</span><input type="text" name="title" class="product-name-input tool-tip-right" title="<?php echo $lang['T_TIP_CAT_NAME']; ?>" ><div class="errorField"><?php echo $lang['ERROR_SPEC_SYMBOL']; ?></div></label>
              <label><span class="custom-text"><?php echo $lang['CAT_URL']; ?>:</span><input type="text" name="url" class="product-name-input tool-tip-right" title="<?php echo $lang['T_TIP_CAT_URL']; ?>"><div class="errorField"><?php echo $lang['ERROR_EMPTY']; ?></div></label>
              <div class="category-filter">
                <span class="custom-text"><?php echo $lang['CAT_PARENT']; ?>:</span>
                <select class="last-items-dropdown custom-dropdown tool-tip-right" title="<?php echo $lang['T_TIP_CAT_PARENT']; ?>" name="parent">
                  <option selected value='0'><?php echo $lang['ALL']; ?></option>
                  <?php echo $select_categories ?>
                </select>
              </div>

              <div class="cat-img">
                <span class="custom-text">
                  <?php echo $lang['CAT_IMAGE_URL']; ?>:
                </span>
                <input type="hidden" name="image_url" class="tool-tip-bottom">
                <div class="category-img-block" style="display: none">
                  <img class="category-image">
                </div>
                <a href="javascript:void(0);" class="add-image-to-category custom-btn" title="<?php echo $lang['CAT_IMAGE_URL']; ?>"><span>Добавить</span></a>
                <a href="javascript:void(0);" style="display: none" class="del-image-to-category custom-btn" title=""><span>Удалить</span></a>
              </div>
              <label><span class="custom-text"><?php echo $lang['CAT_INVISIBLE']; ?>:</span><input type="checkbox" name="invisible" class="tool-tip-bottom" title="<?php echo $lang['CAT_INVISIBLE']; ?>"></label>


              <a class="discount-setup-rate" href="javascript:void(0);" title="">Установить скидку/наценку для товаров категории</a>

              <div class="discount-rate-control" style="display:none">                  
                <div class="select-rate-block" style="display:none">
                  <div class="currency-block">
                    <div class="change-rate-dir">
                      <span>Применять к товарам категории:</span>        
                      <select name="change_rate_dir">  
                        <option value="up">Наценку</option>     
                        <option value="down" >Cкидку</option>                              
                      </select> 
                    </div>                   
                    <a class="apply-rate-dir fl-right custom-btn" href="javascript:void(0);"><span>Применить</span></a>
                    <a class="cancel-rate-dir fl-left custom-btn" href="javascript:void(0);"><span>Отмена</span></a>
                    <div class="clear"></div>
                  </div>
                </div>   

                <a class="discount-change-rate rate-dir-name" href="javascript:void(0);" title="Нажмите для выбора скидки или наценки">Наценка</a>

                <div class="discount-rate">                        
                  <span class="set-margin" style=""> <span class="rate-dir" style="">+</span> <input type="text" name="rate" value="0"> %</span>                 
                  <a href="javascript:void(0);" class="cancel-rate" style="display:inline-block">X</a>  
                </div>  
                <div class="discount-error errorField">Введите число</div>
              
              </div>
              <div class="discount_apply_follow">
              <label ><span class="custom-text"><?php echo $lang['APPLAY_FOR_SUBCAT']; ?>:</span>
                  <input type="checkbox" name="discount_apply_follow" value="false" class="tool-tip-bottom" title="<?php echo $lang['APPLAY_FOR_SUBCAT_T_TIP']; ?>">
              </label>
              </div>

              <div class="category-desc-wrapper">
                <span class="custom-text" style="margin-bottom: 10px;"><?php echo $lang['CATEGORY_CONTENT']; ?>:</span>
                <a href="javascript:void(0);" class="html-content-edit"><?php echo $lang['CATEGORY_CONTENT_EDIT']; ?></a>
                <div style="background:#FFF;display:none;" id="html-content-wrapper">   
                  <textarea class="product-desc-field" name="html_content"></textarea>
                </div>
              </div>
              <div class="category-desc-wrapper-seo">
                <span class="custom-text"><?php echo $lang['CATEGORY_CONTENT_SEO']; ?>:
                    <a href="javascript:void(0);" class="tool-tip-top desc-property" title="<?php echo $lang['CATEGORY_CONTENT_SEO_DESC']?>">?</a></span>
                <a href="javascript:void(0);" class="html-content-edit-seo"><?php echo $lang['CATEGORY_CONTENT_EDIT']; ?></a>
                <div style="background:#FFF;display:none;" id="html-content-wrapper-seo">   
                  <textarea class="product-desc-field" name="html_content-seo"></textarea>
                </div>
              </div>
              <div class="clear"></div>
              <span class="seo-title"><?php echo $lang['SEO_BLOCK'] ?></span>
              <div class="seo-wrapper">
                <button class="custom-btn seo-gen-tmpl tool-tip-bottom" title="<?php echo $lang['T_TIP_SEO_GEN_TMPL'];?>"><span><?php echo $lang['SEO_GEN_TMPL'];?></span></button>
                <div class="clear"></div>
                <label><span class="custom-text"><?php echo $lang['META_TITLE']; ?>:</span><input type="text" name="meta_title" class="product-name-input meta-data-category tool-tip-bottom" title="<?php echo $lang['T_TIP_META_TITLE']; ?>"></label>
                <label><span class="custom-text"><?php echo $lang['META_KEYWORDS']; ?>:</span><input type="text" name="meta_keywords" class="product-name-input meta-data-category tool-tip-bottom" title="<?php echo $lang['T_TIP_META_KEYWORDS']; ?>"></label>
                <label>
                  <ul class="meta-list">
                    <li><span class="custom-text"><?php echo $lang['META_DESC']; ?>:</span></li>
                    <li><span class="symbol-left"><?php echo $lang['LENGTH_META_DESC']; ?>: <span class="symbol-count"></span></li>
                  </ul>
                  <textarea class="product-meta-field meta-data-category tool-tip-bottom" name="meta_desc" title="<?php echo $lang['T_TIP_META_DESC']; ?>"></textarea>
                </label>
              </div>
              <div class="clear"></div>
              <button class="save-button tool-tip-bottom" title="<?php echo $lang['T_TIP_SAVE_CAT']; ?>"><span><?php echo $lang['SAVE']; ?></span></button>
              <div class="clear"></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Верстка модального окна -->

    <div class="widget-table-body">
      <div class="widget-table-action">
        <a href="javascript:void(0);" class="mg-open-modal add-new-button tool-tip-bottom" title="<?php echo $lang['T_TIP_ADD_CATEGORY']; ?>"><span><?php echo $lang['ADD_CATEGORY']; ?></span></a>
        <a href="<?php echo SITE ?>/mg-admin?category_csv=1" class="get-csv tool-tip-top" title="<?php echo $lang['T_TIP_CATEGORY_CSV'];?>"><span><?php echo $lang['IN_CSV'];?></span></a>
        <a href="javascript:void(0);" class="import-csv tool-tip-top custom-btn" title="<?php echo $lang['T_TIP_CATEGORY_FROM_CSV'];?>"><span><?php echo $lang['PROD_FROM_CSV'];?></span></a>   
        <a href="javascript:void(0);" class="check-all-cat tool-tip-bottom" title="Выбрать все категории"></a>
        <a href="javascript:void(0);" class="uncheck-all-cat tool-tip-bottom" title="Снять выбор категорий" style="display:none"></a>
        <a href="javascript:void(0);" class="sort-all-cat tool-tip-bottom" title="Упорядочить категории по алфавиту"></a>
      </div>
      
      <div class="import-container">
        <div class="message-importing"></div>
        <div class="process"></div>
      
        
        <div class="block-upload-сsv">
            <ul class="form-list">                
                <li>
                    <a href="<?php echo SITE?>/mg-admin?examplecategorycsv=1" class="get-example-csv-update view-MogutaCMSUpdate example-csv">Скачать пример файла</a>
                </li>
                <li>
                    <span><?php echo $lang['UPLOAD'];?></span>
                    <form method="post" noengine="true" enctype="multipart/form-data" class="upload-csv-form imageform">
                        <a href="javascript:void(0);" class="add-img-wrapper upload-btn">
                            <label for="check-file"><span>Выбрать файл</span></label>
                            <input type="file" name="upload" class="" id="check-file" title="<?php echo $lang['CSV_UPLOAD_FILE'];?>"/>
                        </a>
                    </form>
                </li>
            </ul>

        </div>
        
        <div class="block-importer">
          <div class="repeat-upload-file"><a href="javascript:void(0);" class="repeat-upload-csv" title="Отменить"></a></div>
          <div class="cancel-importing"><a href="javascript:void(0);" class="cancel-import custom-btn"><span><?php echo $lang['BREAK_UPLOAD_CSV'];?></span></a></div>    
          <div class="delete-all-products-btn"><label><input type="checkbox" name="no-merge" class="" title="<?php echo $lang['CLEAR_CATEGORY_BEFORE_CSV'];?>"  value="false"><?php echo $lang['DEL_ALL_CATEGORY'];?></label></div>
          <a href="javascript:void(0);" class="start-import custom-btn"><span><?php echo $lang['BEGIN_UPLOAD_CATEGORY_CSV'];?></span></a>
        </div>
        <div class="clear"></div>
      </div>

      <div class="category-tree-field">
        <ul class="edit-category-list" style="display:none">
          <li class="cat-li"><span class="cat-title">Название категории</span> <span class="cat-id">[id=101010]</span></li>
          <li><a href="javascript:void(0);" class="mg-open-modal edit-sub-cat"><?php echo $lang['EDIT']; ?></a></li>
          <li><a href="javascript:void(0);" class="add-sub-cat mg-open-modal"><?php echo $lang['ADD_SUBCATEGORY']; ?></a></li>
          <li><a href="javascript:void(0);" class="prod-sub-cat"><?php echo $lang['SHOW_PRODUCT']; ?></a></li>
          <li><a href="javascript:void(0);" class="delete-sub-cat"><?php echo $lang['DELETE']; ?></a></li>         
          <li><a href="javascript:void(0);" class="cancel-sub-cat"><?php echo $lang['CANCEL']; ?></a></li>
        </ul>
        <?php if (!empty($categories)): ?>
          <ul class="category-tree">
            <?php echo $categories ?>
          </ul>
        <?php else: ?>	
          <?php echo '<div class="empty-cat">'.$lang["CAT_NONE"].'</div>' ?>
        <?php endif; ?>
        <div class="clear"></div>

      </div>
        <select name="operation" class="category-operation">
            <option value="invisible_1">Не выводить в меню категорий</option>
            <option value="invisible_0">Выводить в меню категорий</option>
            <option value="activity_0">Сделать не активными</option>
            <option value="activity_1">Сделать активными</option>
            <option value="delete">Удалить выбранные категории</option>
        </select>
        <a href="javascript:void(0);" class="run-operation custom-btn"><span><?php echo $lang['ACTION_RUN']?></span></a>
    </div>
  </div>
</div>