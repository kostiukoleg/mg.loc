<!--
Доступны переменные:
  $pluginName - название плагина
  $lang - массив фраз для выбранной локали движка
  $options - набор данного плагина хранимый в записи таблиц mg_setting
  $entity - набор записей сущностей плагина из его таблицы
  $pagination - блок навигациицам 
-->

<div class="section-<?php echo $pluginName?>"><!-- $pluginName - задает название секции для разграничения JS скрипта -->

  <!-- Тут начинается Верстка модального окна -->
  <div class="b-modal hidden-form">
    <div class="product-table-wrapper add-news-form">
      <div class="widget-table-title">
        <h4 class="pages-table-icon" id="modalTitle"><?php echo $lang['MODAL_TITLE'];?>Соответствие полей импорта</h4>
        <div class="b-modal_close tool-tip-bottom" title="<?php echo $lang['T_TIP_CLOSE_MODAL'];?>"></div>
      </div>
      <div class="widget-table-body">
        <div class="add-product-form-wrapper">
          <ul>
            
          </ul>
          <div class="clear"></div>
          <button class="save-button tool-tip-bottom" title="<?php echo $lang['T_TIP_SAVE'];?>"><span><?php echo $lang['SAVE'];?></span></button>
          <div class="clear"></div>
        </div>
      </div>
    </div>
  </div>
  <!-- Тут заканчивается Верстка модального окна -->
  
  <!-- Тут начинается верстка видимой части станицы настроек плагина-->
  <div class="widget-table-body">
    <div class="wrapper-entity-setting">
      <div id="settings-tabs">
        <!-- Заголовки -->
        <ul class="tabs-list">
          <li class="ui-state-active">
            <a href="javascript:void(0);" class="tool-tip-top" id="setting-import-tab" title="Импорт каталога из Excel файла"><span>Импорт</span></a>
          </li>
          <li>
            <a href="javascript:void(0);" class="tool-tip-top" id="setting-export-tab" title="Экспорт каталога в Excel файл"><span>Экспорт</span></a>
          </li>
        </ul>
      </div>
      <!-- Тут начинается  Верстка базовых настроек  плагина (опций из таблицы  setting)-->
      <div class="widget-table-action base-settings">
        <div class="setting-block setting-import-block">
          <ul class="list-option"><!-- список опций из таблицы setting-->
            <li class="section"><?php echo $lang['SECTION_SELECT_FILE']?></li>
            <li><label>
              <span class="custom-text"><?php echo $lang['IMPORT_FILE_TYPE'];?>:</span>
              <select name="importType">
                <option value="0"><?php echo $lang['SELECT_FILE_TYPE'];?></option>
                <option value="MogutaCMS"><?php echo $lang['CATALOG_FILE_TYPE'];?></option>
                <option value="MogutaCMSUpdate"><?php echo $lang['UPDATE_FILE_TYPE'];?></option>
              </select>
            </label></li>
            <li><label>
              <form method="post" noengine="true" enctype="multipart/form-data" class="excel-upload-form">
                <span class="custom-text"><?php echo $lang['UPLOAD_FILE']?>:</span> 
                <input type="file" name="upload_data_file" class="tool-tip-right" title="<?php echo $lang['T_TIP_UPLOAD_FILE']?>" disabled="disabled" />
                <span class="upload_file_success" style="display:none;"><?php echo $lang['UPLOAD_FILE_SUCCESS']?></span>
              </form>
            </label></li>
            <li><label>
              <span class="custom-text"><?php echo $lang['SET_COLUMN_COMPLIANCE'];?>:</span>
              <select name="importScheme" disabled="disabled" class="importScheme">
                <option value="default"><?php echo $lang['DEFAULT_IMPORT_SCHEME'];?></option>
                <option value="last"><?php echo $lang['LAST_IMPORT_SCHEME'];?></option>
                <option value="new"><?php echo $lang['NEW_IMPORT_SCHEME'];?></option>
              </select>
            </label></li>
            <li><label>
              <span class="custom-text"><?php echo $lang['CLEAR_CATALOG_MODE'];?>:</span>
              <input type="checkbox" name="clearCatalog" value="" class="tool-tip-right" title="<?php echo $lang['T_TIP_CLEAR_CATALOG_IMPORT']?>"  />
            </label></li>
          </ul>
          <div class="block-console" style="margin-top:20px;">          
            <textarea style="width:600px; height:200px;" disabled="disabled"> </textarea>     
          </div>
          <div class="clear"></div>
          <button class="tool-tip-bottom base-setting-save save-button custom-btn" data-id="" title="<?php echo $lang['START_IMPORT']?>">
            <span><?php echo $lang['START_IMPORT']?></span> <!-- кнопка применения настроек -->
          </button>
        </div>
        <div class="setting-block setting-export-block" style="display: none;">
          <ul class="list-option">
            <li class="section"><?php echo $lang['SECTION_EXPORT_SETTINGS']?></li>
            <li><label>
              <span class="custom-text"><?php echo $lang['EXPORT_ONLY_ACTIVE'];?>:</span>
              <input type="checkbox" name="only_active" value="" class="tool-tip-right" title="<?php echo $lang['T_TIP_EXPORT_ONLY_ACTIVE']?>"  />
            </label></li>
            <li><label>
              <span class="custom-text"><?php echo $lang['EXPORT_ONLY_ON_COUNT'];?>:</span>
              <input type="checkbox" name="only_in_count" value="" class="tool-tip-right" title="<?php echo $lang['T_TIP_EXPORT_ONLY_ON_COUNT']?>"  />
            </label></li>
            <li>
              <span class="custom-text"><?php echo $lang['EXPORT_CATEGORY'];?>:</span>
              <select name="export_category_list" multiple="multiple" size="10">
                <?php foreach ($data['category'] as $key => $value):?>
                    <option value="<?php echo $key?>"><?php echo $value?></option>
                  <?php endforeach;?>
              </select>
            </li>
          </ul>
          <div class="download-export-file">
            <?php if($data['file']):?>
            <a href="<?php echo $data['file']['link']?>">Скачать файл экспорта от <?php echo date('d.m.Y H:i', $data['file']['date'])?></a>
            <?php endif;?>
          </div>
          <div class="clear"></div>
          <button class="tool-tip-bottom export-start save-button custom-btn" data-id="" title="<?php echo $lang['START_EXPORT']?>">
            <span><?php echo $lang['START_EXPORT']?></span> <!-- кнопка применения настроек -->
          </button>
        </div>
        <div class="clear"></div>
      </div>
      <div class="clear"></div>
    </div>
  </div>