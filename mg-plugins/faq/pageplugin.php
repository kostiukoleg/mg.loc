<!--
Доступны переменные:
  $pluginName - название плагина
  $lang - массив фраз для выбранной локали движка
  $options - набор данного плагина хранимый в записи таблиц mg_setting - количесвто записей
  $entity - набор записей сущностей плагина из его таблицы
  $pagination - блок навигациицам 
-->

<div class="section-<?php echo $pluginName ?>"><!-- $pluginName - задает название секции для разграничения JS скрипта -->
    <!-- Тут начинается Верстка модального окна -->
    <div class="b-modal hidden-form add-faq-question">
        <div class="custom-table-wrapper"><!-- блок для контента модального окна -->
            <div class="widget-table-title"><!-- Заголовок модального окна -->
                <h4 class="pages-table-icon" id="modalTitle">
                    <?php echo $lang['HEADER_MODAL_ADD']; ?>
                </h4><!-- Иконка + Заголовок модального окна -->
                <div class="b-modal_close tool-tip-bottom" title="<?php echo $lang['CLOSE_MODAL']; ?>"></div><!-- Кнопка для закрытия окнаа -->
            </div>
            <div class="widget-table-body slide-editor"><!-- Содержимое окна, управляющие элементы -->
                <ul class="text-list">
                    <li>
                        <span class="custom-text">Вопрос:</span>
                        <input type="text" data-name="question" class="question-input" value=""/>
                        <div class="errorField" data-error = "question">Введите Ваш вопрос!</div>
                    </li>
                </ul>
                <div class="clear"></div>
                <!-- Добавление ответа -->
                <div class="product-desc-wrapper">
                    <span class="custom-text"><?php echo $lang['ANSWER']; ?>:</span>
                    <textarea class="product-desc-field" data-name="html_content"></textarea>
                     <div class="errorField" data-error = "answer">Введите Ваш ответ!</div>
                </div>

                <button class="save-button tool-tip-bottom" data-id="" title="
                        <?php echo $lang['SAVE_MODAL'] ?>"><!-- Кнопка действия -->
                    <span><?php echo $lang['SAVE_MODAL'] ?></span>
                </button>
                <div class="clear"></div>
            </div>
        </div>
    </div>
    <!-- Тут заканчивается Верстка модального окна -->
    <!--Кнопка добавления новой записи -->
    <div class="widget-table-action">
        <div class="add-new-button tool-tip-bottom" title="<?php echo $lang['ADD_MODAL']; ?>">
            <span><?php echo $lang['ADD_MODAL']; ?></span>
        </div>

        <div class="filter" >
            <span class="last-items"><?php echo $lang['SHOW_PRODUCT_QUEST']; ?></span>
            <select class="last-items-dropdown countPrintRowsQuest">
                <?php
                foreach (array(5, 10, 30, 50, 100) as $value) {
                  $selected = '';
                  if ($value == $countPrintRowsQuest) {
                    $selected = 'selected="selected"';
                  }
                  echo '<option value="'.$value.'" '.$selected.' >'.$value.'</option>';
                }
                ?>
            </select>
        </div>
    </div>
    <!-- Тут начинается верстка видимой части станицы настроек плагина-->
    <div class="wrapper-entity-setting">
        <!-- Тут начинается верстка таблицы сущностей  -->
        <div class="entity-table-wrap">
            <div class="entity-settings-table-wrapper">
                <table class="widget-table">
                    <thead>
                        <tr>
                            <th>Вопрос</th>
                            <th class="actions">Действия</th>
                        </tr>
                    </thead>
                    <tbody class="entity-table-tbody">
                        <?php if (empty($entity)): ?>
                          <tr class="no-results">
                              <td colspan="3" align="center"><?php echo $lang['ENTITY_NONE']; ?></td>
                          </tr>
                        <?php else: ?>
                          <?php foreach ($entity as $row): ?>
                            <tr data-id="<?php echo $row['id']; ?>">
                                <td>
                                    <p> <?php echo $row['question'] ?></p>
                                </td>
                                <td class="actions">
                                    <ul class="action-list"><!-- Действия над записями плагина -->
                                        <li class="edit-row"
                                            data-id="<?php echo $row['id'] ?>"
                                            data-question="<?php echo $row['question'] ?>">
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
        <?php echo $pagination ?>  <!-- Вывод навигации -->
    </div>
</div>