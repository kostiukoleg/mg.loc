 /* 
 * Модуль  uloginAuthModule, подключается на странице настроек плагина.
 */

var uloginAuthModule = (function() {
  
  return { 
    lang: [], // локаль плагина 
    init: function() {      
      
      // установка локали плагина 
      admin.ajaxRequest({
          mguniqueurl: "action/seLocalesToPlug",
          pluginName: 'ulogin'
        },
        function(response) {
          uloginAuthModule.lang = response.data;        
        }
      );        

       // Сохраняет базовые настроки запись
      $('.admin-center').on('click', '#loginza-save', function() {
        //преобразуем полученные данные в JS объект для передачи на сервер
        var data = {widget:$('#uloginWidget').val()}

        data.nameEntity = $(".base-settings input[name=nameEntity]").val();

        admin.ajaxRequest({
          mguniqueurl: "action/saveBaseOption", // действия для выполнения на сервере
          pluginHandler: 'ulogin', // плагин для обработки запроса
          data: data // id записи
        },

        function(response) {
          admin.indication(response.status, response.msg);      
        }

        );
        
      });      

    },
    
  }
})();

uloginAuthModule.init();
admin.sortable('.entity-table-tbody', 'ulogin');