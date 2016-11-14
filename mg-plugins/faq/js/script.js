/* 
 * Модуль  faqModule, подключается на странице настроек плагина.
 */

var faqModule = (function () {  
  return {
    lang: [], // локаль плагина 
    init: function () {
       // установка локали плагина 
      admin.ajaxRequest({
        mguniqueurl: "action/seLocalesToPlug",
        pluginName: 'faq'
      },
      function (response) {
        faqModule.lang = response.data;
      } );
    // Выводит модальное окно для добавления
    $('.admin-center').on('click', '.section-faq .add-new-button', function () {
      faqModule.showModal('add');
    });
    // Выводит модальное окно для редактирования
    $('.admin-center').on('click', '.section-faq .edit-row', function () {
      var id = $(this).data('id');
      faqModule.showModal('edit', id);
    });

    // Сохраняет изменения в модальном окне
    $('.admin-center').on('click', '.section-faq .b-modal .save-button', function () {
      var id = $(this).data('id');
      faqModule.saveField(id);
    });
    
    // Устанавливает количиство выводимых записей в этом разделе.
    $('.admin-center').on('change', '.section-faq .countPrintRowsQuest', function () {
      var count = $(this).val();
      admin.ajaxRequest({
        mguniqueurl: "action/setCountPrintRowsQuest",
        pluginHandler: 'faq',
        count: count
      },
     function (response) {
     admin.refreshPanel();
     });
    });

    // Удаляет запись
    $('.admin-center').on('click', '.section-faq .delete-row', function () {
      var id = $(this).data('id');
      faqModule.deleteEntity(id);
    });
    },
    /* открывает модальное окно 
     * @param {type} type -тип окна, для редактирования или для добавления
     * @param {type} id - номер записи, которая открыта на редактирование
     * @returns {undefined}
    */
    showModal: function (type, id) {
      switch (type) {
        case 'add': {
          faqModule.clearField();
          break;
        }
        case 'edit': {
          faqModule.clearField();
          faqModule.fillField(id);
          break;
        }
        default: {
          break;
        }
      }
      admin.openModal($('.b-modal'));
      $('textarea[data-name=html_content]' ).ckeditor(); 
    },
    /**
     * Очистка модального окна
    */
    clearField: function () {
      $('.section-faq .b-modal input').val('');
      $('.section-faq .b-modal .save-button').data('id', '');
      $('.section-faq input[data-name=question]').val('');
      $('.section-faq textarea[data-name=html_content]').val('');
      $(".errorField").hide();
    },
    /**
     * Заполнение модального окна данными из БД
     * @param {type} id
     * @returns {undefined}
    */
    fillField: function (id) {
      admin.ajaxRequest({
        mguniqueurl: "action/getEntity", // действия для выполнения на сервере
        pluginHandler: 'faq', // плагин для обработки запроса
        id: id // id записи
      },
      function (response) {
        $('.section-faq .b-modal  input[data-name="question"]').val(response.data.question);
        $('.section-faq .b-modal  textarea[data-name=html_content]').val(response.data.answer);
        $('.section-faq .b-modal .save-button').data('id', response.data.id);
      },
      $('.b-modal .widget-table-body') // вывод лоадера в контейнер окна, пока идет загрузка данных
      );
    },
    /**
     * Сохранение данных из модального окна
     * @param {type} id
     * @returns {undefined}
    */
    saveField: function (id) {
      var question = $('.section-faq .slide-editor input[data-name=question]').val();
      var answer = $('.section-faq .slide-editor textarea[data-name=html_content]').val();
        if (question && (answer!=="")) {
        $(".errorField").hide();
        admin.ajaxRequest({
        mguniqueurl: "action/saveEntity", // действия для выполнения на сервере
        pluginHandler: 'faq', // плагин для обработки запроса
        id: id,
        answer: answer,
        question: question
        },
        function (response) {
        admin.indication(response.status, response.msg);
        if (id) {
          var replaceTr = $('.entity-table-tbody tr[data-id=' + id + ']');
          faqModule.drawRow(response.data.row, replaceTr); // перерисовка строки новыми данными
        } 
        else {
          faqModule.drawRow(response.data.row); // добавление новой записи         
        }
        admin.closeModal($('.b-modal'));
        
        faqModule.clearField();
        },
        $('.b-modal .widget-table-body') // на месте кнопки
        );
      }
      else if (!question) {
        $('[data-error=question]').show();
        $('[data-name=question]').keydown(function() {
        $('[data-error=question]').hide();
        });
        }
        else {
          $('[data-error=answer]').show();;
        }
    },
    /**    
     * Отрисовывает  строку сущности в главной таблице
     * @param {type} data - данные для вывода в строке таблицы
    */
    drawRow: function (data, replaceTr) {
      var question = " <p class=> " + data.question + "</p>";
      var tr = '\
        <tr data-id="' + data.id + '">\
        <td>' + question + '</td>\
        <td class="actions">\
          <ul class="action-list">\
            <li class="edit-row" data-id="' + data.id + '" data-question="' + data.question + '" ><a class="tool-tip-bottom" href="javascript:void(0);" title="' + lang.EDIT + '"></a></li>\
            <li class="delete-row" data-id="' + data.id + '"><a class="tool-tip-bottom" href="javascript:void(0);"  title="' + lang.DELETE + '"></a></li>\
          </ul>\
        </td>\
        </tr>';
      if (!replaceTr) {
        if ($('.entity-table-tbody tr').length > 0) {
          $('.entity-table-tbody tr:first').before(tr);
        } 
        else {
          $('.entity-table-tbody').append(tr);
        }
        $('.entity-table-tbody .no-results').remove();
      } 
      else {
        replaceTr.replaceWith(tr);
      }
    },
    /**    
     * Удаляет  строку сущности в главной таблице
     * @param {type} data - данные для вывода в строке таблицы
    */
    deleteEntity: function (id) {
      if (!confirm(lang.DELETE + '?')) {
        return false;
      }
      admin.ajaxRequest({
        mguniqueurl: "action/deleteEntity", // действия для выполнения на сервере
        pluginHandler: 'faq', // плагин для обработки запроса
        id: id
      },
      function (response) {
        admin.indication(response.status, response.msg);
        $('.entity-table-tbody tr[data-id=' + id + ']').remove();
        if ($(".entity-table-tbody tr").length == 0) {
          var html = '<tr class="no-results">\
            <td colspan="3" align="center">' + faqModule.lang['ENTITY_NONE'] + '</td>\
            </tr>';
          $(".entity-table-tbody").append(html);
        };
      });
    }
    }
  })();
admin.initToolTip();
faqModule.init();
admin.sortable('.entity-table-tbody', 'faq');