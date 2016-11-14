<!--
Доступны переменные:
  $pluginName - название плагина
  $lang - массив фраз для выбранной локали движка
  $options - набор данного плагина хранимый в записи таблиц mg_setting
  $entity - набор записей сущностей плагина из его таблицы
  $pagination - блок навигациицам 
-->

<div class="section-<?php echo $pluginName ?>"><!-- $pluginName - задает название секции для разграничения JS скрипта -->
    <!-- Тут начинается Верстка модального окна -->
    <div class="b-modal hidden-form">
        <div class="custom-table-wrapper"><!-- блок для контента модального окна -->

            <div class="widget-table-title"><!-- Заголовок модального окна -->
                <h4 class="pages-table-icon" id="modalTitle">
                    <?php echo $lang['HEADER_MODAL_ADD']; ?>
                </h4><!-- Иконка + Заголовок модального окна -->
                <div class="b-modal_close tool-tip-bottom" title="<?php echo $lang['CLOSE_MODAL']; ?>"></div><!-- Кнопка для закрытия окнаа -->
            </div>

            <div class="widget-table-body slide-editor"><!-- Содержимое окна, управляющие элементы -->
                <h3>Описание бренда: </h3>
                <ul class="custom-form-wrapper mg-brand-info">        
                    <li>
                        <span>Бренд: </span>
                        <h2 class="brand-name activity-product-true" style="display:none"></h2>      
                        <input type="text" name="brand" value="" style="display:none"/>
                    </li>
                    <li>
                        <span>Описание: </span> <textarea name="desc" data-name="html_content"></textarea>              
                    </li>          
                    <li>
                        <span>Логотип: </span> <input type="hidden" name="logo" value=""/>
                        <img style="width:100px; height:100px;" class="logo-brand" src="" />
                        <div class="btn-holder">
                            <a href="javascript:void(0);" class="browseImage tool-tip-top" title="Загрузить логотип">
                                <span>Загрузить логотип</span>
                            </a>
                        </div>
                    </li>                      		
                </ul>                

                <button class="save-button tool-tip-bottom" data-id="" title="<?php echo $lang['SAVE_MODAL'] ?>"><!-- Кнопка действия -->
                    <span><?php echo $lang['SAVE_MODAL'] ?></span>
                </button>
                <div class="clear"></div>
            </div>
        </div>
    </div>
    <!-- Тут заканчивается Верстка модального окна -->
    <!-- Тут начинается верстка видимой части станицы настроек плагина-->
    <div class="widget-table-body">
        <div class="widget-table-action">
            <a href="javascript:void(0);" class="add-new-button tool-tip-top" title="<?php echo $lang['T_TIP_ADD_BRAND']; ?>"><span><?php echo $lang['ADD_BRAND']; ?></span></a>
            <a href="javascript:void(0);" class="copy-old-characteristic custom-btn tool-tip-bottom" data-property="<?php echo $options['propertyId'] ?>"
              title="Если Вы добавили товары с новыми значениями брендов в строковой характеристике, Вы можете копировать эти значения 
                  в характеристику 'Бренд'"><span>Копировать значения из другой характеристики</span></a>
              
            <div class="filter">
                <span class="last-items"><?php echo $lang['SHOW_COUNT_BRAND']; ?></span>
                <select class="last-items-dropdown countPrintRowsEntity">
                    <?php
                    foreach (array(10, 20, 30, 50, 100) as $value) {
                      $selected = '';
                      if ($value == $countPrintRows) {
                        $selected = 'selected="selected"';
                      }
                      echo '<option value="'.$value.'" '.$selected.' >'.$value.'</option>';
                    }
                    ?>
                </select>
            </div>
            <div class="clear"></div>
        </div>         
        <?php if ($options['first'] == 'true') : ?>
          <div class="first-settings"> 
              <div class="link-result">
                  Внимание! Создана новая характеристика "Бренд". Если Вы уже используете
                  такую же или похожую характеристику, можете экспортировать значения и настройки
                  этой характеристики в новую. Старая характеристика будет неактивна для
                  вывода в фильтрах и в карточке товара, вместо нее будет выводится новая.
                  Все данные будут скопированы в новую характеристику. Экспортировать характеристику?
              </div>
              <div class="brand-buttons">
                  <a href="javascript:void(0);" class="no-old-characteristic custom-btn" data-property="<?php echo $options['propertyId'] ?>">
                      <span>Нет</span></a>
                  <a href="javascript:void(0);" class="export-old-characteristic custom-btn" data-property="<?php echo $options['propertyId'] ?>">
                      <span>Экспортировать</span></a>
              </div>
          </div>
        <?php endif; ?> 

        <div class="wrapper-entity-setting ">
            <?php if ($empty > 0) : ?>
              <div class="link-result"> Бренды без логотипов не выводятся в блоке на сайте! Добавьте логотип, чтобы бренд был выведен на сайте.</div>
            <?php endif; ?>
            <!-- Тут начинается верстка таблицы сущностей  -->
            <table class="widget-table mg-brand-table">          
                <thead>
                    <tr>
                        <th>
                            Логотип
                        </th>
                        <th>
                            Название
                        </th>
                        <th>
                            Описание
                        </th>                                
                        <th class="actions"><?php echo $lang['ACTIONS']; ?>
                        </th>
                    </tr>
                </thead>
                <tbody class="entity-table-tbody mg-brand-tbody"> 
                    <?php if (empty($brand)): ?>
                      <tr class="no-results">
                          <td colspan="4" class="no-results" align="center"><?php echo $lang['ENTITY_NONE']; ?></td>
                      </tr>
                    <?php else: ?>
                      <?php foreach ($brand as $row): ?>
                        <tr data-id="<?php echo $row['id']; ?>" class="<?php echo $row['url'] == '' ? 'no-logo' : '' ?>">
                            <td class="logo">
                                <?php
                                $src = ($row['url'] ? $row['url'] : SITE.'/mg-admin/design/images/no-img.png');
                                ?>
                                <img class="uploads" src="<?php echo $src ?>"/>
                            </td>
                            <td class="brand"> 
                                <?php echo $row['brand'] ?>
                            </td>

                            <td class="desc">                                  
                                <?php echo $row['desc'] ?>                    
                            </td>  
                            <td class="actions">
                                <ul class="action-list"><!-- Действия над записями плагина -->
                                    <li class="edit-row" 
                                        data-id="<?php echo $row['id'] ?>">
                                        <a class="tool-tip-bottom" href="javascript:void(0);" 
                                           title="<?php echo $lang['EDIT']; ?>"></a>
                                    </li>                                           
                                    <li class="delete-row" 
                                        data-id="<?php echo $row['id'] ?>">
                                        <a class="tool-tip-bottom" href="javascript:void(0);"  
                                           title="<?php echo $lang['DELETE']; ?>"></a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="clear"></div>

    <?php echo $pagination ?>  <!-- Вывод навигации -->
    <div class="clear"></div>
</div>
<script>
  admin.sortable('.mg-brand-tbody', 'brand-logo');
</script>