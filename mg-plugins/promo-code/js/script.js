 /* 
 * Модуль  promoCode, подключается на странице настроек плагина.
 */

var promoCode = (function() {
  
  return { 
    lang: [], // локаль плагина 
    init: function() {      
      
      // установка локали плагина 
      admin.ajaxRequest({
          mguniqueurl: "action/seLocalesToPlug",
          pluginName: 'promo-code'
        },
        function(response) {
          promoCode.lang = response.data;        
        }
      );        
        
      // Выводит модальное окно для добавления
      $('.admin-center').on('click', '.section-promo-code .add-new-button', function() {    
        promoCode.showModal('add');    
      });
      
      // Выводит модальное окно для редактирования
      $('.admin-center').on('click', '.section-promo-code .edit-row', function() {       
        var id = $(this).data('id');
        promoCode.showModal('edit', id);            
      });
      
       // Сохраняет изменения в модальном окне
      $('.admin-center').on('click', '.section-promo-code .b-modal .save-button', function() { 
        var id = $(this).data('id');    
        promoCode.saveField(id);        
      });
      
      // Сброс фильтров.
      $('.admin-center').on('click', '.section-promo-code .refreshFilter', function(){
        admin.show('promo-code',"plugin","refreshFilter=1",promoCode.callbackBackRing);
        return false;
      });
      
       // Устанавливает количиство выводимых записей в этом разделе.
      $('.admin-center').on('change', '.section-promo-code .countPrintRowsEntity', function(){
        var count = $(this).val();
        admin.ajaxRequest({
          mguniqueurl: "action/setCountPrintRowsEnity",
          pluginHandler: 'promo-code',
          option: 'countPrintRowsPromoCode',
          count: count
        },
        function(response) {         
          admin.refreshPanel();
        }
        );
      });

      
     // Нажатие на кнопку - активности
      $('.admin-center').on('click', '.section-promo-code .visible', function(){    
        $(this).toggleClass('active');  
        var id = $(this).data('id');
        if($(this).hasClass('active')) { 
          promoCode.visibleEntity(id, 1); 
          $(this).attr('title', lang.ACT_V_ENTITY);
        }
        else {
          promoCode.visibleEntity(id, 0);
          $(this).attr('title', lang.ACT_UNV_ENTITY);
        }
        $('#tiptip_holder').hide();
        admin.initToolTip();
      });
      
      // Удаляет запись
      $('.admin-center').on('click', '.section-promo-code .delete-row', function() {
        var id = $(this).data('id');
        promoCode.deleteEntity(id);
      });
      
       // Сохраняет базовые настроки запись
      $('.admin-center').on('click', '.section-promo-code .base-setting-save', function() {
       
        var obj = '{';
        $('.section-promo-code .list-option input, .section-promo-code .list-option textarea, .section-promo-code .list-option select').each(function() {     
          obj += '"' + $(this).attr('name') + '":"' + $(this).val() + '",';
        });
        obj += '}';    

        //преобразуем полученные данные в JS объект для передачи на сервер
        var data =  eval("(" + obj + ")");

        admin.ajaxRequest({
          mguniqueurl: "action/saveBaseOption", // действия для выполнения на сервере
          pluginHandler: 'promo-code', // плагин для обработки запроса
          data: data // id записи
        },

        function(response) {
          admin.indication(response.status, response.msg);     
          admin.refreshPanel();
        }

        );
        
      });      
      
      // Применение выбраных фильтров
      $('.admin-center').on('click', '.section-promo-code .filter-now', function() {
        promoCode.getProductByFilter();
        return false;
      });
      
      // Выбор картинки
      $('.admin-center').on('click', '.section-promo-code .browseImage', function() {
        admin.openUploader('promoCode.getFile');
      });     
      
      // Показывает панель с настройками.
      $('.admin-center').on('click', '.section-promo-code .show-property-order', function() {
        $('.property-order-container').slideToggle(function() {
          $('.filter-container').slideUp();
          $('.widget-table-action').toggleClass('no-radius');
        });
      });
            
      // Показывает панель с фильтрами.
      $('.admin-center').on('click', '.section-promo-code  .show-filters', function() {
        $('.filter-container').slideToggle(function() {
          $('.property-order-container').slideUp();
          $('.widget-table-action').toggleClass('no-radius');
        });
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
            promoCode.clearField();           
            break;
          }
        case 'edit':
          {
            promoCode.clearField();
            promoCode.fillField(id);
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
      $('.section-promo-code .b-modal  input[name="src"]').val(file.url);
    },      
            

   /**
    * Очистка модального окна
    */         
    clearField: function() {        
      $('.section-promo-code .b-modal .fields-calback input[name="code"]').val('');  
      $('.section-promo-code .b-modal .fields-calback input[name="percent"]').val('');
      $('.section-promo-code .b-modal .fields-calback input[name="from_datetime"]').val('');  
      $('.section-promo-code .b-modal .fields-calback input[name="to_datetime"]').val('');
      $('.section-promo-code .b-modal .fields-calback textarea[name=desc]').val('');
      $('.section-promo-code .b-modal .save-button').data('id','');        
    },
            
    /**
     * Заполнение модального окна данными из БД
     * @param {type} id
     * @returns {undefined}
     */        
    fillField: function(id) {

      admin.ajaxRequest({
        mguniqueurl: "action/getEntity", // действия для выполнения на сервере
        pluginHandler: 'promo-code', // плагин для обработки запроса
        id: id // id записи
      },
         
      
      function(response) {         
        $('.section-promo-code .b-modal .fields-calback input[name="code"]').val(response.data.code);  
        $('.section-promo-code .b-modal .fields-calback input[name="percent"]').val(response.data.percent);
        $('.section-promo-code .b-modal .fields-calback input[name="from_datetime"]').val(response.data.from_datetime);  
        $('.section-promo-code .b-modal .fields-calback input[name="to_datetime"]').val(response.data.to_datetime);
        $('.section-promo-code .b-modal .fields-calback textarea[name=desc]').val(response.data.desc);     
        $('.section-promo-code .b-modal .save-button').data('id',response.data.id);     
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
	    
      var invisible = '0';     
      if($('.entity-table-tbody tr[data-id='+id+'] .visible').hasClass('active')){   
        invisible = '1' ;
      }     
      
      if($('.section-promo-code .b-modal .fields-calback input[name="percent"]').val() <0 ||
         $('.section-promo-code .b-modal .fields-calback input[name="percent"]').val() >100 ||
         isNaN(parseFloat($('.section-promo-code .b-modal .fields-calback input[name="percent"]').val()))){
         alert(promoCode.lang['PPROCENT_ERROR']);
        return false;
      }
   
      admin.ajaxRequest({
        mguniqueurl: "action/saveEntity", // действия для выполнения на сервере
        pluginHandler: 'promo-code', // плагин для обработки запроса
        id: id,
        code: $('.section-promo-code .b-modal .fields-calback input[name="code"]').val(),
        percent: $('.section-promo-code .b-modal .fields-calback input[name="percent"]').val(),
		    from_datetime: $('.section-promo-code .b-modal .fields-calback input[name="from_datetime"]').val(),   
        to_datetime: $('.section-promo-code .b-modal .fields-calback input[name="to_datetime"]').val(),
        desc:   $('.section-promo-code .b-modal .fields-calback textarea[name=desc]').val(), 
        invisible: invisible,  
      },
      
      function(response) {
        admin.indication(response.status, response.msg);
        if(id){
          var replaceTr = $('.entity-table-tbody tr[data-id='+id+']');
          promoCode.drawRow(response.data.row,replaceTr); // перерисовка строки новыми данными
        } else{
          promoCode.drawRow(response.data.row); // добавление новой записи         
        }             
        admin.closeModal($('.b-modal'));        
        promoCode.clearField();
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
    
      var tr = '\
       <tr data-id="'+data.id+'">\
        <td>'+data.id+'</td>\
        <td class="add_datetime">'+data.add_datetime+'</td>\
        <td class="code">'+data.code+'</td>\
        <td class="percent">'+data.percent+'</td>\
        <td class="desc">'+data.desc+'</td>\
        <td class="from_datetime">'+data.from_datetime+'</td>\
        <td class="to_datetime">'+data.to_datetime+'</td>\
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
        pluginHandler: 'promo-code', // плагин для обработки запроса
        id: id               
      },
      
      function(response) {
        admin.indication(response.status, response.msg);
        $('.entity-table-tbody tr[data-id='+id+']').remove();
        if($(".entity-table-tbody tr").length==0){
          var html ='<tr class="no-results">\
            <td colspan="3" align="center">'+promoCode.lang['ENTITY_NONE']+'</td>\
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
        pluginHandler: 'promo-code', // плагин для обработки запроса
        id: id,
        invisible: val,
      },
      function(response) {
        admin.indication(response.status, response.msg);
      } 
      );
    },
    
    getProductByFilter: function() {
      var request = $("form[name=filter]").formSerialize(); 
      admin.show("promo-code", "plugin", request + '&applyFilter=1',promoCode.callbackBackRing);
      return false;
    },
            
    /**
     *Пакет выполняемых действий после загрузки раздела товаров
     */
    callbackBackRing:function() { 
    admin.AJAXCALLBACK = [      
      {callback:'admin.sortable', param:['.entity-table-tbody','promo-code']},       
    ]; 
    },
    
  }
})();

promoCode.init();
//admin.sortable('.entity-table-tbody', 'promo-code');