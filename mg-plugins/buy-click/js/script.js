/* 
 * Модуль  buyClickModule, подключается на странице настроек плагина.
 */

var buyClickModule = (function () {

  return {
    lang: [], // локаль плагина 
    init: function () {

      // установка локали плагина 
      admin.ajaxRequest({
        mguniqueurl: "action/seLocalesToPlug",
        pluginName: 'byu-click'
      },
      function (response) {
        buyClickModule.lang = response.data;
      }
      );

      // Сохраняет базовые настроки запись
      $('.admin-center').on('click', '.section-buy-click .base-settings .save-button', function () {

        var obj = '{';
        $('.list-option input, .list-option textarea, .list-option select').each(function () {
          obj += '"' + $(this).attr('name') + '":"' + $(this).val() + '",';
        });

        obj += '}';

        //преобразуем полученные данные в JS объект для передачи на сервер
        var data = eval("(" + obj + ")");
        admin.ajaxRequest({
          mguniqueurl: "action/saveBaseOption", // действия для выполнения на сервере
          pluginHandler: 'buy-click', // плагин для обработки запроса
          data: data // id записи
        },
        function (response) {
          admin.indication(response.status, response.msg);
        }
        );
      });
    }}
})();

buyClickModule.init();
