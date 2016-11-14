<!--
Доступны переменные:
  $pluginName - название плагина
  $lang - массив фраз для выбранной локали движка
  $options - набор данного плагина хранимый в записи таблиц mg_setting
  $entity - набор записей сущностей плагина из его таблицы
  $pagination - блок навигациицам 
-->

<div class="section-<?php echo $pluginName;?>"><!-- $pluginName - задает название секции для разграничения JS скрипта -->
    
  <!-- Тут начинается Верстка модального окна -->
  <div class="b-modal hidden-form">
    <div class="product-table-wrapper add-news-form">
      <div class="widget-table-title">
        <h4 class="pages-table-icon" id="modalTitle"><?php echo $lang['MODAL_TITLE'];?></h4>
        <div class="b-modal_close tool-tip-bottom" title="<?php echo $lang['T_TIP_CLOSE_MODAL'];?>"></div>
      </div>
      <div class="widget-table-body">
        <div class="add-product-form-wrapper">

          <div class="add-img-form">
            <div class="product-text-inputs">
              <label for="question"><span class="custom-text"><?php echo $lang['NAME'];?>:</span><input type="text" name="question" class="product-name-input tool-tip-right" title="<?php echo $lang['T_TIP_NAME'];?>"><div class="errorField"><?php echo $lang['ERROR_SPEC_SYMBOL'];?></div></label>
<!--              <label><a href="javascript:void(0);" class="set-visible-period note" data-change-text="<?php echo $lang['HIDE_PERIOD_PARAMS'];?>"><span><?php echo $lang['VISIBLE_PERIOD_PARAMS'];?></span></a></label>
              <div class="period-params">
                <label><span class="custom-text"><?php echo $lang['ACTIVE_FROM'];?>:</span><input style="width:250px;" type="text" name="date_active_from" class="date-from-input tool-tip-right" title="<?php echo $lang['T_TIP_DATE_ACTIVE_FROM'];?>"></label>
                <label><span class="custom-text"><?php echo $lang['ACTIVE_TO'];?>:</span><input style="width:250px;" type="text" name="date_active_to" class="date-to-input tool-tip-right" title="<?php echo $lang['T_TIP_DATE_ACTIVE_TO'];?>"></label>
              </div>-->
              <label>Варианты ответов</label>
              <div class="poll-answers">
              </div>
              <div class="errorField answers"><?php echo $lang['ERROR_MIN_ANSWERS'];?></div>
            </div>
            <div class="clear"></div>
            <button class="custom-btn add-answer-button tool-tip-bottom" id="add-answer-button" title="<?php echo $lang['T_TIP_PREVIEW'];?>"><span><?php echo $lang['ADD_ANSWER'];?></span></button>
            <button class="save-button tool-tip-bottom" title="<?php echo $lang['T_TIP_SAVE'];?>"><span><?php echo $lang['SAVE'];?></span></button>
            <div class="clear"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- Тут заканчивается Верстка модального окна -->

  <!-- Тут начинается верстка видимой части станицы настроек плагина-->

  <div class="widget-table-body">
    <div class="widget-table-action">
      <a href="javascript:void(0);" class="custom-btn add-new-button"><span><?php echo $lang['ADD_MODAL'];?></span></a>
      <div class="filter">
        <span class="last-items"><?php echo $lang['COUNT'];?></span>
        <select class="last-items-dropdown countPrintRowsPage">
          <?php
          foreach(array(5, 10, 15, 20, 25, 30) as $value){
            $selected = '';
            if($value == $countPrintRows){
              $selected = 'selected="selected"';
            }
            echo '<option value="'.$value.'" '.$selected.' >'.$value.'</option>';
          }
          ?>
        </select>
      </div>
      <div class="clear"></div>
    </div>
    
    <div class="wrapper-entity-setting">
      <div class="clear"></div>
      <!-- Тут начинается верстка таблицы сущностей  -->
      <div class="entity-table-wrap">        
        <div class="entity-settings-table-wrapper">
          <table class="widget-table">
            <thead>
              <tr>
                <th style="width:40px">№</th>
                <th style="width:300px;"><?php echo $lang['NAME'];?></th>
                <th style="width:100px;"><?php echo $lang['DATE_ACTIVE_FROM'];?></th>
                <th style="width:100px;"><?php echo $lang['VOTES_COUNT'];?></th>
                <th style="width:100px;"><?php echo $lang['ENTITY_ACTIONS'];?></th>
              </tr>
            </thead>
            <tbody class="entity-table-tbody"> 
              <?php if(empty($entity)):?>
                <tr class="no-results">
                  <td colspan="5" align="center"><?php echo $lang['ENTITY_NONE'];?></td>
                </tr>
                  <?php else:?>
                    <?php foreach ($entity as $row):?>
                    <tr data-id="<?php echo $row['id'];?>">
                      <td><?php echo $row['id'];?></td>
                      <td>
                        <?php echo $row['question'];?>
                      </td>
                      <td class="date_from"><?php echo $row['date_active_from']?></td>
                      <td class="votes-count"><?php echo $row['votes_count'];?></td>
                      <td class="actions">
                        <ul class="action-list"><!-- Действия над записями плагина -->
                          <li class="edit-row" 
                              data-id="<?php echo $row['id']?>" 
                              data-type="<?php echo $row['type'];?>">
                            <a class="tool-tip-bottom" href="javascript:void(0);" 
                               title="<?php echo $lang['EDIT'];?>"></a>
                          </li>
                          <li class="visible tool-tip-bottom  <?php echo ($row['activity']) ? 'active' : ''?>" 
                              data-id="<?php echo $row['id']?>" 
                              title="<?php echo ($row['invisible']) ? $lang['ACT_V_ENTITY'] : $lang['ACT_UNV_ENTITY'];?>">
                            <a href="javascript:void(0);"></a>
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
    
      <?php echo $pagination?>  <!-- Вывод навигации -->
      <div class="clear"></div>
    </div>
  </div>
</div>