/**
 * Модуль для  раздела "парнеров".
 */

var partner = (function () {
  return {
    /**
     * Инициализирует обработчики для кнопок и элементов раздела.
     */
    init: function () {

      // Вызов модального окна при нажатии на кнопку изменения.
      $('.admin-center').on('click', '.section-partners .partners .edit-row', 
      function () {
        partner.openModalWindow('edit', $(this).attr('id'));
      });

      // Вызов модального окна при нажатии на кнопку изменения.
      $('.admin-center').on('click', '.partner-link', function () {
        partner.openModalWindow('edit', $(this).attr('data-partnerId'));
      });

      // Удаления.
      $('.admin-center').on('click', '.section-partners .partners .delete-order', 
      function () {
        partner.deletePartner($(this).attr('id'));
      });

      // Вызов модального окна при нажатии на кнопку промсмотра 
      // запроса средств от партнера по данному заказу
      $('.admin-center').on('click', '.section-partners .partner-order-tbody .see-order', 
      function () {
        // admin.closeModal($('.b-modal'));
        partner.openModalWindow('request', $(this).attr('id'));

      });

      // удаление заказа 
      $('.admin-center').on('click', '.section-partners .partner-order-tbody .delete-order', 
      function () {
        partner.deleteOrder($(this).attr('id'));

      });

      // Выплата.
      $('body').on('click', '.request-partner .save-button.request', function () {
        partner.paymentToPartner($(this).attr('id'));
      });

      /** изменение информации о партнере */
      $('body').on('click', '.partners-payment-block .save-button.partner', function () {
        var id = $(this).attr('id');
        var obj = '{';
        $('.blockInfo input, .blockInfo textarea').each(function () {
          obj += '"' + $(this).attr('name') + '":"' + $(this).val() + '",';
        });
        obj += '"id":"' + id + '",';
        obj += '}';

        //преобразуем полученные данные в JS объект для передачи на сервер
        var data = eval("(" + obj + ")");

        admin.ajaxRequest({
          mguniqueurl: "action/saveInfoPartner", // действия для выполнения на сервере
          pluginHandler: 'partners-program', // плагин для обработки запроса
          data: data // 
        },
        function (response) {
          $('.partner-tbody tr#' + id + ' .percent').text(response.data.percent);
          admin.indication(response.status, response.msg);
          admin.closeModal($('.b-modal'));
        }

        );
      });

      // Сохраняет базовые настройки
      $('.admin-center').on('click', '.section-partners .base-setting-save', function () {

        var obj = '{';
        $('.section-partners .list-option input').each(function () {
          obj += '"' + $(this).attr('name') + '":"' + $(this).val() + '",';
        });
        obj += '}';

        //преобразуем полученные данные в JS объект для передачи на сервер
        var data = eval("(" + obj + ")");

        admin.ajaxRequest({
          mguniqueurl: "action/saveBaseOption", // действия для выполнения на сервере
          pluginHandler: 'partners-program', // плагин для обработки запроса
          data: data // id записи
        },
        function (response) {
          admin.indication(response.status, response.msg);
          admin.refreshPanel();
        }

        );

      });

      // Показывает панель с настройками.
      $('.admin-center').on('click', '.section-partners .show-property-order', function () {
        $('.property-order-container').slideToggle(function () {
          $('.widget-table-action').toggleClass('no-radius');
        });
      });

      // загрузка договора 
      $('.admin-center').on('click', '.section-partners .browseContract', function () {
        admin.openUploader('partner.getContract');
      });

      // Удаляет ссылку на электронный товар
      $('.admin-center').on('click', '.section-partners .del-link-contract', function () {
        $('.section-partners input[name="contractLink"]').val('');
        $(this).hide();
        $('.section-partners .readContract').hide();
        $('.section-partners .browseContract').show();
        $('.section-partners .linkToContract').attr('href', 'javascript:void(0);');
        $('.section-partners .linkToContract').text('');
      });

      // Показывает список партнеров 
      $('.admin-center').on('click', '.section-partners .list-partners', function () {
        cookie("tab", "1");
        admin.refreshPanel();

      });

      // Показывает список запросов от партнеров 
      $('.admin-center').on('click', '.section-partners .list-request', function () {
        cookie("tab", "2");
        $('#list-partners-table').slideUp();
        $('.list-partners').removeClass('active');
        $('#list-request-table').slideToggle();
        $('.list-request').toggleClass('active');
      });

      // Вызов модального окна при нажатии на кнопку изменения запроса на вывод средств
      $('.admin-center').on('click', '.section-partners .request .edit-row', function () {
        partner.openModalWindow('request', $(this).attr('id'));
      });
      // удаление запроса партнера 
      $('.admin-center').on('click', '.section-partners .request .delete-order', function () {
        partner.deleteRequest($(this).attr('id'));
      });

      // обработчик закрытия окна
      $('body').on('click', '.b-modal_close.request', function () {
        $('.admin-center .b-modal.partner').css('z-index', 99);
        admin.closeModal($(this).closest('.b-modal.request'));

      });

      // обработчик закрытия окна
      $('body').on('click', '.close-link', function () {
        $('.admin-center .b-modal.partner').css('z-index', 99);
        admin.closeModal($(this).closest('.b-modal.request'));
      });

      // обработчик закрытия окна
      $('body').on('click', '.b-modal-close.partner', function () {
        admin.closeModal($(this).closest('.b-modal.partner'));
      });

      // Устанавливает количиство выводимых записей в этом разделе.
      $('.admin-center').on('change', '.section-partners .countPrintRowsEntity', function () {
        var count = $(this).val();
        admin.ajaxRequest({
          mguniqueurl: "action/setCountPrintRowsEnity",
          pluginHandler: 'partners-program',
          option: 'countPrintRowsPartners',
          count: count
        },
        function (response) {
          admin.refreshPanel();
        }
        );

      });

    },
    /**
     * Открывает модальное окно.
     * type - тип окна, либо для создания нового товара, либо для редактирования старого.
     */
    openModalWindow: function (type, id) {

      switch (type) {
        case 'edit':
        {
          partner.clearFileds();
          $('#modalTitle').text('Подробная информация');
          partner.editPage(id);

          // Вызов модального окна.
          admin.openModal($('.b-modal.partner'));
          break;
        }
        case 'add':
        {
          $('#modalTitle').text('Добавить партнера');
          partner.clearFileds();
          break;
        }
        case 'request':
        {
          partner.clearFiledsRequestModal();
          partner.editRequest(id);
          break;

        }
        default:
        {
          partner.clearFileds();
          break;
        }
      }



  },
    /**
     * Производит списание средств со счета партнера, заносит информацию в историю по выплатам
     */
    paymentToPartner: function (id) {
      var statusRequest = new Array('Не доступен', 'Выполнен', 'Ожидает оплаты', '', 'Отказ');
      var newStatus = $('.request-partner .status-request').find('option:selected').val();
      var comment = $('.request-partner textarea[name=comment]').val();
      var adop = "Согласием Вы подтверждаете, что выплата партнеру по данному запросу произведена.";
      var refuse = "Согласием Вы подтверждаете, что причины \
            отказа обоснованы и партнер будет проинформирован об этом.";
      if (newStatus == 1) {
        message = adop;
      }
      else if (newStatus == 4) {
        message = refuse;
      }
      else {
        message = 'Сохранить изменения?';
      }

      if (confirm(message)) {
        // отправка данных на сервер для сохранеиня
        admin.ajaxRequest({
          mguniqueurl: "action/paymentToPartner", // действия для выполнения на сервере
          pluginHandler: 'partners-program', // плагин для обработки запроса
          request_id: id,
          status: newStatus,
          comment: comment
        },
        function (response) {
          var color = 'get-paid';
          if (response.data.request.status == 1) {
            color = 'get-paid';
          }
          if (response.data.request.status == 0 || response.data.request.status == 4) {
            color = 'dont-paid';
          }
          if (response.data.request.status == 2) {
            color = 'activity-product-true';
          }
          var partner_id = $('.partner-tbody.request #request' + response.data.request.request_id).find('.partner-request .partner-link').attr('data-partnerid');
          admin.indication(response.status, response.msg);
          $('.partner-tbody.request #request' + response.data.request.request_id).find('.status-request').attr('data-status', response.data.request.status);
          $('.partner-tbody.request #request' + response.data.request.request_id + ' .status-request').html('\
            <span class=' + color + '>' + statusRequest[response.data.request.status] + ' </span>\n\
            <p class="comment" style="display:none">' + response.data.request.comment + '</p>');
          $('.partner-order .partner-order-tbody tr .change ul #' + response.data.request.request_id + '.see-order').closest('tr').find('.status').text(statusRequest[response.data.request.status]);

          admin.closeModal($('.product-table-wrapper').closest('.b-modal.request'));
          $('.admin-center .b-modal.partner').css('z-index', 99);
        }
        );
      }
    },
    /**
     * Получает данные о новости с сервера и заполняет ими поля в окне.
     */
    editPage: function (id) {
      admin.ajaxRequest({
        mguniqueurl: "action/getPartnerBalanse",
        pluginHandler: 'partners-program', // имя папки в которой лежит данный плагин
        id: id
      },
      partner.fillFileds()
        );
    },
    /**
     * Получает данные о новости с сервера и заполняет ими поля в окне.
     */
    editRequest: function (id) {
      admin.ajaxRequest({
        mguniqueurl: "action/getPartnerRequest",
        pluginHandler: 'partners-program', // имя папки в которой лежит данный плагин
        id: id
      },
      partner.fillFiledsRequestModal()
        );
    },
    /**
     * Заполняет поля модального окна данными
     */
    fillFileds: function () {
      return (function (response) {
        $('.blockInfo .balance').text(response.data.info.balance + ' ' + admin.CURRENCY);
        $('.blockInfo .amount').text(response.data.info.amount + ' ' + admin.CURRENCY);
        $('.blockInfo .exitbalance').text(response.data.info.exitbalance + ' ' + admin.CURRENCY);
        $('.blockInfo .request').text(response.data.info.request + ' ' + admin.CURRENCY);
        $('.blockInfo .links').text(response.data.info.links);
        $('.blockInfo .orders').text(response.data.info.orders);
        $('.blockInfo #email').text(response.data.info.email);
        $('.save-button.partner').attr('id', response.data.info.id);
        var status = ['Не доступен', 'Выплачен', 'Ожидает оплаты', 'Доступен', 'Отказ'];
        var orders = response.data.order;
        if (orders) {
          orders.forEach(function (object) {
            var tr = '\
                <tr id="' + object.order_id + '" >\
                <td class="odred_id"> ' + object.order_number + '</td>\
                <td class="summ">' + object.summ + ' ' + admin.CURRENCY + '</td>\
                <td class="date_done">' + object.date_done + '</td>\
                <td class="status">' + status[object.status] + '</td>\
                <td class="change">\
                  <ul class="action-list partner-orders">'
            if (object.request_id != 0) {
              tr += ' \<li class="see-order" id="' + object.request_id + '">\
                  <a class="tool-tip-bottom" href="#" title="Смотреть запрос средств по заказу"></a></li>\
                  <li class="delete-order" id="' + object.order_id + '">\n\
                    <a class="tool-tip-bottom" href="#"  title="Удалить"></a></li>\
                  </ul>\
                </td>\
                </tr>';
            }
            else {
              tr += ' \<span>запрос средств и</br> удаление заказа недоступно</span>\
                </td>\
                </tr>';
            }
            ;
            if ($('.partner-order .partner-order-tbody tr').length > 0) {
              $('.partner-order .partner-order-tbody tr:last').after(tr);
            } else {
              $('.partner-order .partner-order-tbody').append(tr);
            }
            $("#" + object.order_id + " .change option[value=" + object.status + "]").attr('selected', 'selected');
          });
        }
        else {
          var tr = '\<tr class="noneRequest"><td colspan="5">Выполненных заказов нет</td></tr>';
          $('.partner-order .partner-order-tbody').append(tr);
        }
        $('.blockInfo input[name=percent]').val(response.data.info.percent);
        $('.blockInfo textarea[name=about]').val(response.data.info.about);
        if (response.data.info.contract == 1) {
          $('.blockInfo input[name=contract]').val(true);
          $('.blockInfo input[name=contract]').prop('checked', true);
        }

      });

    },
    /**
     * Получает данные о запросе и о партнере
     */
    editPageRequest: function (id) {
      admin.ajaxRequest({
        pluginHandler: 'partners-program', // имя папки в которой лежит данный плагин
        actionerClass: "Partner", // класс News в partner.php - в папке плагина
        action: "getPartnerBalanse", // название действия в пользовательском  классе News
        id: id
      },
      partner.fillFileds()
        );
    },
    /**
     * заполняет поля таблицы запросов от партнеров
     * @returns {undefined}
     */

    fillFiledsRequest: function (id) {
      return (function (response) {
        partner.clearFiledsRequest();
        statusRequest = new Array('Не доступен', 'Выполнен', 'Ожидает оплаты', '', 'Отказ');
        var request = response.data.requestPartner;
        if ((request)) {
          request.forEach(function (object) {
            var color = 'get-paid';
            if (object.status == 1) {
              color = 'get-paid';
            }
            if (object.status == 0 || object.status == 4) {
              color = 'dont-paid';
            }
            if (object.status == 2) {
              color = 'activity-product-true';
            }
            var tr = '\
                <tr id="request' + object.id + '" >\
                <td class="request_id"> ' + object.id + '</td>\
                <td class="date-request">' + object.date_add + ' </td>\
                <td class="partner-request"><a href="javascript:void(0);" \
                  class="custom-btn partner-link" data-partnerId = ' + object.partner_id + ' title="Информация о партнере">\
                  <span>О партнере № ' + object.partner_id + '</span></a></td>\
                <td class="order-request">' + object.orders_numbers + '</td>\
                <td class="payments-request">' + object.summ + '</td>\
                <td class="status-request" data-status =' + object.status + '>\
                  <span class=' + color + '>' + statusRequest[object.status] + ' </span>\
                  <p class="comment" style="display:none">' + object.comment + '</p></td>\
                <td class="actions"><ul class="action-list request">\
                    <li class="edit-row" id="' + object.id + '"><a class="tool-tip-bottom" href="#" ></a></li>\
                    <li class="delete-row" id="' + object.id + '"><a class="tool-tip-bottom" href="#" ></a></li>\
                  </ul></td>\
                             </tr>';
            if ($('.partner-tbody.request tr').length > 0) {
              $('.partner-tbody.request tr:last').after(tr);
            }
            else {
              $('.partner-tbody.request').append(tr);
            }
          });
        }
        else {
          var tr = '\<tr class="noneRequest"><td colspan="7">Запросов нет</td></tr>';
          $('.partner-tbody.request').append(tr);


        }
        $('#list-request-table .main-settings-container').after(response.data.pageRequest);

      });
    },
    /*
     * заполняет модальное окно запроса информацией о запросе на выплату от партнера 
     * @param {type} id
     * @returns {Function}
     */
    fillFiledsRequestModal: function () {
      /**обращение к бд за информацие о e-mail  и проверка по id заказам - время выполнения и стутус!
       **/
      return (function (response) {
        if (response.data.none) {
          $('.section-partners .partners-payment-block .error').html('Запроса по данному заказу нет в базе данных');
          $('.section-partners .partners-payment-block .error').show();
          return true;
        }

        if (response.data.error) {
          error = 'error';
          $('.request-partner #warning').addClass('error');
          $('.request-partner .status-request').find('[value=1]').prop('disabled', true);
        }
        if (response.data.request.status == 1) {
          $('.request-partner .status-request option[value=2], .request-partner .status-request [value=4]').prop('disabled', true);
        }
        if (response.data.request.status == 4) {
          $('.request-partner .status-request option[value=1], .request-partner .status-request [value=2]').prop('disabled', true);
        }
        $('.request-partner #warning').html(response.data.warning);
        $('.id-request').html(response.data.request.id);
        $('.request-partner .id-partner').html(response.data.email);
        $('.request-partner .id-partner').attr('data-partner', response.data.request.partner_id);
        $('.request-partner .summ-request').html(admin.numberFormat(response.data.request.summ) + ' ' + admin.CURRENCY);
        $('.request-partner .orders-request').html(response.data.request.orders_numbers);
        $('.request-partner .status-request').find('[value=' + response.data.request.status + ']').prop('selected', true);
        $('textarea').val(response.data.request.comment);
        $('.save-button.request').attr('id', response.data.request.id);
        admin.openModal($('.b-modal.request'));
        $('.admin-center .b-modal.partner').css('z-index', 10);
      })

    },
    /**
     * Чистит все поля модального окна
     */
    clearFileds: function () {
      $('.section-partners .partners-payment-block .error').html('');
      $('.partner-order .partner-order-tbody tr').remove();
      $('.partner-form-wrapper .balance').text('');
      $('.partner-form-wrapper .amount').text('');
      $('.partner-form-wrapper .exitbalance').text('');
      $('input[name="payment"]').val('');
      $('.settings-info-partner .blockInfo input, .blockInfo textarea').val('');
      $('.settings-info-partner .blockInfo input[name=contract]').val('false');
      $('.settings-info-partner .blockInfo input[name=contract]').removeAttr('checked');
      $('.save-button.partner').attr('id', '');
    },
    /**
     * Чистит все поля таблицы по запросам выплат
     */
    clearFiledsRequest: function () {

      $('.partner-tbody.request tr').remove();

    },
    /**
     * Чистит все поля таблицы по запросам выплат в модальном окне
     */
    clearFiledsRequestModal: function () {
      $('.section-partners .partners-payment-block .error').html('');
      $('.request-partner #warning').removeClass('error');
      $('.request-partner .status-request option').removeAttr('disabled');
      $('.request-partner #warning').html('');
      $('.request-partner .id-request').html('');
      $('.request-partner .id-partner').html('');
      $('.request-partner .summ-request').html('');
      $('.request-partner .orders-request').html('');
      $('textarea').val('');
      $('.request-partner .status-request option').removeAttr('selected');
      $('.save-button.request').attr('id', '');
    },
    /**
     * функция для приема файла из аплоадера, для сохранения путь к договору о партнерской программе
     */
    getContract: function (file) {

      var dir = file.url;
      dir = dir.replace(mgBaseDir, '');

      $('.section-partners input[name="contractLink"]').val(dir);
      $('.section-partners .linkToContract').attr('href', file.url);
      $('.section-partners .linkToContract').html(dir.substr(0, 50));
      $('.section-partners .del-link-contract').attr('title', dir);
      $('.section-partners .del-link-contract').show();
      $('.section-partners .readContract').show();
      $('.section-partners .browseContract').hide();
    },
    /**
     * удаление партнера и всех записей с его id
     */
    deletePartner: function (id) {
      if (confirm("При удалении партнера, удалится информация о заказах и его запросах. Удалить партнера?")) {
      admin.ajaxRequest({
        mguniqueurl: "action/deletePartner", // действия для выполнения на сервере
        pluginHandler: 'partners-program', // плагин для обработки запроса
        id: id,
      },
        function (response) {
          admin.indication(response.status, response.msg);
          admin.refreshPanel();
        }
      );
    }
    },
    /**
     * удаление запроса партнера 
     */
    deleteRequest: function (id) {
      if (confirm("Уверены, что хотите удалить запрос партнера?")) {
        admin.ajaxRequest({
          mguniqueurl: "action/deleteRequest", // действия для выполнения на сервере
          pluginHandler: 'partners-program', // плагин для обработки запроса
          id: id,
        },
          function (response) {
            admin.indication(response.status, response.msg);
            admin.refreshPanel();
          }
        );
      }
    },
    /**
     * удаление заказа и всех записей с его id
     */
    deleteOrder: function (id) {
      if (confirm("Удалить информацию о заказе?" )) {
      admin.ajaxRequest({
        mguniqueurl: "action/deleteOrder", // действия для выполнения на сервере
        pluginHandler: 'partners-program', // плагин для обработки запроса
        id: id,
      },
        function (response) {
          admin.indication(response.status, response.msg);
          $('.partner-order .partner-order-tbody tr#' + id + '').remove();
          if (!$('.partner-order .partner-order-tbody tr').length > 0) {
            var tr = '\<tr class="noneRequest"><td colspan="5">Выполненных заказов нет</td></tr>';
            $('.partner-order .partner-order-tbody').append(tr);
          }
        }
      );
    }
  }

  }
})();

// инициализация модуля при подключении
partner.init();