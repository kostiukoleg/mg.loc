<!--
Доступны переменные:
  $pluginName - название плагина
  $lang - массив фраз для выбранной локали движка
  $options - набор данного плагина хранимый в записи таблиц mg_setting
  $entity - набор записей сущностей плагина из его таблицы
  $pagination - блок навигациицам 
-->
<div class="section-<?php echo $pluginName?>"><!-- $pluginName - задает название секции для разграничения JS скрипта -->

  <!-- Тут начинается верстка видимой части станицы настроек плагина-->
  <div class="widget-table-body">
    <div class="wrapper-entity-setting">

      <!-- Тут начинается  Верстка базовых настроек  плагина (опций из таблицы  setting)-->
      <div class="widget-table-action base-settings">
        <h3>Настройки плагина</h3>

        <ul class="list-option"><!-- список опций из таблицы setting-->
          <li><label>
            <span class="custom-text"><?php echo $lang['USE_MARGIN']?>:</span> 
            <input type="checkbox" name="use_margin" value="<?php echo $options["use_margin"];?>" <?php echo ($options["use_margin"]!='false')?'checked=checked':''?>>
          </label></li>
          <li><label>
            <span><?php echo $lang['MARGIN']?>:</span> 
            <input type="text" name="margin" value="<?php echo $options['margin'];?>" <?php echo ($options["use_margin"]=='false')?'disabled=disabled':''?> class="tool-tip-right" title="<?php echo $lang['T_TIP_MARGIN']?>"><span>%</span>
          </label></li>
          <li><label>
            <span class="custom-text"><?php echo $lang['USE_AUTO_UPDATE']?>:</span> 
            <input type="checkbox" name="use_auto_update" value="<?php echo $options["use_auto_update"];?>" <?php echo ($options["use_auto_update"]!='false')?'checked=checked':''?>>
          </label></li>
          <li><label>
            <span class="custom-text"><?php echo $lang['AUTO_UPDATE_PRICES']?>:</span> 
            <input type="checkbox" name="auto_update_price" value="<?php echo $options["auto_update_price"];?>" <?php echo (!empty($options["auto_update_price"])&&$options["auto_update_price"]!='false')?'checked=checked':''?>>
          </label></li>
          <li>
              <span style="width: 100%;"><?php echo $lang['LAST_UPDATE_DATETIME'].": ".$lastCurrencyUpdate?></span>
        </ul>

        <button class="tool-tip-bottom base-setting-save save-button custom-btn" data-id="" title="<?php echo $lang['SAVE_MODAL']?>">
          <span><?php echo $lang['SAVE_MODAL']?></span> <!-- кнопка применения настроек -->
        </button>
        <button class="tool-tip-bottom reload-slider update-price custom-btn" data-id="" title="">
          <span><?php echo $lang['UPDATE_PRICES']?></span> <!-- кнопка применения настроек -->
        </button>
        <div class="clear"></div>
      </div>
      <div class="clear"></div>
    </div>
  </div>
</div>