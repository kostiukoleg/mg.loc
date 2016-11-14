<script type="text/javascript">
  includeJS('../mg-plugins/partners-program/js/script.js');
</script>
<link rel='stylesheet' href='../mg-plugins/partners-program/css/style.css' type='text/css' />﻿

<div class="section-partners">
    <div class="widget-table-action">
        <a href="javascript:void(0);" class="show-property-order tool-tip-top" title="Настройки выплат"><span>Настройки выплат</span></a>
        <a href="javascript:void(0);" class="custom-btn list-partners <?php if (1 == $_COOKIE['tab'] || (!isset($_COOKIE['tab']))) echo 'active'; ?>" 
           title="Список партнеров">
            <span>Список парнеров</span></a>
        <a href="javascript:void(0);" class="custom-btn list-request <?php if (2 == $_COOKIE['tab']) echo 'active'; ?>" title="Список запросов от партнеров">
            <span>Запросы от партнеров</span></a>

        <span class="last-items">Выводить записей в таблице:</span>
        <select class="last-items-dropdown countPrintRowsEntity">
            <?php
            foreach (array(10, 20, 30, 50, 100) as $value) {
              $selected = '';
              if ($value == $countPrintRowsPartners) {
                $selected = 'selected="selected"';
              }
              echo '<option value="'.$value.'" '.$selected.' >'.$value.'</option>';
            }
            ?>
        </select>
        <div class="clear"></div>
    </div>

    <div class="property-order-container">    
        <h2>Настройки выплат:</h2>
        <form  class="base-setting" name="base-setting" method="POST">
            <table class="list-option">
                <tr>
                    <td><span>Процент выплат от заказов:</span></td>
                    <td><input type="text" name="percent" value="<?php echo $options["percent"] ?>"> %</td>
                </tr>
                <tr>
                    <td><span>Минимальная сумма для вывода:</span></td>
                    <td><input type="text" name="exitMoneyLimit" value="<?php echo $options["exitMoneyLimit"] ?>" style="width:80px;"> 
                      <?php echo MG::getSetting('currency'); ?></td>
                </tr>
                <tr>
                    <td><span>Период после выполнения заказа до возможности выплаты средств: </span></td>
                    <td><input type="text" name="period" value="<?php echo ($options["period"] ? $options["period"] : 30) ?>" style="width:80px;"> 
                      дней </td>
                </tr>
                <tr>
                    <td><span>Обязательное наличие договора:</span></td>
                    <td><input type="checkbox" name="contract" value="<?php echo $options["contract"] ?>" 
                      <?php echo ($options["contract"] && $options["contract"] != 'false') ? 'checked=cheked' : '' ?> ></td>
                </tr>
                <tr>
                    <td colspan="2">
                        <input type="hidden" name="contractLink" value='<?php echo ($options["contractLink"]) ? $options["contractLink"] : '' ?>'>
                        <a class="custom-btn browseContract"
                          style="display:<?php echo ($options["contractLink"]) ? 'none' : 'inline-block' ?>"><span>Загрузить договор</span></a>
                        <span class="readContract" 
                          style="display:<?php echo ($options["contractLink"]) ? 'inline' : 'none' ?>">Скачать договор:</span>
                        <a href="<?php echo ($options["contractLink"]) ? SITE.$options["contractLink"] : "javascript:void(0);" ?>" 
                           class ="linkToContract">
                          <?php echo ($options["contractLink"]) ? $options["contractLink"] : '' ?></a>
                        <a href="javascript:void(0);" class="del-link-contract" 
                           style="display:<?php echo ($options["contractLink"]) ? 'inline' : 'none' ?>">Удалить</a>
                    </td>
                </tr>
            </table>
            <div class="clear"></div>
        </form>
        <div class="clear"></div>
        <div class='link-success'>
            При обязательном наличии договора - партнер может отправить запрос 
            на выплату денежных средств только, после того, как администратор 
            подтвердит получение договора, подписанного партнером!

        </div>
        <div class="clear"></div>
        <a href="javascript:void(0);" class="base-setting-save custom-btn"><span>Сохранить</span></a>
        <div class="clear"></div>
    </div>

    <div class="b-modal partner hidden-form" id="add-partners-wrapper">
        <div class="product-table-wrapper ">
            <div class="widget-table-title">
                <h4 class="pages-table-icon" id="modalTitle"><?php echo $lang['NEWS_MODAL_TITLE']; ?></h4>
                <div class="b-modal_close tool-tip-bottom" title="Закрыть"></div>
            </div>
            <div class="widget-table-body">
                <div class="partner-form-wrapper">
                    <div class="partners-payment-block">
                        <table class="widget-table partner-order">
                            <thead>
                                <tr>
                                    <th>№ заказа</th>
                                    <th>Сумма</th>
                                    <th>Дата выполнения заказа</th>
                                    <th>Статус</th>
                                    <th class="">Изменить</th>
                                </tr>
                            </thead>
                            <tbody class="partner-order-tbody">
                                <tr>

                                </tr>
                            </tbody>
                        </table>
                        <div class ='error' style="text-align: center; margin: 10px; display: none;"></div>
                        <div class="blockInfo">
                            <table class="info-table">
                            <tr>
                                <td>Текущий баланс:</td>
                                <td><strong><span class="balance">0</span></strong></td>
                            </tr>
                            <tr>
                                <td>Доступно к выплате:</td>
                                <td><strong><span class="exitbalance">0</span></strong></td>
                            </tr>
                            <tr>
                                <td>Всего выплачено:</td>
                                <td><strong><span class="amount">0</span></strong></td>
                            </tr>
                            <tr>
                                <td>Запрошен счет на:</td>
                                <td><strong><span class="request">0</span></strong></td>
                            </tr>
                            <tr>
                                <td>Всего переходов по cсылке партнера:</td>
                                <td><strong><span class="links">0</span></strong></td>
                            </tr>
                            <tr>
                                <td>Оформлено заказов:</td>
                                <td><strong><span class="orders">0</span></strong></td>
                            </tr>
                                <tr>
                                    <td>mail партнера:</td>
                                    <td><strong><span id='email'> </span></strong></td>
                                </tr>
                                <tr>
                                    <td>Процент выплат от заказов:</td>
                                    <td><input type="text" name="percent" value="" style="width:30px;"/> %</td>
                                </tr>
                                <tr>
                                    <td>Заключен договор с партнером</td>
                                    <td><input type="checkbox" name="contract" value=""/></td>
                                </tr>
                                <tr>
                                    <td>Дополнительная информация о пратнере (способ оплаты, контакты и т.п.):</td>
                                    <td><textarea name="about"></textarea></td>
                                </tr>
                        </table>
                        </div>
                        <button class="save-button partner tool-tip-bottom" title="Сохраненить изменения">
                            <span>Сохранить</span>
                        </button>
                        <div class="clear"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Тут заканчивается Верстка модального окна -->

    <!-- начинается верстка модального окна запросов на выплату средств от партнеров -->
    <div class="b-modal request hidden-form" id="request-wrapper">
        <div class="product-table-wrapper ">
            <div class="widget-table-title">
                <h4 class="pages-table-icon" id="requestTitle">Запрос № <span class="id-request"></span></h4>
                <div class="b-modal_close request tool-tip-bottom" title="Закрыть"></div>
            </div>
            <div class="widget-table-body">
                <div class="request-partner">
                    <table>
                        <tr>
                            <td>Запрос oт партнера</td>
                            <td><strong><span class="id-partner"></span></strong></td>
                        </tr>
                        <tr>
                            <td>Сумма на вывод:</td>
                            <td><strong  class="summ-request"></strong></td>
                        </tr>
                        <tr>
                            <td>Сумма получена от заказа(ов) №:</td>
                            <td><strong><span class="orders-request"></span></strong></td>
                        </tr>
                        <tr>
                            <td>Статус :</td>
                            <td>
                                <select class="status-request">
                                    <option value="2">Ожидает оплаты</option>
                                    <option value="1">Выполнен</option>
                                    <option value="4">Отказ</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td>Комментарий для партнера: </td>
                            <td><textarea name="comment"></textarea></td>
                        </tr>
                    </table>

                    <div id="warning"></div>
                    <div class="notify-message">Статус <span class="bold-text">"Выполнен"</span>
                        устанавливается только после реальной выплаты агенту, следующая редакция невозможна. 
                        Выплата производится вне системы. Данным действием в базу заносится информация о дате и количестве выплаты.</div>
                    <div class="popup-footer">
                        <button class="save-button request tool-tip-bottom" title="Нажать для занесения в таблицу выплат">
                            <span>Сохранить</span>
                        </button>
                        <a href="javascript:void(0)" class="close-link custom-btn"><span>Закрыть</span></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Тут заканчивается Верстка модального окна -->


    <!-- Тут начинается  Верстка таблицы  -->
    <div class="widget-table-body partners-table-body" id = "list-partners-table" <?php if (2 == $_COOKIE['tab']) echo 'style="display:none"'; ?>>

        <h1>Таблица со списком партнеров </h1> 
        <div class="main-settings-container">
            <table class="widget-table product-table" >
                <thead>
                    <tr>
                        <th>id партнера</th>
                        <th>e-mail</th>
                        <th>Всего заказов</th>
                        <th>Коммисия в %</th>
                        <th>Выплачено</th>
                        <th class="actions">Действия</th>
                    </tr>
                </thead>
                <tbody class="partner-tbody">

                    <?php
                    if (!empty($partners)) {
                      
                      foreach ($partners as $data) {
                        ?>
                        <tr id="<?php echo $data['id'] ?>">
                            <td class="id">
    <?php echo $data['id'] ?>
                            </td>
                            <td class="email"><?php echo $data['email'] ?></td>
                            <td class="count_orders"><?php echo $data['orders_count'] ?></td>
                            <td class="percent"><?php echo $data['percent'] ?>%</td>
                            <td class="payments_amount"><?php echo MG::priceCourse($data['payments_amount']).' '.MG::getSetting('currency') ?></td>               
                            <td class="actions">
                                <ul class="action-list partners">
                                    <li class="edit-row" id="<?php echo $data['id'] ?>">
                                      <a class="tool-tip-bottom" href="#" title="Редактировать"></a></li>
                                    <li class="delete-order" id="<?php echo $data['id'] ?>">
                                      <a class="tool-tip-bottom" href="#"  title="Удалить"></a></li>
                                </ul>
                            </td>
                        </tr>
                      <?php
                      }
                    } else {
                      ?>

                      <tr class="noneNews"><td colspan="6">Нет партнеров</td></tr>

<?php } ?>

                </tbody>
            </table>
        </div>

<?php echo $pagination ?>
        <div class="clear"></div>
    </div>

    <!-- Тут начинается  Верстка таблицы  -->
    <div class="widget-table-body request-table-body" id = "list-request-table" 
      <?php if (2 != $_COOKIE['tab']) echo 'style="display:none"'; ?>>
        <h1>Таблица запросов на выплату денежных средств от партнеров </h1>

        <div class="main-settings-container">
            <table class="widget-table product-table request" >
                <thead>
                    <tr>
                        <th>№ запроса</th>
                        <th>Дата запроса</th>
                        <th>id партнера</th>
                        <th>№ заказов</th>
                        <th>Сумма</th>
                        <th>Статус</th>
                        <th class="actions">Действия</th>
                    </tr>
                </thead>
                <tbody class="partner-tbody request">
                <?php
                  if (!empty($request)) {
                    foreach ($request as $data) {
                    ?>
                      <tr id="request<?php echo $data['id'] ?>">
                        <td class="request_id">
                          <?php echo $data['id'] ?>
                        </td>
                        <td class="date-request">
                        <?php echo MG::dateConvert($data['date_add']).' ['.date('H:i', strtotime($data['date_add'])).']' ?>
                        </td>
                        <td class="partner-request">
                          <a href="javascript:void(0);" class="partner-link" 
                             data-partnerId = '<?php echo $data['partner_id'] ?>' title="Информация о партнере">
                            <span>О партнере №<?php echo $data['partner_id'] ?></span></a>
                        </td>
                        <td class="order-request">
                          <?php echo $data['orders_numbers'] ?>
                        </td>
                        <td class="payments-request">
                          <?php echo MG::priceCourse($data['summ']).' '.MG::getSetting('currency') ?>
                        </td> 
                        <td class="status-request" data-status ='<?php echo $data['status'] ?>'>
                        <?php
                          $class = 'get-paid';
                          if ($data['status'] == 1) {
                            $class = 'get-paid';
                          }
                          if ($data['status'] == 0 || $data['status'] == 4) {
                            $class = 'dont-paid';
                          }
                          if ($data['status'] == 2) {
                            $class = 'activity-product-true';
                          }
                          echo "<span class='".$class."'> ".$statusRequest[$data['status']]."</span>
                            <p class='comment' style='display:none'>".$data['comment']."</p></td>";
                          ?>      
                          <td class="actions">
                            <ul class="action-list request">
                              <li class="edit-row" id="<?php echo $data['id'] ?>"><a class="tool-tip-bottom" href="#" title="Редактировать"></a></li>
                              <li class="delete-order" id="<?php echo $data['id'] ?>"><a class="tool-tip-bottom" href="#"  title="Удалить"></a></li>
                            </ul>
                          </td>
                        </tr>
  <?php
  }
} else {
  ?>
                      <tr class="noneRequest"><td colspan="7">Нет запросов</td></tr>

        <?php } ?>
                </tbody>
            </table>
        </div>
<?php echo $pageRequest ?>
        <div class="clear"></div>
    </div>
</div>
