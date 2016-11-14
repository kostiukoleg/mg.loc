 /* 
 * Модуль подключается на странице настроек плагина.
 */

var mgIOExcel = (function() {
  
  return { 
    lang: [], // локаль плагина 
    pluginName: 'mg-io-excel',
    init: function() {      
      
      // установка локали плагина 
      admin.ajaxRequest({
          mguniqueurl: "action/seLocalesToPlug",
          pluginName: mgIOExcel.pluginName
        },
        function(response) {
          mgIOExcel.lang = response.data;        
        }
      );        
      
      // Сохраняет базовые настроки запись
      $('.admin-center').on('click', '.section-'+mgIOExcel.pluginName+' .base-setting-save', function() {
        if($('input[name="clearCatalog"]').is(':checked')){
          mgIOExcel.clearCatalog();
        }else{
          mgIOExcel.importCatalog(0, 0);
        }
      });  
      
      $('.admin-center').on('click', '.section-'+mgIOExcel.pluginName+' .export-start', function() {
        mgIOExcel.exportCatalog(0, 2);        
      });  
      
      $('body').on('change', '.section-'+mgIOExcel.pluginName+' input[name="upload_data_file"]', function() {
        mgIOExcel.uploadFileToImport();
      });
      
      // Клик по кнопкам таб панели
			$('.admin-center').on('click', '.section-'+mgIOExcel.pluginName+' .tabs-list li a', function() {				
				// Если открываемый блок не открыт
				if(!$(this).parent().hasClass('ui-state-active')) {
					$('.section-'+mgIOExcel.pluginName+' .tabs-list li').removeClass("ui-state-active"); // деактивируем
					$(this).parent().addClass("ui-state-active"); // активируем необходимый блок
          $('.section-'+mgIOExcel.pluginName+' .setting-block').hide();
					$('.section-'+mgIOExcel.pluginName+' .'+$(this).attr('id').replace('tab', 'block')).show();	 // открываем необходимый блок				
				}
			});
      
      $('.admin-center').on('click', '.section-'+mgIOExcel.pluginName+' .set-import-config', function(){
        
      });
      
      // Сохраняет изменения в модальном окне
      $('.admin-center').on('click', '.section-'+mgIOExcel.pluginName+' .b-modal .save-button', function() { 
        var obj = '{';
        $('.section-'+mgIOExcel.pluginName+' .b-modal select').each(function() {     
          obj += '"' + $(this).attr('name') + '":"' + admin.htmlspecialchars($(this).val()) + '",';
        });
        obj += '}';
        
        //преобразуем полученные данные в JS объект для передачи на сервер
        var data =  eval("(" + obj + ")");
        
        admin.ajaxRequest({
          mguniqueurl: "action/setCompliance", // действия для выполнения на сервере
          pluginHandler: mgIOExcel.pluginName, // плагин для обработки запроса
          data: data,
          importType: $('.b-modal button.save-button').attr('importType')
        },
        function(response){
          admin.indication(response.status, response.msg); 
          mgIOExcel.printLog(response.msg);
          admin.closeModal($('.b-modal'));
        });
      });
      
      $('.admin-center').on('change', 'select[name=importScheme]', function(){
        switch($(this).val()){
          case 'last':
            mgIOExcel.showModal('last');
            break;
          case 'new':
            mgIOExcel.showModal('auto');
            break;
          default:
            return false;
        }
      });
      
      $('.admin-center').on('change', 'select[name=importType]', function(){
        if($(this).val() != 0){
          $('input[name=upload_data_file]').removeAttr('disabled');
          
          admin.ajaxRequest({
            mguniqueurl: "action/isSetCompliance", // действия для выполнения на сервере
            pluginHandler: mgIOExcel.pluginName,
            importType: $(this).val()
          },function(response){
            if(response.data.isSet){
              $('.section-'+mgIOExcel.pluginName+' select[name=importScheme] option[value=last]').show();
            }else{
              $('.section-'+mgIOExcel.pluginName+' select[name=importScheme] option[value=last]').hide();
            }            
          });
          
        }else{
          $('input[name=upload_data_file]').attr('disabled', 'disabled');
        }
      });
      
    },
    
    showModal: function(type){
      $('.b-modal .widget-table-body ul').empty();
      var obj = '{';
      $('.list-option input, .list-option select').each(function(){     
        obj += '"' + $(this).attr('name') + '":"' + $(this).val() + '",';
      });
      obj += '}';    

      //преобразуем полученные данные в JS объект для передачи на сервер
      var data =  eval("(" + obj + ")");
      
      admin.ajaxRequest({
        mguniqueurl: "action/getCompliance", // действия для выполнения на сервере
        pluginHandler: mgIOExcel.pluginName, // плагин для обработки запроса
        type: type,
        data: data
      },
      mgIOExcel.fillField());
      $('.b-modal button.save-button').attr('importType', data.importType);
      admin.openModal($('.b-modal'));
    },
    
    fillField: function(){
      return function(response){
        var titleList = '';
        var compList = '';
        
        response.data.titleList.forEach(function(item, i, arr){
          titleList += '<option value="'+i+'">'+item+'</option>';
        });
        
        response.data.maskArray.forEach(function(item, i, arr){
          compList = '\
            <li><span>'+item+'</span>\
              <select name="colIndex'+i+'">\
                '+titleList+'\
              </select>\
            </li>';
          
          $('.b-modal .widget-table-body ul').append(compList);
          $('.b-modal .widget-table-body ul select[name=colIndex'+i+'] option[value='+response.data.compliance[i]+']').attr('selected', 'selected');
        });
        //$('.save-button').attr('id',response.data.id);
        
      }
    },
    
    exportCatalog: function(step, nextRow){
      var obj = '{';
      $('.list-option input, .list-option select').each(function(){     
        obj += '"' + $(this).attr('name') + '":"' + $(this).val() + '",';
      });
      obj += '}';    

      //преобразуем полученные данные в JS объект для передачи на сервер
      var data =  eval("(" + obj + ")");
      
      if(step == 0){
        //mgIOExcel.printLog(mgIOExcel.lang.EXPORT_CATALOG_START);
      }else{
        setTimeout(function(){
          $('.mailLoader').before('<div class="my-view-action">'+mgIOExcel.lang.EXPORT_CATALOG_PROCESS+'</div>');
        }, 300);
        //admin.waiting(true);
      }     
      
      admin.ajaxRequest({
        mguniqueurl: "action/exportCatalog", // действия для выполнения на сервере
        pluginHandler: mgIOExcel.pluginName, // плагин для обработки запроса
        nextRow: nextRow, // Номер строки
        step: step,
        data: data
      },
      function(response){
        if(response.status == 'error'){
          //mgIOExcel.printLog(response.msg);
          admin.indication(response.status, response.msg); 
          return false;
        }
        $('.my-view-action').remove();
        if(!response.data.exportSuccess){
//          mgIOExcel.printLog(response.msg);
//          if(response.data.message.length){
//            mgIOExcel.printLog(response.data.message);
//          }
          mgIOExcel.exportCatalog(response.data.nextStep, response.data.nextRow);
        }else{
//          mgIOExcel.printLog(response.msg);
//          admin.show(mgIOExcel.pluginName, "plugin");
          $('.download-export-file').html('<a href="'+response.data.file+'">Скачать файл экспорта</a>');

          admin.indication(response.status, response.msg); 
        }
      });
    },
    clearCatalog: function(){
      mgIOExcel.printLog(mgIOExcel.lang.CLEAR_CATALOG_START);
      
      setTimeout(function(){
        $('.mailLoader').before('<div class="my-view-action">'+mgIOExcel.lang.CLEAR_CATALOG_PROCESS+'</div>');
      }, 300);
      
      admin.ajaxRequest({
        mguniqueurl: "action/clearCatalog", // действия для выполнения на сервере
        pluginHandler: mgIOExcel.pluginName, // плагин для обработки запроса
      },
      function(response){
        $('.my-view-action').remove();
        mgIOExcel.printLog(response.msg);
        admin.indication(response.status, response.msg);
        mgIOExcel.importCatalog(0, 0);
      });
    },
    uploadFileToImport: function(){
      $('.section-'+mgIOExcel.pluginName+' input[name="upload_data_file"]').hide();
      $('.mailLoader').before('<div class="view-action" style="margin-top:-2px;">' + lang.LOADING + '</div>');
      // отправка файла на сервер
      mgIOExcel.printLog(mgIOExcel.lang.UPLOAD_FILE_PROCESS);
      var importType = $('select[name=importType]').val();
      
      $('.excel-upload-form').ajaxForm({
        type: "PUT",
        url: "ajax",
        cache: false,
        dataType: 'json',
        data: {
          mguniqueurl: "action/uploadFileToImport",
          pluginHandler: mgIOExcel.pluginName, // плагин для обработки запроса               
          importType: importType
        },
        error: function(response) {
          mgIOExcel.printLog(response.msg);
          $('.section-'+mgIOExcel.pluginName+' input[name="upload_data_file"]').show();
          $('.view-action').remove();
        },
        success: function(response) {
          admin.indication(response.status, response.msg);
          if (response.status == 'success') {
            $('.mgIOExcel-importer').show();        
            mgIOExcel.printLog(mgIOExcel.lang.UPLOAD_FILE_SUCCESS);
            $('select.importScheme').removeAttr('disabled');
          } else {            
            $('.section-'+mgIOExcel.pluginName+' input[name="upload_data_file"]').val('');
          }
          $('.upload_file_success').show();
          $('.view-action').remove();
        },
      }).submit();
    },
    importCatalog: function(nextRow, step){
      var obj = '{';
      $('.list-option input, .list-option select').each(function(){     
        obj += '"' + $(this).attr('name') + '":"' + $(this).val() + '",';
      });
      obj += '}';    

      //преобразуем полученные данные в JS объект для передачи на сервер
      var data =  eval("(" + obj + ")");
      
      if(step == 0){
        mgIOExcel.printLog(mgIOExcel.lang['START_IMPORT_LOG']);
      }else{
        setTimeout(function(){
          $('.mailLoader').before('<div class="my-view-action">'+mgIOExcel.lang.IMPORT_PROCESS+'</div>');
        }, 300);
        //admin.waiting(true);
      }
      
      admin.ajaxRequest({
        mguniqueurl: "action/importCatalog", // действия для выполнения на сервере
        pluginHandler: mgIOExcel.pluginName, // плагин для обработки запроса
        nextRow: nextRow, // Номер строки
        data: data
      },
      function(response){
        if(response.status == 'error'){
          mgIOExcel.printLog(response.msg);
          $('.my-view-action').remove();
          admin.indication(response.status, response.msg); 
          return false;
        }
        $('.my-view-action').remove();
        if(!response.data.importSuccess){
          mgIOExcel.printLog(response.msg);
          if(response.data.message.length){
            mgIOExcel.printLog(response.data.message);
          }
          mgIOExcel.importCatalog(response.data.nextRow, 1);
        }else{
          mgIOExcel.printLog(response.msg);          
          admin.indication(response.status, response.msg); 
          setTimeout(function(){
            $('.my-view-action').remove();
          }, 300);
        }
      });
    },
    printLog: function(text){
      $('.section-'+mgIOExcel.pluginName+' .block-console textarea').append("\r\n"+text);         
    },
  }
})();

mgIOExcel.init();