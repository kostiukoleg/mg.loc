 /* 
 * Модуль  backRingModule, подключается на странице настроек плагина.
 */

var backRingModule = (function() {
  
  return { 
    lang: [], // локаль плагина 
    init: function() {      
      
      // установка локали плагина 
      admin.ajaxRequest({
          mguniqueurl: "action/seLocalesToPlug",
          pluginName: 'back-ring'
        },
        function(response) {
          backRingModule.lang = response.data;        
        }
      );        
        
      // Выводит модальное окно для добавления
      $('.admin-center').on('click', '.section-back-ring .add-new-button', function() {    
        backRingModule.showModal('add');    
      });
      
      // Выводит модальное окно для редактирования
      $('.admin-center').on('click', '.section-back-ring .edit-row', function() {       
        var id = $(this).data('id');
        backRingModule.showModal('edit', id);            
      });
      
       // Сохраняет изменения в модальном окне
      $('.admin-center').on('click', '.section-back-ring .b-modal .save-button', function() { 
        var id = $(this).data('id');    
        backRingModule.saveField(id);        
      });
      
     // Нажатие на кнопку - активности
      $('.admin-center').on('click', '.section-back-ring .visible', function(){    
        $(this).toggleClass('active');  
        var id = $(this).data('id');
        if($(this).hasClass('active')) { 
          backRingModule.visibleEntity(id, 1); 
          $(this).attr('title', lang.ACT_V_ENTITY);
        }
        else {
          backRingModule.visibleEntity(id, 0);
          $(this).attr('title', lang.ACT_UNV_ENTITY);
        }
        $('#tiptip_holder').hide();
        admin.initToolTip();
      });
      
      // Удаляет запись
      $('.admin-center').on('click', '.section-back-ring .delete-row', function() {
        var id = $(this).data('id');
        backRingModule.deleteEntity(id);
      });
      
       // Сохраняет базовые настроки запись
      $('.admin-center').on('click', '.section-back-ring .base-setting-save', function() {
   
        var obj = '{';
        $('.list-option input, .list-option select').each(function() {     
          obj += '"' + $(this).attr('name') + '":"' + $(this).val() + '",';
        });
        obj += '}';    

        //преобразуем полученные данные в JS объект для передачи на сервер
        var data =  eval("(" + obj + ")");

        data.nameEntity = $(".base-settings input[name=nameEntity]").val();

        admin.ajaxRequest({
          mguniqueurl: "action/saveBaseOption", // действия для выполнения на сервере
          pluginHandler: 'back-ring', // плагин для обработки запроса
          data: data // id записи
        },

        function(response) {
          admin.indication(response.status, response.msg);      
        }

        );
        
      });      
      
      // Выбор картинки
      $('.admin-center').on('click', '.section-back-ring .browseImage', function() {
        admin.openUploader('backRingModule.getFile');
      });     
      
    },
    
    /* открывает модальное окно 
     * @param {type} type -тип окна, для редактирования или для добавления
     * @param {type} id - номер записи, которая открыта на редактирование
     * @returns {undefined}
     */
    showModal: function(type, id) {
      switch (type) {
        case 'add':
          {
            backRingModule.clearField();           
            break;
          }
        case 'edit':
          {
            backRingModule.clearField();
            backRingModule.fillField(id);
            break;
          }
        default:
          {
            break;
          }
      }

      admin.openModal($('.b-modal'));      
      
    },
                 
   /**
    * функция для приема файла из аплоадера
    */         
    getFile: function(file) {      
      $('.section-back-ring .b-modal  input[name="src"]').val(file.url);
    },      
            
   /**
    * Очистка модального окна
    */         
    clearField: function() {
      $('.section-back-ring .b-modal input').val('');  
      $('.section-back-ring .b-modal .id-entity').text('');
      $('.section-back-ring .b-modal .save-button').data('id','');
      $('.section-back-ring textarea[name=value]').val('');
    },
            
    /**
     * Заполнение модального окна данными из БД
     * @param {type} id
     * @returns {undefined}
     */        
    fillField: function(id) {

      admin.ajaxRequest({
        mguniqueurl: "action/getEntity", // действия для выполнения на сервере
        pluginHandler: 'back-ring', // плагин для обработки запроса
        id: id // id записи
      },
      
      function(response) {     	   
	      $('.section-back-ring .b-modal  input[name="nameEntity"]').val(response.data.nameEntity);	      
        $('.section-back-ring textarea[name=value]').val(response.data.value);
        $('.section-back-ring .b-modal .save-button').data('id',response.data.id);
      },
              
      $('.b-modal .widget-table-body') // вывод лоадера в контейнер окна, пока идет загрузка данных
      
      );

    },
    
    /**
     * Сохранение данных из модального окна
     * @param {type} id
     * @returns {undefined}
     */        
    saveField: function(id) {
	    var nameEntity = $('.section-back-ring .slide-editor input[name=nameEntity]').val();
      var type = $('.section-back-ring .slide-editor select[name=type]').val();     
      var value = $('.section-back-ring textarea[name=value]').val(); 
      var invisible = '0';
      console.log($('.entity-table-tbody tr[data-id='+id+'] .visible').length);
      if($('.entity-table-tbody tr[data-id='+id+'] .visible').hasClass('active')){   
        invisible = '1' ;
      }
              
      admin.ajaxRequest({
        mguniqueurl: "action/saveEntity", // действия для выполнения на сервере
        pluginHandler: 'back-ring', // плагин для обработки запроса
        id: id,
        value: value,
        type: type,
		    nameEntity: nameEntity,   
        invisible:invisible
      },
      
      function(response) {
        console.log(response);
        admin.indication(response.status, response.msg);
        if(id){
          var replaceTr = $('.entity-table-tbody tr[data-id='+id+']');
          backRingModule.drawRow(response.data.row,replaceTr); // перерисовка строки новыми данными
        } else{
          backRingModule.drawRow(response.data.row); // добавление новой записи         
        }        
     
        admin.closeModal($('.b-modal'));        
        backRingModule.clearField();
      },
              
      $('.b-modal .widget-table-body') // на месте кнопки
      
      );

    },
    
    
    /**    
     * Отрисовывает  строку сущности в главной таблице
     * @param {type} data - данные для вывода в строке таблицы
     */        
    drawRow: function(data, replaceTr) {
      
      var invisible = data.invisible==='1'?'active':'';        
      var titleInvisible = data.invisible?lang.ACT_V_ENTITY:lang.ACT_UNV_ENTITY;  
      var type = " <span class='activity-product-true'> "+data.nameEntity+"</span>";     
      
      var tr = '\
       <tr data-id="'+data.id+'">\
        <td>'+data.id+'</td>\
        <td class="type">'+type+'</td>\
         <td class="actions">\
           <ul class="action-list">\
             <li class="edit-row" data-id="'+data.id+'" data-type="'+data.type+'"><a class="tool-tip-bottom" href="javascript:void(0);" title="'+lang.EDIT+'"></a></li>\
             <li class="visible tool-tip-bottom '+invisible+'" data-id="'+data.id+'" title="'+titleInvisible+'"><a href="javascript:void(0);"></a></li>\
             <li class="delete-row" data-id="'+data.id+'"><a class="tool-tip-bottom" href="javascript:void(0);"  title="'+lang.DELETE+'"></a></li>\
           </ul>\
         </td>\
      </tr>';
 
      if(!replaceTr){
       
        if($('.entity-table-tbody tr').length>0){
          $('.entity-table-tbody tr:first').before(tr);
        } else{
          $('.entity-table-tbody').append(tr);
        }
        $('.entity-table-tbody .no-results').remove();
         
      }else{
        replaceTr.replaceWith(tr);
      }
    },
       
       
    /**    
     * Удаляет  строку сущности в главной таблице
     * @param {type} data - данные для вывода в строке таблицы
     */           
    deleteEntity: function(id) {
      if(!confirm(lang.DELETE+'?')){
        return false;
      }
      
      admin.ajaxRequest({
        mguniqueurl: "action/deleteEntity", // действия для выполнения на сервере
        pluginHandler: 'back-ring', // плагин для обработки запроса
        id: id               
      },
      
      function(response) {
        admin.indication(response.status, response.msg);
        $('.entity-table-tbody tr[data-id='+id+']').remove();
        if($(".entity-table-tbody tr").length==0){
          var html ='<tr class="no-results">\
            <td colspan="3" align="center">'+backRingModule.lang['ENTITY_NONE']+'</td>\
          </tr>';
          $(".entity-table-tbody").append(html);
        };
      }
      
      );
    },    


    /*
     * Переключатель активности
     */
    visibleEntity:function(id, val) {
      admin.ajaxRequest({
        mguniqueurl:"action/visibleEntity",
        pluginHandler: 'back-ring', // плагин для обработки запроса
        id: id,
        invisible: val,
      },
      function(response) {
        admin.indication(response.status, response.msg);
      } 
      );
    },
    
  }
})();

backRingModule.init();
admin.sortable('.entity-table-tbody', 'back-ring');