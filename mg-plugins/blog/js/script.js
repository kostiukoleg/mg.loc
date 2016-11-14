 /* 
 * Модуль  blog, подключается на странице настроек плагина.
 */

var blog = (function() {
  
  return { 
    lang: [], // локаль плагина 
    pluginName: 'blog',
    supportCkeditor: null,
    init: function() {
      
      // установка локали плагина 
      admin.ajaxRequest({
          mguniqueurl: "action/seLocalesToPlug",
          pluginName: 'blog'
        },
        function(response){
          blog.lang = response.data;   
        }
      );        
        
      // Выводит модальное окно для добавления
      $('.admin-center').on('click', '.section-blog .add-new-button', function() {    
        blog.showModal('add');    
      });
      
      // Выводит модальное окно для редактирования статей
      $('.admin-center').on('click', '.section-blog .edit-row', function() {       
        var id = $(this).data('id');
        var entityType = 'item';
        
        if($('.section-blog').hasClass('category')){
          entityType = 'category';
        }
        
        blog.showModal('edit', id, entityType);            
      });
      
      // Сохраняет изменения в модальном окне
      $('.admin-center').on('click', '.section-blog .b-modal .save-button', function() { 
        var id = $(this).attr('id');
        var entityType = 'item';
        
        if($('.section-blog').hasClass('category')){
          entityType = 'category';
        }
        
        blog.saveField(id, entityType);        
      });
      
      
     // Нажатие на кнопку - активности
      $('.admin-center').on('click', '.section-blog .visible', function(){    
        $(this).toggleClass('active');  
        var id = $(this).data('id');
        if($(this).hasClass('active')) { 
          blog.visibleEntity(id, 1); 
          $(this).attr('title', lang.ACT_V_ENTITY);
        }
        else {
          blog.visibleEntity(id, 0);
          $(this).attr('title', lang.ACT_UNV_ENTITY);
        }
        $('#tiptip_holder').hide();
        admin.initToolTip();
      });
      
      // Удаляет запись
      $('.admin-center').on('click', '.section-blog .delete-row', function() {
        var id = $(this).data('id');
        var entityType = 'item';
        
        if($('.section-blog').hasClass('category')){
          entityType = 'category';
        }
        
        blog.deleteEntity(id, entityType);
      });
      
      // Устанавливает количиство выводимых записей в этом разделе.
      $('.admin-center').on('change', '.section-blog .countPrintRowsPage', function(){
        var count = $(this).val();
        
        admin.ajaxRequest({
          pluginHandler: 'blog', // имя папки в которой лежит данный плагин
          actionerClass: "Pactioner", // класс News в news.php - в папке плагина
          action: "setCountPrintRowsNews", // название действия в пользовательском  классе News
          count: count
        },
        function(response) {
          admin.refreshPanel();
        });
        
      });
      
       // Сохраняет базовые настроки запись
      $('.admin-center').on('click', '.section-blog .base-setting-save', function() {
   
        var obj = '{';
        $('.section-blog .list-option input, .section-blog .list-option textarea, .section-blog .list-option select').each(function() {     
          obj += '"' + $(this).attr('name') + '":"' + admin.htmlspecialchars($(this).val()) + '",';
        });
        obj += '}';    

        //преобразуем полученные данные в JS объект для передачи на сервер
        var data =  eval("(" + obj + ")");

        data.nameEntity = $(".section-blog .base-settings input[name=nameEntity]").val();

        admin.ajaxRequest({
          mguniqueurl: "action/saveBaseOption", // действия для выполнения на сервере
          pluginHandler: 'blog', // плагин для обработки запроса
          data: data // id записи
        },

        function(response) {
          admin.indication(response.status, response.msg);      
        }

        );
        
      });      
      
      // Выбор картинки
      /*$('.admin-center').on('click', '.section-blog .browseImage', function() {
        admin.openUploader('blog.getFile');
      });*/     
      
      // Обработчик для загрузки изображения на сервер, сразу после выбора.
      $('body').on('change', '#photoimg', function(){
        if($(this).val()){
          blog.addImageToNews();
        }
      });
      
      // Удаляение изображения записи, как из БД таи физически с сервера.
      $('body').on('click', '.section-blog .cancel-img-upload', function(){
        blog.delImageNews($(this).attr('id'),$('.prev-img img').attr('src'));
      });
      
      //Переключение вида выбора категории: выпадающий список/множественный выбор
      $('.admin-center').on('click', '.section-blog .b-modal .add-category', function(){
        var select = $('.section-blog .b-modal select[name=category_id]');
        if(select.prop("multiple")){
          select.prop("multiple", false);
          select.css("height", "29px");
          select.find('option').eq(0).show();
          $(this).removeClass("opened-list");
        }else{
          select.prop("multiple", true);
          select.css("height", "100px");
          select.find('option').eq(0).hide();
          $(this).addClass("opened-list");
        }
      });
      
      //Переключение с выбора категории на добавление новой
      $('.admin-center').on('click', '.section-blog .b-modal .add-new-cat-change', function(){
        var select = $('.section-blog .b-modal select[name=category_id]');
        var input = select.parent('div').find('input.new-category').parent('span');
        var changeText = $(this).attr("data-change-text");
        var oldText = $(this).find('span').text();
        if(select.is(":visible")){
          select.hide();
          input.show();
        }else{
          select.show();
          input.hide();
        }
        $(this).find('span').text(changeText);
        $(this).attr("data-change-text", oldText);
        changeText = oldText;
      });
      
      //Скрытие/раскрытие настройки периода активности
      $('.admin-center').on('click', '.section-blog .b-modal .set-visible-period', function(){
        var period = $('.section-blog .b-modal div.period-params');
        var changeText = $(this).attr("data-change-text");
        var oldText = $(this).find('span').text();
        period.slideToggle();
        $(this).find('span').text(changeText);
        $(this).attr("data-change-text", oldText);
        changeText = oldText;
      });
      
      //Добавление новой категории в базу и отображение её в списке доступных
      $('.admin-center').on('click', '.section-blog .b-modal .addNewCat', function(){
        if($('input[name=new_category]').val()){
          admin.ajaxRequest({
              mguniqueurl: "action/saveCategory", // действия для выполнения на сервере
              pluginHandler: 'blog', // плагин для обработки запроса
              title: $('input[name=new_category]').val() // название новой категории
            }, function(response){
              var select = $('.section-blog .b-modal select[name=category_id]');
              var option = select.append('<option value='+response.data.id+'>'+response.data.title+'</option>');
              select.find('option[value='+response.data.id+']').prop("selected", true);
              $('.section-blog .b-modal .add-new-cat-change').trigger('click');
            },$('.b-modal .addNewCat')
          );
        }else{}
      });
      
      // Обработчик для смены категории
      $('body').on('change', '.section-blog select[name="category"]', function() {       
        var cat_id= $('.section-blog select[name="category"]').val();
        if(cat_id=="null"){
            cat_id = 0;
        }
        admin.show("blog", cookie("type"), "page=0&category=" + cat_id);   
      });
      
      //Переход к управлению категориями
      $('body').on('click', '.section-blog .manage-categories', function(){
        admin.show("blog", cookie("type"), "manageCats=1&pluginTitle=Блог");
      });
      
      //Переход от кправления категориями, назад к списку статей
      $('body').on('click', '.section-blog .back-to-item-list', function(){
        admin.show("blog", cookie("type"), "page=0&pluginTitle=Блог");
      });
      
      // Показывает панель с настройками.
      $('.admin-center').on('click', '.section-blog .show-property-order', function() {
        $('.property-order-container').slideToggle(function() {
          $('.widget-table-action').toggleClass('no-radius');
        });
      });
      
      // Открывает страницу предпросмотра на сайте.
      $('body').on('click', '.previewPage', function(){
        blog.previewPage();
      });
    },
    
    /* открывает модальное окно 
     * @param {type} type -тип окна, для редактирования или для добавления
     * @param {type} id - номер записи, которая открыта на редактирование
     * @returns {undefined}
     */
    showModal: function(type, id, entity) {
      
      switch (type) {
        case 'add':
          {
            blog.clearField();  
            $('textarea[name=html_content_blog]').ckeditor();
            break;
          }
        case 'edit':
          {
            try{
              if(CKEDITOR.instances['html_content_blog']){         
                CKEDITOR.instances['html_content_blog'].destroy();
              }
            }catch(e){} 
            
            blog.clearField();
            var action = 'getEntity';
            if(entity === 'category'){
              action = 'getCategory';
            }
            admin.ajaxRequest({
              mguniqueurl: "action/"+action, // действия для выполнения на сервере
              pluginHandler: 'blog', // плагин для обработки запроса
              id: id // id записи
            }, 
            blog.fillField(entity), 
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
      
      $('.section-blog .date-from-input').datetimepicker($.timepicker.regional['ru']);
      $('.section-blog .date-to-input').datetimepicker($.timepicker.regional['ru']);
    },
                 
   /**
    * функция для приема файла из аплоадера
    */         
    getFile: function(file) {      
      $('.section-blog .b-modal  input[name="src"]').val(file.url);
    },      
            
   /**
    * Очистка модального окна
    */         
    clearField: function() {
      $('.errorField').css('display','none');
      $('.section-blog .b-modal input').val('');  
      $('.section-blog .b-modal select').prop("multiple", false);
      $('.section-blog .b-modal select option').prop("selected", false); 
      $('.section-blog .b-modal textarea').val('');
      var src=admin.SITE+'/mg-admin/design/images/no-img.png';
      $('.prev-img').html('<img src="'+src+'" alt="" />');
      $('.symbol-count').text('0');
      $('.section-blog .b-modal .cancel-img-upload').attr('id','');
      $('.section-blog .b-modal .save-button').attr('id','');
      blog.supportCkeditor = '';
    },
            
    /**
     * Заполнение модального окна данными из БД
     * @param string entity тип сущности: статьи или категории
     * @returns {undefined}
     */        
    fillField: function(entity) {
      return function(response){
        blog.supportCkeditor = response.data.description;
        $('input[name=title]').val(response.data.title);
        $('input[name=url]').val(response.data.url);  
        $('input[name=tags]').val(response.data.tags);  
        $('input[name=meta_title]').val(response.data.meta_title);
        $('input[name=meta_keywords]').val(response.data.meta_keywords);
        $('textarea[name=meta_desc]').val(response.data.meta_desc);
        
        var src=admin.SITE+'/mg-admin/design/images/no-img.png';
        
        if(response.data.image_url){
          $('.product-upload-img input[name=photoImgName]').val(response.data.image_url);
          src=admin.SITE+'/uploads/blog/'+response.data.image_url;
        }
        
        $('.prev-img').html('<img src="'+src+'" alt="" />');
        
        if(entity === 'item'){
          $('.section-blog input[name=date_active_from]').val(response.data.date_active_from);
          $('.section-blog input[name=date_active_to]').val(response.data.date_active_to);

          if(response.data.categories && response.data.categories[0] != ''){
            var select = $('.section-blog .b-modal select[name=category_id]');
            if(response.data.categories.length > 1){
              select.prop("multiple", true);
              select.find('option').eq(0).hide();
              $('.section-blog .b-modal .add-category').addClass("opened-list");

              /*function checkItems(item, i, arr){

              }*/
              response.data.categories.forEach(function(item, i, arr){
                select.find('option[value='+item+']').prop("selected", true);
              });
            }else{
              select.find('option[value='+response.data.categories+']').prop("selected", true);
            }
          }
        }
        
        $('.symbol-count').text($('textarea[name=meta_desc]').val().length);
        $('.cancel-img-upload').attr('id',response.data.id);
        $('.save-button').attr('id',response.data.id);
        
        $('textarea[name=html_content_blog]').ckeditor(function() {
           this.setData(blog.supportCkeditor);   
        });
      }
    },
    
    /**
     *  Проверка заполненности полей, для каждого поля прописывается свое правило.
     */
    checkRulesForm: function() {
      $('.errorField').css('display','none');
      $('.product-text-inputs input').removeClass('error-input');
      var error = false;

      // наименование не должно иметь специальных символов.
//      if(!admin.regTest(1,$('input[name=title]').val()) || !$('input[name=title]').val()){
//        $('input[name=title]').parent("label").find('.errorField').css('display','block');
//        $('.product-text-inputs input[name=title]').addClass('error-input');
//        error = true;
//      }

      // url обязательно надо заполнить.
      if(!$('input[name=url]').val()){
        $('input[name=url]').parent("label").find('.errorField').css('display','block');
        $('.product-text-inputs input[name=url]').addClass('error-input');
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
    saveField: function(id, entity) {
        
      if(!blog.checkRulesForm()){
          return false;
      }
      
      var action = 'saveEntity';
      
      if(entity === 'category'){
        action = 'saveCategory';
      }
      
      var packedProperty = {
        mguniqueurl: "action/"+action, // действия для выполнения на сервере
        pluginHandler: 'blog', // плагин для обработки запроса
        id: id,
        title: $('.section-blog input[name=title]').val(),
        url: $('.section-blog input[name=url]').val(),
        tags: $('.section-blog input[name=tags]').val(),
        description: $('textarea[name=html_content_blog]').val(),
        meta_title: $('input[name=meta_title]').val(),
        meta_keywords: $('input[name=meta_keywords]').val(),
        meta_desc: $('textarea[name=meta_desc]').val()
      };
      
      if($('.product-upload-img input[name=photoImgName]').val()){
        packedProperty.image_url = $('.product-upload-img input[name=photoImgName]').val();
      }
      
      if(entity === 'item'){
        
        if($('.section-blog input[name=date_active_from]').val()){
          packedProperty.date_active_from = $('.section-blog input[name=date_active_from]').val();
        }
        
        if($('.section-blog input[name=date_active_to]').val()){
          packedProperty.date_active_to = $('.section-blog input[name=date_active_to]').val();
        }
        
        if($('select[name=category_id]').val() && $('select[name=category_id]').val()!=0){
          packedProperty.category_id = $('select[name=category_id]').val();
        }else if($('input[name=new_category]').val()){
          packedProperty.new_category = $('input[name=new_category]').val();
        }  
      }
            
      admin.ajaxRequest(
        packedProperty,
        function(response) {
          admin.indication(response.status, response.msg);
          
          if(id){
            var replaceTr = $('.entity-table-tbody tr[data-id='+id+']');
            
            if(entity === 'item'){
              response.data.activity = replaceTr.find('li.visible').hasClass('active');
            }
            
            blog.drawRow(response.data, entity, replaceTr); // перерисовка строки новыми данными
          }else{
            blog.drawRow(response.data, entity); // добавление новой записи         
          }        
          
          admin.closeModal($('.b-modal'));        
          blog.clearField();
        }
      );
    },
    
    
    /**    
     * Отрисовывает  строку сущности в главной таблице
     * @param {type} data - данные для вывода в строке таблицы
     */        
    drawRow: function(data, entity, replaceTr) {
      
      if(entity === 'item'){
        var activity = (data.activity)?'active':'';
        
        if(replaceTr){
          var date_create = replaceTr.find('td.date-create').text();
          
          if(!data.date_active_from){
            data.date_active_from = '';
          }
          
          var path = replaceTr.find('a.link-to-site').attr('href');
          
          if(data.cat_url){
            path = admin.SITE+"/"+blog.pluginName+"/"+data.cat_url+"/"+data.url;
          }
          
          if(!data.cat_name){
            data.cat_name = '';
          }
          
        }else{
          var date_create = data.date_create;
          
          if(!data.date_active_from){
            data.date_active_from = '';
          }else{
            var arDateTime = data.date_active_from.split(' ');
            var arDate = arDateTime[0].split('-');
            var arTime = arDateTime[1].split(':');
            data.date_active_from = arDate[2]+'.'+arDate[1]+'.'+arDate[0]+' '+arTime[0]+':'+arTime[1];
          }
          
          var path = admin.SITE+"/"+blog.pluginName;
          if(data.cat_url){
            path += "/"+data.cat_url;
          }
          path += "/"+data.url;
          
          if(!data.cat_name){
            data.cat_name = '';
          }
          
        }

        var src=$('tr[id='+data.id+'] .image_url .uploads').attr('src');
        
        if(data.image_url){
          // если идет процесс обновления и картинка новая то обновляем путь к ней
          src=admin.SITE+'/uploads/blog/'+data.image_url;
        }else{
          src=admin.SITE+'/mg-admin/design/images/no-img.png'
        }
        
        var tr = '\
         <tr data-id="'+data.id+'">\
          <td>'+data.id+'</td>\
          <td class="product-picture image_url"><img class="uploads" src="'+src+'" /></td>\
          <td>'+data.title+'\
            <a class="link-to-site tool-tip-bottom" title="'+blog.lang.VIEW_SITE+'" href="'+path+'"  target="_blank" >\
              <img src="'+admin.SITE+'/mg-admin/design/images/icons/link.png" alt="" />\
            </a>\
          </td>\
          <td class="cat_name">'+data.cat_name+'</td>\
          <td class="date-create">'+data.date_active_from+'</td>\
          <td class="actions">\
            <ul class="action-list">\
              <li class="edit-row" data-id="'+data.id+'"><a class="tool-tip-bottom" href="javascript:void(0);" title="'+lang.EDIT+'"></a></li>\
              <li class="visible tool-tip-bottom '+activity+'" data-id="'+data.id+'" title="'+blog.lang.ACT_V_ENTITY+'"><a href="javascript:void(0);"></a></li>\
              <li class="delete-row" data-id="'+data.id+'"><a class="tool-tip-bottom" href="javascript:void(0);"  title="'+lang.DELETE+'"></a></li>\
            </ul>\
          </td>\
        </tr>';
      }else{
        if(replaceTr){
          var path = replaceTr.find('a.link-to-site').attr("href");
        }else{
          var path = admin.SITE+"/"+blog.pluginName+"/"+data.url;
        }
        
        var tr = '\
        <tr data-id="'+data.id+'">\
          <td>'+data.id+'</td>\
          <td>'+data.title+'\
            <a class="link-to-site tool-tip-bottom" title="'+blog.lang.VIEW_SITE+'" href="'+path+'"  target="_blank" >\
              <img src="'+admin.SITE+'/mg-admin/design/images/icons/link.png" alt="" />\
            </a>\
          </td>\
          <td>'+data.url+'</td>\
          <td class="actions">\
            <ul class="action-list">\
              <li class="edit-row" data-id="'+data.id+'"><a class="tool-tip-bottom" href="javascript:void(0);" title="'+lang.EDIT+'"></a></li>\
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
    * Предпросмотр новости
    */
    previewPage: function() {
      admin.ajaxRequest({
        mguniqueurl: "action/getPreview", // действия для выполнения на сервере
        pluginHandler: 'blog', // плагин для обработки запроса
        description: CKEDITOR.instances['product-desc'].getData(),
        title: $('input[name="title"]').val(),
        date_active_from: $('input[name="date_active_from"]').val(),
        image_url: $('input[name="photoImgName"]').val(),
      },
      function(response){
        $('#previewContent').val(response.data);
        $('#previewer').submit();
      });
    },
       
    /**    
     * Удаляет  строку сущности в главной таблице
     * @param {type} data - данные для вывода в строке таблицы
     */           
    deleteEntity: function(id, entity){
      
      if(!confirm(lang.DELETE+'?')){
        return false;
      }
      
      var action = 'deleteEntity';
      
      if(entity === 'category'){
        action = 'deleteCategory';
      }
      
      admin.ajaxRequest({
        mguniqueurl: "action/"+action, // действия для выполнения на сервере
        pluginHandler: 'blog', // плагин для обработки запроса
        id: id               
      },
      function(response){
        admin.indication(response.status, response.msg);
        $('.entity-table-tbody tr[data-id='+id+']').remove();
        if($(".entity-table-tbody tr").length==0){
          var colspan = 6;
          if(entity === 'category'){
            colspan = 4;
          }
          var html ='<tr class="no-results">\
            <td colspan="'+colspan+'" align="center">'+blog.lang['ENTITY_NONE']+'</td>\
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
        pluginHandler: 'blog', // плагин для обработки запроса
        id: id,
        activity: val,
      },
      function(response) {
        admin.indication(response.status, response.msg);
      } 
      );
    },
    
    /**
    * Добавляет изображение продукта
    */
    addImageToNews:function() {
      $('.img-loader').show();
      // отпраквка картинки на сервер

      $("#imageform").ajaxSubmit({
        type:"POST",
        url: "ajax",
        data: {
          pluginHandler:"blog",
          actionerClass:"Pactioner",
          action: "addImageNews"  //передается в скрытом поле в силу специфики плагина form.js
        },
        cache: false,
        dataType: 'json',
        success: function(response){
          admin.indication(response.status, response.msg);
            if(response.status != 'error'){
              var src=admin.SITE+'/uploads/blog/'+response.data.img;
              $('.prev-img').html('<img src="'+src+'" alt="" />');
              $('input[name=photoImgName]').val(response.data.img);
            }else{
              $('.prev-img').html('');
            }
            $('.img-loader').hide();
        }
      });

    },


   /**
    * Удаляет изображение новости
    */
    delImageNews: function(id,imgFile) {
      if(confirm(lang.DELETE_IMAGE+'?')){
        admin.ajaxRequest({
          pluginHandler: 'blog', // имя папки в которой лежит данный плагин
          actionerClass: "Pactioner", // класс News в news.php - в папке плагина
          action: "deleteImageNews", // название действия в пользовательском  классе News
          id: id,
          imgFile: imgFile
        },
        function(response) {
          admin.indication(response.status, response.msg);
          var src=admin.SITE+'/mg-admin/design/images/no-img.png';
          $('.prev-img').html('<img src="'+src+'" alt="" />');
          $('tr[id='+id+'] .uploads').attr('src',src);
          $('.product-upload-img input[name=photoImgName]').val('')
          $('#photoimg').val('');
        }
        );
      }
    }
    
  }
})();

blog.init();