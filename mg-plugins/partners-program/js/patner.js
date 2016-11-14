var partnerProgram = (function () {
  return {
    /**
     * Инициализирует обработчики для кнопок и элементов раздела.
     */
    init: function () {
      var number = $('.showAccountStatements').data('active');
      $('#table' + number).show();

      // Вызов модального окна при нажатии на кнопку изменения.
      $('.partner-orders-tbody tr[data-status!=3], .partner-orders-tbody tr[data-status!=4]').find('.action input').attr('disabled', 'disabled');
      $('.partner-orders-tbody tr[data-status=3], .partner-orders-tbody tr[data-status=4]').find('.action input').removeAttr('disabled');
      $('.partner-orders-tbody tr[data-status=3]').find('.action input').attr('checked', 'checked');
      $('.partner-orders-tbody tr[data-status=3]').find('.action input').attr('value', 'true');
      $('.partner-orders-tbody tr[data-status=4]').find('.action input').attr('checked', 'checked');
      $('.partner-orders-tbody tr[data-status=4]').find('.action input').attr('value', 'true');
      $(".partnerProgram .total-request").text('');
      partnerProgram.totalToOrder();

      var count = $('.count-items-dropdown').data('count');
      $('.count-items-dropdown option[value=' + count + ']').prop('selected', true);

      $('.partnerProgram').on('change', '.count-items-dropdown', function () {
        var count = $(this).val();
        document.cookie = "countRowsPartnersProgram=" + count + "";
        location.reload();
      });

      $('.action').on('click', 'input', function () {

        if ($(this).val() == 'true') {
          $(this).removeAttr('checked');
          $(this).attr('value', 'false');
        }
        else {

          $(this).attr('value', 'true');
          $(this).attr('checked', 'checked');
        }
        partnerProgram.totalToOrder();
      });

      $('.partnerProgram').on('click', '.showFormOrderParnet', function () {
        $(this).hide();
        if ($(this).data('contract') == 0) {
          $('.partnerProgram .totalSum .error').show();
          $(this).show();
          return false;
        }
        else {
          partnerProgram.orderPartner();
        }
      });

      $('#becomePartner').on('click', function () {
        partnerProgram.newPartner();
      });

    },
    /**
     * Пересчитывает сумму запрошенной выплаты
     */
    totalToOrder: function () {


      var sum = 0;
      var min = $('.partnerProgram .totalSum').attr('data-min');
      $('.partner-orders-tbody tr[data-status=3], .partner-orders-tbody tr[data-status=4]').find('.action input:checked').each(function () {
        sum += parseFloat($(this).attr('data-summa'));
      });
      
      $('.partnerProgram .total-request').text(partnerProgram.numberFormat(sum));
      $('.partnerProgram .total-request').attr('data-total', sum);
      if (sum < min) {
        $('.showFormOrderParnet').hide();
      }
      else {
        $('.showFormOrderParnet').show();
      }
      return true;
    },
    orderPartner: function () {
      var orders = '';
      var numbers = '';
      var number, id, sum;
      $('.partner-orders-tbody tr[data-status=3], .partner-orders-tbody tr[data-status=4]').find('.action input:checked').each(function () {
        sum = $(this).attr('data-summa');
        id = $(this).parent().parent().attr('id');
        number = $(this).parent().parent().find('.number').text();
        number = $.trim(number);
        orders += id + ',';
        numbers += number + ',';
      });
      var summ = $(".partnerProgram .total-request").attr('data-total');
      //преобразуем полученные данные в JS объект для передачи на сервер
      $.ajax({
        type: "POST",
        url: "ajaxrequest",
        data: {
          pluginHandler: "partners-program",
          actionerClass: "Partner",
          action: "sendOrderToPayment",
          summ: summ,
          orders: orders,
          numbers: numbers
        },
        dataType: "json",
        cache: false,
        success: function (response) {

          if (!response.data.error) {
            var currency = $(".showFormOrderParnet").attr('data-currency');
            $(".showFormOrderParnet").hide();
            $('#actionPartner').after("<div style=\"color:green\">\
              Заявка на выплату <b>" + partnerProgram.numberFormat(summ) + " " + currency + "</b>\
              принята, наши менеджеры свяжутся с Вами, чтобы уточнить удобный способ перечисления денег!</div>");

            $('.partner-orders-tbody tr').find('.action input:checked').each(function () {
              $(this).parent().parent().attr('data-status', 2);
            });
            $('.partner-orders-tbody tr[data-status=2]').find('.status').text('Запрос отправлен');
            $('.partner-orders-tbody tr[data-status=2]').find('.action input').attr('disabled', 'disabled');

            partnerProgram.totalToOrder();
            var tr = '\
                <tr id="' + response.data.id + '" >\
                <td class="dateRequest">' + response.data.date_add + '</td>\
                <td class="summRequest">' + response.data.summ + ' ' + currency + '</td>\
                <td class="statusRequest">Запрос отправлен</td>\
                <td class="comment"></td> \
                <td></td>\
              </tr>';
            if ($('.partner-request-tbody tr').length > 0) {
              $('.partner-request-tbody tr:first').before(tr);
            } else {
              $('.partner-request-tbody').append(tr);
            }
          }
          else {
            $('.partnerProgram .totalSum').after('<p>' + response.data.error + '</p>');
          }
        },
      });
    },
    newPartner: function () {
      $.ajax({
        type: "POST",
        url: "ajaxrequest",
        data: {
          pluginHandler: "partners-program",
          actionerClass: "Partner",
          action: "becomePartner"
        },
        dataType: "json",
        cache: false,
        success: function (response) {
          location.reload();
        }
      });
    },
    numberFormat: function (str) {
      return partnerProgram.number_format(str, 2, ',', ' ');
    },
    // форматирует строку в соответствии с форматом
    number_format: function (number, decimals, dec_point, thousands_sep) {	
      // Format a number with grouped thousands

      var i, j, kw, kd, km;

      if (isNaN(decimals = Math.abs(decimals))) {
        decimals = 2;
      }
      if (dec_point == undefined) {
        dec_point = ",";
      }
      if (thousands_sep == undefined) {
        thousands_sep = ".";
      }

      i = parseInt(number = (+number || 0).toFixed(decimals)) + "";

      if ((j = i.length) > 3) {
        j = j % 3;
      } else {
        j = 0;
      }

      km = (j ? i.substr(0, j) + thousands_sep : "");
      kw = i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thousands_sep);

      kd = (decimals ? dec_point + Math.abs(number - i).toFixed(decimals).replace(/-/, 0).slice(2) : "");


      return km + kw + kd;
    },
  }

})();
$(document).ready(function () {
// инициализациямодуля при подключении
  partnerProgram.init();
})