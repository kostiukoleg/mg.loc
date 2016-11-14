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
       
        <div class="block-for-form" >
          <ul class="custom-form-wrapper fields-calback">
            <li>
              <span>Промокод</span> <input type="text" name="code" value=""/>              
            </li>
            <li>
              <span>Процент скидки</span> <input type="text" name="percent" value=""/>              
            </li>      
            <li>
              <span>Нижняя граница</span> <input type="text" name="from_datetime" value=""/>              
            </li>
            <li>
              <span>Верхняя граница</span> <input type="text" name="to_datetime" value=""/>              
            </li>            
            <li>
              <span class="textarea-text">Описание</span>
              <textarea name="desc">  </textarea>
            </li>			
          </ul>        
        </div>                
                
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
       <a href="javascript:void(0);" class="add-new-button tool-tip-top" title=""><span><?php echo $lang['ADD_NEW_PROMO'];?></span></a>
       <a href="javascript:void(0);" class="show-filters tool-tip-top" title="<?php echo $lang['T_TIP_SHOW_FILTER'];?>"><span><?php echo $lang['FILTER'];?></span></a>
             
        <div class="filter">
          <span class="last-items"><?php echo $lang['SHOW_COUNT_ORDER'];?></span>
          <select class="last-items-dropdown countPrintRowsEntity">
            <?php
            foreach(array(10, 20, 30, 50, 100) as $value){
              $selected = '';
              if($value == $countPrintRowsBackRing){
                $selected = 'selected="selected"';
              }
              echo '<option value="'.$value.'" '.$selected.' >'.$value.'</option>';
            }
            ?>
          </select>
        </div>
        <div class="clear"></div>
      </div>
      
      <div class="filter-container" <?php if($displayFilter){echo "style='display:block'";} ?>>
        <?php echo $filter ?>
        <div class="clear"></div>
      </div>
      
    <div class="wrapper-entity-setting">

      
      <div class="clear"></div>
      <!-- Тут начинается верстка таблицы сущностей  -->
      <div class="entity-table-wrap">                
        <div class="clear"></div>
        <div class="entity-settings-table-wrapper">
          <table class="widget-table">          
            <thead>
            <tr>
              <th class="id-width">№</th>       
              <th>                
               <a href="javascript:void(0);" class="field-sorter <?php echo ($sorterData[0]=="add_datetime") ? 'sort-dir-'.$sorterData[3]:'sort-dir-asc' ?>" data-sort="<?php echo ($sorterData[0]=="add_datetime") ? $sorterData[1]*(-1) : 1 ?>" data-field="add_datetime"><?php echo $lang['ADD_DATE'];?></a>
              </th> 
              <th>
               <a href="javascript:void(0);" class="field-sorter <?php echo ($sorterData[0]=="code") ? 'sort-dir-'.$sorterData[3]:'sort-dir-asc' ?>" data-sort="<?php echo ($sorterData[0]=="code") ? $sorterData[1]*(-1) : 1 ?>" data-field="code"><?php echo $lang['ADD_CODE'];?></a>
              </th>
              <th>
                 <a href="javascript:void(0);" class="field-sorter <?php echo ($sorterData[0]=="percent") ? 'sort-dir-'.$sorterData[3]:'sort-dir-asc' ?>" data-sort="<?php echo ($sorterData[0]=="percent") ? $sorterData[1]*(-1) : 1 ?>" data-field="percent"><?php echo $lang['ADD_REPCENT'];?></a>
              </th>              
              <th>
                 <a href="javascript:void(0);" class="field-sorter <?php echo ($sorterData[0]=="`desc`") ? 'sort-dir-'.$sorterData[3]:'sort-dir-asc' ?>" data-sort="<?php echo ($sorterData[0]=="`desc`") ? $sorterData[1]*(-1) : 1 ?>" data-field="`desc`"><?php echo $lang['ADD_DESC'];?></a>
              </th>    
              <th>
               <a href="javascript:void(0);" class="field-sorter <?php echo ($sorterData[0]=="from_datetime") ? 'sort-dir-'.$sorterData[3]:'sort-dir-asc' ?>" data-sort="<?php echo ($sorterData[0]=="from_datetime") ? $sorterData[1]*(-1) : 1 ?>" data-field="from_datetime"><?php echo $lang['ADD_BOT'];?></a>
              </th>
              <th>
               <a href="javascript:void(0);" class="field-sorter <?php echo ($sorterData[0]=="to_datetime") ? 'sort-dir-'.$sorterData[3]:'sort-dir-asc' ?>" data-sort="<?php echo ($sorterData[0]=="to_datetime") ? $sorterData[1]*(-1) : 1 ?>" data-field="to_datetime"><?php echo $lang['ADD_TOP'];?></a>
              </th>
              <th class="actions"><?php echo $lang['ACTIONS'];?>
              </th>
            </tr>
          </thead>
            <tbody class="entity-table-tbody"> 
              <?php 
             
              if (empty($entity)): ?>
                <tr class="no-results">
                  <td colspan="8" align="center"><?php echo $lang['ENTITY_NONE']; ?></td>
                </tr>
                  <?php else: ?>
                    <?php foreach ($entity as $row): ?>
                    <tr data-id="<?php echo $row['id']; ?>">
                      <td><?php echo $row['id']; ?></td>   
                            
                      <td class="add_datetime">                                  
                        <?php  echo MG::dateConvert($row['add_datetime']); ?>                    
                      </td>                    
                      
                      <td class="code">                                  
                        <?php echo $row['code'] ?>                    
                      </td>
                      
                      <td class="percent">                                  
                        <?php echo $row['percent'] ?>                      
                      </td>
                      
                      <td class="desc">  
                         <?php echo $row['desc'] ?>   
                      </td>
                      
                      <td class="from_datetime">                          
                        <?php  echo MG::dateConvert($row['from_datetime']); ?> 
                      </td>
                      
                      <td class="to_datetime">  
                        <?php  echo MG::dateConvert($row['to_datetime']); ?> 
                      </td>                    
                  
                      <td class="actions">
                        <ul class="action-list"><!-- Действия над записями плагина -->
                          <li class="edit-row" 
                              data-id="<?php echo $row['id'] ?>" 
                              data-type="<?php echo $row['type']; ?>">
                            <a class="tool-tip-bottom" href="javascript:void(0);" 
                               title="<?php echo $lang['EDIT']; ?>"></a>
                          </li>
                          <li class="visible tool-tip-bottom  <?php echo ($row['invisible']) ? 'active' : '' ?>" 
                              data-id="<?php echo $row['id'] ?>" 
                              title="<?php echo ($row['invisible']) ? $lang['ACT_V_ENTITY'] : $lang['ACT_UNV_ENTITY']; ?>">
                            <a href="javascript:void(0);"></a>
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
  </div>
              
  <script>  
     $('.section-promo-code  .b-modal .fields-calback input[name="from_datetime"]').datepicker({ dateFormat: "yy-mm-dd" });  
     $('.section-promo-code  .b-modal .fields-calback input[name="to_datetime"]').datepicker({ dateFormat: "yy-mm-dd" });  
  </script>