/* 
 * Модуль  brandPlugin, подключается на странице настроек плагина.
 */

var brandPlugin = (function () {
  return {
    lang: [], // локаль плагина 
    supportCkeditor: null,
    init: function () {

      // установка локали плагина 
      admin.ajaxRequest({
        mguniqueurl: "action/seLocalesToPlug",
        pluginName: 'brand'
      },
      function (response) {
        brandPlugin.lang = response.data;
      });
      // отмена на запрос экспортировать характеристику
      $('.admin-center').on('click', '.section-brand .no-old-characteristic', function () {
        var id = $(this).data('property');
        var data = eval("(" + '{"propertyId":"' + id + '", "first":"false"}' + ")");
        admin.ajaxRequest({
          mguniqueurl: "action/saveBaseOption", // действия для выполнения на сервере
          pluginHandler: 'brand', // плагин для обработки запроса
          data: data,
        },
          function () {
            $('.section-brand .first-settings').remove();
          });
      });
      // экспорт значений из другой харакетистики 
      $('.admin-center').on('click', '.section-brand .export-old-characteristic', function () {
        var id = $(this).data('property');
        admin.ajaxRequest({
          mguniqueurl: "action/getAllCharact", // действия для выполнения на сервере
          pluginHandler: 'brand', // плагин для обработки запроса
          id: id
        },
        function (response) {
          var header = '<span class="link-result">Выберите значения какой характеристики копировать, затем нажмите далее:</span>'
          var html = '<select name="property"><option value=0>Выберите характеристику</option>';
          response.data.forEach(function (element) {
            html += '<option value="' + element.id + '">' + element.name + '</option>';
          })
          html += '</select>';
          var button = '<a href="javascript:void(0);" class="custom-btn next-export" data-property="' + id + '"><span>Далее</span></a>';
          $('.section-brand .first-settings').after('<div class="second-step-settings">' + header + html + button + '</div>');
          $('.section-brand .first-settings').hide();
        });
      });
        // перезаписать значения из другой (строковой) харакетистики
      $('.admin-center').on('click', '.section-brand .copy-old-characteristic', function () {
        var id = $(this).data('property');
        admin.ajaxRequest({
          mguniqueurl: "action/getAllCharact", // действия для выполнения на сервере
          pluginHandler: 'brand', // плагин для обработки запроса
          id: id
        },
        function (response) {
          var header = '<span class="link-result">Выберите значения какой характеристики копировать, затем нажмите далее:</span>'
          var html = '<select name="property"><option value=0>Выберите характеристику</option>';
          response.data.forEach(function (element) {
            html += '<option value="' + element.id + '">' + element.name + '</option>';
          })
          html += '</select>';
          var button = '<a href="javascript:void(0);" class="custom-btn next-copy" data-property="' + id + '"><span>Далее</span></a>';
          $('.section-brand .wrapper-entity-setting').before('<div class="second-step-settings">' + header + html + button + '</div>');
          $('.section-brand .first-settings').hide();
        });
      });
      // экспорт значений из другой харакетистики второй 
      $('.admin-center').on('click', '.section-brand .next-export', function () {
        var id = $(this).data('property');
        var from = $('.second-step-settings select[name="property"]').val();
        if (from == 0) {
          admin.indication('error', 'Необходимо выбрать характеристику');
          return false;
        }
        admin.ajaxRequest({
          mguniqueurl: "action/copyProperty", // действия для выполнения на сервере
          pluginHandler: 'brand', // плагин для обработки запроса
          from: from,
          to: id,
        },
          function (response) {
            admin.indication(response.status, response.msg);
            $('.section-brand .second-step-settings').remove();
            admin.refreshPanel();
          });
      });
      // копирование новых значений из характеристики строковой 
      $('.admin-center').on('click', '.section-brand .next-copy', function () {
        var id = $(this).data('property');
        var from = $('.second-step-settings select[name="property"]').val();
        if (from == 0) {
          admin.indication('error', 'Необходимо выбрать характеристику');
          return false;
        }
        admin.ajaxRequest({
          mguniqueurl: "action/copyNewProperty", // действия для выполнения на сервере
          pluginHandler: 'brand', // плагин для обработки запроса
          from: from,
          to: id,
        },
          function (response) {
            admin.indication(response.status, response.msg);
            admin.refreshPanel();
          });
      });
      
      // удаление бренда 
      $('.admin-center').on('click', '.section-brand .mg-brand-table .delete-row', function () {
        var id = $(this).data('id');
        var brand = $('.section-brand .mg-brand-table').find('tr[data-id=' + id + '] .brand').text();
        brand = $.trim(brand);
        brandPlugin.deleteBrand(id, brand);

      });
      // Выводит модальное окно для добавления
      $('.admin-center').on('click', '.section-brand .add-new-button', function () {
        brandPlugin.showModal('add');
      });
      // Выводит модальное окно для редактирования
      $('.admin-center').on('click', '.section-brand .edit-row', function () {
        var id = $(this).data('id');
        brandPlugin.showModal('edit', id);
      });
      // Выбор логотипа
      $('.admin-center').on('click', '.section-brand .browseImage', function () {
        admin.openUploader('brandPlugin.getLogo');
      });
      // Сохраняет изменения в модальном окне
      $('.admin-center').on('click', '.section-brand .b-modal .save-button', function () {
        var id = $(this).data('id');
        brandPlugin.saveField(id);
      });
      // Устанавливает количиство выводимых записей в этом разделе.
      $('.admin-center').on('change', '.section-brand .countPrintRowsEntity', function () {
        var count = $(this).val();
        admin.ajaxRequest({
          mguniqueurl: "action/countPrintRowsEntity",
          pluginHandler: 'brand',
          count: count
        },
        function (response) {
          admin.refreshPanel();
        });
      });
    },
    /* открывает модальное окно 
     * @param {type} type -тип окна, для редактирования или для добавления
     * @param {type} id - номер записи, которая открыта на редактирование
     * @returns {undefined}
     */
    showModal: function (type, id) {
      switch (type) {
        case 'add':
        {
          brandPlugin.clearField();
          $('.section-brand .mg-brand-info input[name="brand"]').show();
          break;
        }
        case 'edit':
        {
          try {
            if (CKEDITOR.instances['html_content']) {
              CKEDITOR.instances['html_content'].destroy();
            }
          } catch (e) {
          }
          brandPlugin.clearField();
          brandPlugin.fillField(id);
          break;
        }
        default:
        {
          break;
        }
      }
      admin.openModal($('.b-modal'));
      $('.section-brand textarea[data-name=html_content]').ckeditor();
    },
    /**
     * Очистка модального окна
     */
    clearField: function () {
      $('.section-brand .b-modal .save-button').data('id', '');
      $('.section-brand .mg-brand-info .brand-name').text('');
      $('.section-brand textarea[data-name=html_content]').val('');
      $('.section-brand .mg-brand-info img').removeAttr('src');
      $('.section-brand .mg-brand-info .brand-name').hide();
      $('.section-brand .mg-brand-info input[name=brand]').hide();
      brandPlugin.supportCkeditor = '';
    },
    /**
     * Заполнение модального окна данными из БД
     * @param {type} id
     * @returns {undefined}
     */
    fillField: function (id) {
      admin.ajaxRequest({
        mguniqueurl: "action/getEntity", // действия для выполнения на сервере
        pluginHandler: 'brand', // плагин для обработки запроса
        id: id // id записи
      },
      function (response) {
        brandPlugin.supportCkeditor = response.data.desc;
        $('.section-brand .b-modal .save-button').data('id', response.data.id);
        $('.section-brand .mg-brand-info .brand-name').text(response.data.brand);
        $('.section-brand .mg-brand-info .brand-name').show();
        $('.section-brand .mg-brand-info img').attr('src', response.data.url);
        $('.section-brand .b-modal .mg-brand-info input[name="logo"] ').val(response.data.url);
        $('.section-brand .b-modal .mg-brand-info textarea[data-name=html_content]').ckeditor(function () {
          this.setData(brandPlugin.supportCkeditor);
        });
      }
      );
    },
    /**
     * функция для приема файла из аплоадера
     */
    getLogo: function (file) {
      $('.section-brand .b-modal  input[name="logo"]').val(file.url);
      $('.section-brand .b-modal  .logo-brand').attr('src', file.url);
    },
    /**
     * Сохранение данных из модального окна
     * @param {type} id
     * @returns {undefined}
     */
    saveField: function (id) {
      var desc = $('.section-brand .mg-brand-info textarea[data-name=html_content]').val();
      var url = $('.section-brand .mg-brand-info input[name="logo"]').val();
      var brand;
      if (id) {
        brand = $('.section-brand .mg-brand-info .brand-name').text();
      } else {
        brand = $('.section-brand .mg-brand-info input[name="brand"]').val();
      }
      admin.ajaxRequest({
        mguniqueurl: "action/saveEntity", // действия для выполнения на сервере
        pluginHandler: 'brand', // плагин для обработки запроса
        id: id,
        desc: desc,
        url: url,
        brand: brand,
      },
        function (response) {
          admin.indication(response.status, response.msg);
          if (id) {
            var replaceTr = $('.mg-brand-tbody tr[data-id=' + id + ']');
            brandPlugin.drawRow(response.data.row, replaceTr); // перерисовка строки новыми данными
          } else {
            brandPlugin.drawRow(response.data.row); // добавление новой записи         
          }
          admin.closeModal($('.b-modal'));
        }
      );
    },
    /**    
     * Отрисовывает  строку сущности в главной таблице
     * @param {type} data - данные для вывода в строке таблицы
     */
    drawRow: function (data, replaceTr) {
      var url = (data.url ? data.url : admin.SITE + '/mg-admin/design/images/no-img.png');
      var tr = '\
        <tr data-id="' + data.id + '">\
        <td><img width="70px" height="auto" class="uploads" src="' + url + '"/></td>\
        <td class="brand">' + data.brand + '</td>\
        <td>' + data.desc + '</td>\
        <td class="actions">\
          <ul class="action-list">\
            <li class="edit-row" data-id="' + data.id + '" ><a class="tool-tip-bottom" href="javascript:void(0);" title="' + brandPlugin.lang['EDIT'] + '"></a></li>\
            <li class="delete-row" data-id="' + data.id + '"><a class="tool-tip-bottom" href="javascript:void(0);"  title="' + brandPlugin.lang['DELETE'] + '"></a></li>\
          </ul>\
        </td>\
        </tr>';
      if (!replaceTr) {
        if ($('.section-brand .mg-brand-tbody tr').length > 0) {
          $('.section-brand .mg-brand-tbody tr:first').before(tr);
        }
        else {
          $('.section-brand .mg-brand-tbody').append(tr);
        }
        $('.section-brand .mg-brand-tbody .no-results').remove();
      }
      else {
        replaceTr.replaceWith(tr);
      }
      admin.initToolTip();
    },
    deleteBrand: function (id, brand) {
      var msg = brandPlugin.lang['CONFIRM_DEL'];
      if (confirm(msg)) {
        admin.ajaxRequest({
          mguniqueurl: "action/deleteBrand", // действия для выполнения на сервере
          pluginHandler: 'brand', // плагин для обработки запроса
          id: id,
          brand: brand,
        },
          function (response) {
            admin.indication(response.status, response.msg);
            $('.section-brand .mg-brand-table tr[data-id=' + id + ']').remove();
            if ($('.section-brand .mg-brand-table tr').length == 0) {
              var html = '<tr class="no-results">\
                  <td colspan="4" align="center">' + brandPlugin.lang['ENTITY_NONE'] + '</td>\
                  </tr>';
              $(".mg-brand-tbody").append(html);
            }
          });
      }
      return false;
    }
  }
})();

brandPlugin.init();
