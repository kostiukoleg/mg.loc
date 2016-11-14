 /* 
 * Модуль подключается на странице настроек плагина.
 */

var mgCurency = (function() {
  
  return { 
    lang: [], // локаль плагина 
    pluginName: 'mg_update_currency',
    init: function() {      
      
      // установка локали плагина 
      admin.ajaxRequest({
          mguniqueurl: "action/seLocalesToPlug",
          pluginName: mgCurency.pluginName
        },
        function(response) {
          mgCurency.lang = response.data;        
        }
      );        
      
      // Сохраняет базовые настроки запись
      $('.admin-center').on('click', '.section-'+mgCurency.pluginName+' .base-setting-save', function() {
        mgCurency.saveSettings(0);
      });    
      
      $('.admin-center').on('change', '.list-option input[name=use_margin]', function(){
        if($(this).is(":checked")){
          $('.list-option input[name=margin]').removeAttr("disabled");
        }else{
          $('.list-option input[name=margin]').attr("disabled", "disabled");
        }
      });
      
      $('.admin-center').on('change', '.list-option input[name=use_auto_update]', function(){
        if($(this).is(":checked")){
          $('.list-option input[name=auto_update_time]').removeAttr("disabled");
        }else{
          $('.list-option input[name=auto_update_time]').attr("disabled", "disabled");
        }
      });
      
      $('.admin-center').on('click', '.section-'+mgCurency.pluginName+' button.update_rates', function(){
        mgCurency.saveSettings(0);
      });
      
      $('.admin-center').on('click', '.section-'+mgCurency.pluginName+' button.update-price', function(){
        mgCurency.updatePrices(0);
      });
    },
    saveSettings: function(step){
      var obj = '{';
      $('.list-option input, .list-option select').each(function() {     
        obj += '"' + $(this).attr('name') + '":"' + $(this).val() + '",';
      });
      obj += '}';    

      //преобразуем полученные данные в JS объект для передачи на сервер
      var data =  eval("(" + obj + ")");

      admin.ajaxRequest({
        mguniqueurl: "action/saveBaseOption", // действия для выполнения на сервере
        pluginHandler: mgCurency.pluginName, // плагин для обработки запроса
        data: data // id записи
      },
      function(response) {
        //admin.indication(response.status, response.msg); 
        var margin = 0;
        var auto_update_price = $('.list-option input[name=auto_update_price]').is(":checked");
        var use_margin = $('.list-option input[name=use_margin]').is(":checked");
        
        if(use_margin){
          margin = $('.list-option input[name=margin]').val();
        }
        
        
        admin.ajaxRequest({
          mguniqueurl: "action/updateCurrency", // действия для выполнения на сервере
          pluginHandler: mgCurency.pluginName, // плагин для обработки запроса
          use_margin: use_margin,
          margin: margin, // наценка на курс в процентах
          step: step
        },
        function(response) {
          admin.indication(response.status, response.msg); 
          
          if(auto_update_price){
            mgCurency.updatePrices(0);
          }
        });
      });
    },
    updatePrices: function(step){
      admin.ajaxRequest({
        mguniqueurl: "action/updatePrices", // действия для выполнения на сервере
        pluginHandler: mgCurency.pluginName, // плагин для обработки запроса
        step: step
      },
      function(response){
        if(response.data.status == 'success'){
          admin.indication(response.status, response.msg);   
        }else{
          admin.indication(response.status, response.msg);
          step = response.data.step;
          mgCurency.updatePrices(step+1);
        }
      });
    }
  }
})();

mgCurency.init();