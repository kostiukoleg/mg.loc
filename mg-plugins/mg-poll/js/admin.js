 /* 
 * Модуль  mgPoll, подключается на странице настроек плагина.
 */

var mgPoll = (function() {
  
  return { 
    lang: [], // локаль плагина 
    pluginName: 'mg-poll',
    supportCkeditor: null,
    delAnswers: [],
    init: function() {
      
      // установка локали плагина 
      admin.ajaxRequest({
          mguniqueurl: "action/seLocalesToPlug",
          pluginName: 'blog'
        },
        function(response){
          mgPoll.lang = response.data;   
        }
      );        
        
      // Выводит модальное окно для добавления
      $('.admin-center').on('click', '.section-'+mgPoll.pluginName+' .add-new-button', function() {    
        mgPoll.showModal('add');    
      });
      
      // Выводит модальное окно для редактирования статей
      $('.admin-center').on('click', '.section-'+mgPoll.pluginName+' .edit-row', function() {       
        var id = $(this).data('id');
        
        mgPoll.showModal('edit', id);            
      });
      
      // Сохраняет изменения в модальном окне
      $('.admin-center').on('click', '.section-'+mgPoll.pluginName+' .b-modal .save-button', function() { 
        var id = $(this).attr('id');
        mgPoll.saveField(id);        
      });
      
      
     // Нажатие на кнопку - активности
      $('.admin-center').on('click', '.section-'+mgPoll.pluginName+' .visible', function(){    
        $(this).toggleClass('active');  
        var id = $(this).data('id');
        if($(this).hasClass('active')) { 
          mgPoll.visibleEntity(id, 1); 
          $(this).attr('title', lang.ACT_V_ENTITY);
        }
        else {
          mgPoll.visibleEntity(id, 0);
          $(this).attr('title', lang.ACT_UNV_ENTITY);
        }
        $('#tiptip_holder').hide();
        admin.initToolTip();
      });
      
      // Удаляет запись
      $('.admin-center').on('click', '.section-'+mgPoll.pluginName+' .delete-row', function() {
        var id = $(this).data('id');
        
        mgPoll.deleteEntity(id);
      });
      
      // Устанавливает количиство выводимых записей в этом разделе.
      $('.admin-center').on('change', '.section-'+mgPoll.pluginName+' .countPrintRowsPage', function(){
        var count = $(this).val();
        
        admin.ajaxRequest({
          pluginHandler: mgPoll.pluginName, // имя папки в которой лежит данный плагин
          actionerClass: "Pactioner", // класс News в news.php - в папке плагина
          action: "setCountPrintRowsNews", // название действия в пользовательском  классе News
          count: count
        },
        function(response) {
          admin.refreshPanel();
        });
        
      });
      
       // Сохраняет базовые настроки запись
      $('.admin-center').on('click', '.section-'+mgPoll.pluginName+' .base-setting-save', function() {
   
        var obj = '{';
        $('.section-'+mgPoll.pluginName+' .list-option input, .section-'+mgPoll.pluginName+' .list-option textarea, .section-'+mgPoll.pluginName+' .list-option select').each(function() {     
          obj += '"' + $(this).attr('name') + '":"' + admin.htmlspecialchars($(this).val()) + '",';
        });
        obj += '}';    

        //преобразуем полученные данные в JS объект для передачи на сервер
        var data =  eval("(" + obj + ")");

        data.nameEntity = $('.section-'+mgPoll.pluginName+' .base-settings input[name=nameEntity]').val();

        admin.ajaxRequest({
          mguniqueurl: "action/saveBaseOption", // действия для выполнения на сервере
          pluginHandler: mgPoll.pluginName, // плагин для обработки запроса
          data: data // id записи
        },

        function(response) {
          admin.indication(response.status, response.msg);      
        }

        );
        
      });      
      
      //Скрытие/раскрытие настройки периода активности
      $('.admin-center').on('click', '.section-'+mgPoll.pluginName+' .b-modal .set-visible-period', function(){
        var period = $('.section-blog .b-modal div.period-params');
        var changeText = $(this).attr("data-change-text");
        var oldText = $(this).find('span').text();
        period.slideToggle();
        $(this).find('span').text(changeText);
        $(this).attr("data-change-text", oldText);
        changeText = oldText;
      });
      
      // Показывает панель с настройками.
      $('.admin-center').on('click', '.section-blog .show-property-order', function() {
        $('.property-order-container').slideToggle(function() {
          $('.widget-table-action').toggleClass('no-radius');
        });
      });
      
      $('.admin-center').on('click', '.section-'+mgPoll.pluginName+' .b-modal #add-answer-button', function(){
        mgPoll.addNewAnswerLine();
      });
      
      $('.admin-center').on('click', '.section-'+mgPoll.pluginName+' .b-modal .delete-answer', function(){
        var label = $(this).parents('label');
        var id = label.attr('data-id');
        if(id > 0){
          mgPoll.delAnswers[mgPoll.delAnswers.length] = id;
        }
        label.remove();
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
            mgPoll.clearField();  
            for(var i=1; i<6; i++){
              mgPoll.addNewAnswerLine(i);
            }
            break;
          }
        case 'edit':
          { 
            mgPoll.clearField();
            admin.ajaxRequest({
              mguniqueurl: "action/getEntity", // действия для выполнения на сервере
              pluginHandler: mgPoll.pluginName, // плагин для обработки запроса
              id: id // id записи
            }, 
            mgPoll.fillField(id), 
            $('.b-modal .widget-table-body')); // вывод лоадера в контейнер окна, пока идет загрузка данных);
            break;
          }
        default:
          {
            break;
          }
      }
      
      admin.openModal($('.b-modal')); 
      
      
      
      $('.error-input').removeClass('error-input');
      
      $.timepicker.regional['ru'] = {
        prevText: '<Пред',
        nextText: 'След>',
        monthNames: ['Январь','Февраль','Март','Апрель','Май','Июнь',
          'Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь'],
        monthNamesShort: ['Янв','Фев','Мар','Апр','Май','Июн',
          'Июл','Авг','Сен','Окт','Ноя','Дек'],
        dayNames: ['воскресенье','понедельник','вторник','среда','четверг','пятница','суббота'],
        dayNamesShort: ['вск','пнд','втр','срд','чтв','птн','сбт'],
        dayNamesMin: ['Вс','Пн','Вт','Ср','Чт','Пт','Сб'],
        timeText: 'Время:',
        hourText: 'Часы',
        minuteText: 'Минуты',
        secondText: 'Секунды',
        millisecText: 'Миллисекунды',
        currentText: 'Сейчас',
        closeText: 'Применить',
        dateFormat: 'dd.mm.yy',
        isRTL: false
      };
      
      $('.section-'+mgPoll.pluginName+' .date-from-input').datetimepicker($.timepicker.regional['ru']);
      $('.section-'+mgPoll.pluginName+' .date-to-input').datetimepicker($.timepicker.regional['ru']);
    },
            
   /**
    * Очистка модального окна
    */         
    clearField: function() {
      $('.errorField').css('display','none');
      $('.section-'+mgPoll.pluginName+' .b-modal input').val('');  
      $('.section-'+mgPoll.pluginName+' .poll-answers').empty();
      $('.section-'+mgPoll.pluginName+' .b-modal .save-button').attr('id','');
    },
            
    /**
     * Заполнение модального окна данными из БД
     * @param string entity тип сущности: статьи или категории
     * @returns {undefined}
     */        
    fillField: function() {
      return function(response){
        $('input[name=question]').val(response.data.question);
        
        $('.section-'+mgPoll.pluginName+' input[name=date_active_from]').val(response.data.date_active_from);
        $('.section-'+mgPoll.pluginName+' input[name=date_active_to]').val(response.data.date_active_to);
        
        if(response.data.answers.length > 0){
          response.data.answers.forEach(function(answer, i){
            mgPoll.drawAnswerRow(answer, i+1);
          });
        }else{
          for(var i=1; i<6; i++){
            mgPoll.addNewAnswerLine(i);
          }
        }
        
        $('.save-button').attr('id',response.data.id);
      }
    },
    
    drawAnswerRow: function(data, count){
      var answer = '\
        <label data-id="'+data.id+'">\
          <span class="custom-text">Ответ '+count+':</span>\
          <input type="text" name="answer[]" data-id="'+data.id+'" value="'+data.answer+'" class="product-name-input">\
          <span class="vote-count">'+data.votes+'</span>\
          <a class="delete-answer" href="javascript:void(0);">Удалить</a>\
        </label>';
      
      $('.poll-answers').append(answer);
    },
    
    /**
     *  Проверка заполненности полей, для каждого поля прописывается свое правило.
     */
    checkRulesForm: function() {
      $('.errorField').css('display','none');
      $('.product-text-inputs input').removeClass('error-input');
      var error = false;

      // наименование не должно иметь специальных символов.
      if(!admin.regTest(1,$('input[name=question]').val()) || !$('input[name=question]').val()){
        $('input[name=question]').parent("label").find('.errorField').css('display','block');
        $('.product-text-inputs input[name=question]').addClass('error-input');
        error = true;
      }
      
      var answers_count = 0;
      
      $('.section-'+mgPoll.pluginName+' input[name="answer[]"]').each(function(){
        var value = $(this).val();
        if(value.length > 0){
          answers_count++;
        }
      });
      
      $('.section-'+mgPoll.pluginName+' input[name="new_answer[]"]').each(function(){
        var value = $(this).val();
        if(value.length > 0){
          answers_count++;
        }
      });
      
      if(answers_count < 2){
        $('.errorField.answers').css('display','block');
        error = true;
      }

      if(error == true){
        return false;
      }

      return true;
    },
    
    /**
     * Сохранение данных из модального окна
     * @param {type} id
     * @returns {undefined}
     */        
    saveField: function(id) {
      
      if(!mgPoll.checkRulesForm()){
        return false;
      }
      
      var answers = [];
      var new_answers = [];
      
      $('.section-'+mgPoll.pluginName+' input[name="answer[]"]').each(function(){
        var value = $(this).val();
        if(value.length > 0){
          answers[$(this).attr('data-id')] = value;
        }
      });
      
      $('.section-'+mgPoll.pluginName+' input[name="new_answer[]"]').each(function(){
        var value = $(this).val();
        if(value.length > 0){
          new_answers[new_answers.length] = value;
        }
      });
      
      var packedProperty = {
        mguniqueurl: "action/saveEntity", // действия для выполнения на сервере
        pluginHandler: mgPoll.pluginName, // плагин для обработки запроса
        id: id,
        question: $('.section-'+mgPoll.pluginName+' input[name=question]').val(),
        answers: answers,
        new_answers: new_answers,
        del_answers: mgPoll.delAnswers
      };
      
      if($('.section-'+mgPoll.pluginName+' input[name=date_active_from]').val()){
        packedProperty.date_active_from = $('.section-'+mgPoll.pluginName+' input[name=date_active_from]').val();
      }

      if($('.section-'+mgPoll.pluginName+' input[name=date_active_to]').val()){
        packedProperty.date_active_to = $('.section-'+mgPoll.pluginName+' input[name=date_active_to]').val();
      }

            
      admin.ajaxRequest(
        packedProperty,
        function(response) {
          admin.indication(response.status, response.msg);
          
          if(id){
            var replaceTr = $('.entity-table-tbody tr[data-id='+id+']');
            response.data.activity = replaceTr.find('li.visible').hasClass('active');
            mgPoll.drawRow(response.data, replaceTr); // перерисовка строки новыми данными
          }else{
            mgPoll.drawRow(response.data); // добавление новой записи         
          }        
          
          admin.closeModal($('.b-modal'));        
          mgPoll.clearField();
        }
      );
    },
    
    
    /**    
     * Отрисовывает  строку сущности в главной таблице
     * @param {type} data - данные для вывода в строке таблицы
     */        
    drawRow: function(data, replaceTr) {
      
      var activity = (data.activity)?'active':'',
          votes_count = 0,
          date_from = (data.date_active_from)?data.date_active_from:replaceTr.find('td.date_from').text();
        
      if(replaceTr){
        votes_count = replaceTr.find('td.votes-count').text();
        
        var tr = '\
         <tr data-id="'+data.id+'">\
          <td>'+data.id+'</td>\
          <td class="title">'+data.question+'</td>\
          <td class="date_from">'+date_from+'</td>\
          <td class="votes-count">'+votes_count+'</td>\
          <td class="actions">\
            <ul class="action-list">\
              <li class="edit-row" data-id="'+data.id+'"><a class="tool-tip-bottom" href="javascript:void(0);" title="'+lang.EDIT+'"></a></li>\
              <li class="visible tool-tip-bottom '+activity+'" data-id="'+data.id+'" title="'+mgPoll.lang.ACT_V_ENTITY+'"><a href="javascript:void(0);"></a></li>\
              <li class="delete-row" data-id="'+data.id+'"><a class="tool-tip-bottom" href="javascript:void(0);"  title="'+lang.DELETE+'"></a></li>\
            </ul>\
          </td>\
        </tr>';
      }else{
        var tr = '\
        <tr data-id="'+data.id+'">\
          <td>'+data.id+'</td>\
          <td>'+data.question+'</td>\
          <td class="date_from">'+date_from+'</td>\
          <td>'+votes_count+'</td>\
          <td class="actions">\
            <ul class="action-list">\
              <li class="edit-row" data-id="'+data.id+'"><a class="tool-tip-bottom" href="javascript:void(0);" title="'+lang.EDIT+'"></a></li>\
              <li class="visible tool-tip-bottom active" data-id="'+data.id+'" title="'+mgPoll.lang.ACT_V_ENTITY+'"><a href="javascript:void(0);"></a></li>\
              <li class="delete-row" data-id="'+data.id+'"><a class="tool-tip-bottom" href="javascript:void(0);"  title="'+lang.DELETE+'"></a></li>\
            </ul>\
          </td>\
        </tr>';
      }
      
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
    deleteEntity: function(id, entity){
      
      if(!confirm(lang.DELETE+'?')){
        return false;
      }
      
      admin.ajaxRequest({
        mguniqueurl: "action/deleteEntity", // действия для выполнения на сервере
        pluginHandler: mgPoll.pluginName, // плагин для обработки запроса
        id: id               
      },
      function(response){
        admin.indication(response.status, response.msg);
        $('.entity-table-tbody tr[data-id='+id+']').remove();
        if($(".entity-table-tbody tr").length==0){
          var colspan = 5;
          var html ='<tr class="no-results">\
            <td colspan="'+colspan+'" align="center">'+mgPoll.lang['ENTITY_NONE']+'</td>\
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
        pluginHandler: mgPoll.pluginName, // плагин для обработки запроса
        id: id,
        activity: val,
      },function(response) {
        admin.indication(response.status, response.msg);
      });
    },
    
    /*Добавляет новую строку с полем для ввода ответа*/
    addNewAnswerLine: function(number){
      var label = 'Новый ответ';
      
      if(number > 0){
        label = 'Ответ '+number;
      }
      
      var answer = '\
        <label data-id="0">\
          <span class="custom-text">'+label+':</span>\
          <input type="text" name="new_answer[]" value="" class="product-name-input">\
          <span class="vote-count">0</span>\
          <a class="delete-answer" href="javascript:void(0);">Удалить</a>\
        </label>';
      $('.section-'+mgPoll.pluginName+' .b-modal .poll-answers').append(answer);
    },
    
  }
})();

mgPoll.init();