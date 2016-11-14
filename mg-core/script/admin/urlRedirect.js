/**
 * Модуль для работы с перенаправлениями
 */

var urlRedirect = (function() { 
  
  var savedDataRow = {}; // данные редактируемой строки
  var cansel = false; // использовать возврат значений при отмене
  
  return {
    supportCkeditor: null, 
    init: function(){      
      // добавить запись перенаправления
      $('body').on('click', '.seoBlockList .addRedirect', function(){
        urlRedirect.createRow();
      });
      // редактирования строки свойства
      $('body').on('click', '.urlRedirectTable li.edit-row', function(){ 
        urlRedirect.canselEditRow(savedDataRow.id);
        urlRedirect.hideActions(savedDataRow.id);
        urlRedirect.rowToEditRow($(this).attr('id'));
        urlRedirect.showActions($(this).attr('id'));        
      });
      // сохранение строки свойства
      $('body').on('click', '.urlRedirectTable li.save-row', function() {
        urlRedirect.saveEditRow($(this).attr('id'));
        urlRedirect.hideActions($(this).attr('id'));
      });

      // отмена редактирования строки
      $('body').on('click', '.urlRedirectTable li.cancel-row', function() {
        urlRedirect.canselEditRow($(this).attr('id'));
//        urlRedirect.deleteRewrite($(this), $(this).attr('id'));
        urlRedirect.hideActions($(this).attr('id'));
      });
      // смена активности записи 
      $('body').on('click', '.urlRedirectTable li.visible', function(){
        urlRedirect.setActivity($(this), $(this).attr('data-id'));
      });
      // удаление записи 
      $('body').on('click', '.urlRedirectTable li.delete-row', function(){
        urlRedirect.deleteRewrite($(this), $(this).attr('id'));
      });  
      $('body').on('click', '.redirectLinkPage', function (){               
        admin.ajaxRequest({
          mguniquetype: cookie("type"),
          mguniqueurl: admin.SECTION+'.php',
          seo_pager: 1,
          group: "STNG_SEO_GROUP_2",
          redirectPage: $(this).text()
        }, function(response){          
          $('.urlRedirectTable').empty();
          
          response.data.forEach(function(element, index, array){
            $('.urlRedirectTable').append(urlRedirect.drawRow(element));
          });
          
          $('.urlRedirectList tfoot td').html(response.pager);          
        });
        return false;
      });
    },   
    deleteRewrite: function(obj, id){
      admin.ajaxRequest({
        mguniqueurl: "action/deleteUrlRedirect",
        id: id,        
      },
      function (response) {
        if(response.status != "error"){
          obj.parents("tr.rewrite-line").remove();
        }
          
        admin.indication(response.status, response.msg);        
      });
    },
    setActivity: function(obj, id){
      var activity = 1;
      
      if(obj.hasClass('active')){
        activity = 0;
      }
      
      admin.ajaxRequest({
        mguniqueurl: "action/setUrlRedirectActivity",
        id: id,
        activity: activity
      },
      function (response) {
        if(response.status != "error"){
          obj.toggleClass('active');
        }
          
        admin.indication(response.status, response.msg);        
      });
    },
    createRow: function(notCreate){
      if(notCreate){
        
      }
      admin.ajaxRequest({
        mguniqueurl: "action/addUrlRedirect"
      },
      function(response) {
        admin.indication(response.status, response.msg);                                                         
        var row = urlRedirect.drawRow(response.data);

        if ($(".urlRedirectList tr[class=tempMsg]").length != 0) {
          $(".urlRedirectTable").html('');
        }
        $('.urlRedirectTable').prepend(row);      
        
        urlRedirect.canselEditRow(savedDataRow.id);
        urlRedirect.hideActions(savedDataRow.id);
        urlRedirect.rowToEditRow(response.data.id);
        urlRedirect.showActions(response.data.id);
      });            
    },
    drawRow:function(data){
      var codeName = 'REDIRECT_MESSAGE_'+data.code;
      var row = '\
        <tr id="'+data.id+'" class="rewrite-line">\\n\
          <td class="url_old">'+data.url_old+'</td>\
          <td class="url_new">'+data.url_new+'</td>\
          <td class="code" value="'+data.code+'">'+lang[codeName]+'</td>\
          <td class="actions">\
            <ul class="action-list">\
              <li class="save-row" id="' + data.id + '" style="display:none"><a class="tool-tip-bottom" href="javascript:void(0);" title="' + lang.SAVE + '"></a></li>\
              <li class="cancel-row" id="' + data.id + '" style="display:none"><a class="tool-tip-bottom" href="javascript:void(0);" title="' + lang.CANCEL + '"></a></li>\
              <li class="edit-row" id="'+data.id+'"><a href="javascript:void(0);" title="'+lang.EDIT+'"></a></li>\
              <li class="visible tool-tip-bottom active" data-id="'+data.id+'" title="'+lang.ACTIVITY+'" ><a href="javascript:void(0);"></a></li>\
              <li class="delete-row" id="'+data.id+'"><a href="javascript:void(0);"  title="'+lang.DELETE+'"></a></li>\
            </ul>\
          </td>\
        </tr>';
      
      return row;
    },
    //Отменяет редактирование
    canselEditRow: function(id) {
      if(cansel){
        var url_old = $('.urlRedirectTable tr[id=' + id + '] td[class=url_old]');
        url_old.text(savedDataRow.url_old);
        
        var url_new = $('.urlRedirectTable tr[id=' + id + '] td[class=url_new]');
        url_new.text(savedDataRow.url_new);
        
        var code = $('.urlRedirectTable tr[id=' + id + '] td[class=code]');
        code.text(savedDataRow.code);
      }
    },
    // показывает дополнительные  действия при редактировании
    showActions: function(id) {               
      $('.urlRedirectTable tr[id=' + id + '] .cancel-row').show();
      $('.urlRedirectTable tr[id=' + id + '] .save-row').show();      
      $('.urlRedirectTable tr[id=' + id + '] .edit-row').hide(); 
    },
    // скрывает дополнительные  действия при редактировании
    hideActions: function(id) {                        
      $('.urlRedirectTable tr[id=' + id + '] .cancel-row').hide();
      $('.urlRedirectTable tr[id=' + id + '] .save-row').hide();
      $('.urlRedirectTable tr[id=' + id + '] .edit-row').show();      
    },
    //Делает поля доступными для редактирования
    rowToEditRow: function(id) {
      cansel = true;
      var url_old = $('.urlRedirectTable tr[id=' + id + '] td[class=url_old]');
      var urlOldVal = url_old.text();
      url_old.html('<input name="url_old" type="text" class="custom-input tool-tip-bottom" title="' + lang.T_TIP_STNG_SEO_URL_REDIRECT_OLD_URL + '" value="' + url_old.text() + '">');
      	 
      var url_new = $('.urlRedirectTable tr[id=' + id + '] td[class=url_new]');
      var urlNewVal = url_new.text();
      url_new.html('<input name="url_new" type="text" class="custom-input tool-tip-bottom" title="' + lang.T_TIP_STNG_SEO_URL_REDIRECT_NEW_URL + '" value="' + url_new.text() + '">');
      
      var code = $('.urlRedirectTable tr[id=' + id + '] td[class=code]');
      var codeVal = code.attr('value');
      var codeChoise = '\
        <select name="code" class="custom-input tool-tip-bottom">\
          <option value="300">' + lang.REDIRECT_MESSAGE_300 + '</option>\
          <option value="301">' + lang.REDIRECT_MESSAGE_301 + '</option>\
          <option value="302">' + lang.REDIRECT_MESSAGE_302 + '</option>\
          <option value="303">' + lang.REDIRECT_MESSAGE_303 + '</option>\
          <option value="307">' + lang.REDIRECT_MESSAGE_307 + '</option>\
        </select>';
      code.html(codeChoise);
      code.find('option[value='+codeVal+']').prop('selected', 'selected');

      savedDataRow = {
        id: id,
        url_old: urlOldVal,
        url_new: urlNewVal,
        code: codeVal,        
      };

      admin.initToolTip();
    },
    //Сохраняет редактирование
    saveEditRow: function(id) {                      
      cansel = false;
      
      var url_old = $('.urlRedirectTable tr[id=' + id + '] td[class=url_old]');
      var urlOldVal = url_old.find('input').val();
      url_old.text(urlOldVal);
      
      var url_new = $('.urlRedirectTable tr[id=' + id + '] td[class=url_new]');
      var urlNewVal = url_new.find('input').val();
      url_new.text(urlNewVal);
      
      var code = $('.urlRedirectTable tr[id=' + id + '] td[class=code]');
      var codeVal = code.find('select').val();
      code.text(code.find('option[value='+codeVal+']').text());

      // удаляем обработчик показа установки дефолтного значения
      $('.itemData').unbind();
      $('.list-prop').unbind();      
       
      admin.ajaxRequest({
        mguniqueurl: "action/saveUrlRedirect",
        id: id,
        url_old: urlOldVal,
        url_new: urlNewVal,
        code: codeVal,
      },
      function(response) {        
        var data = {};
        data.url_old = urlOldVal;
        data.url_new = urlNewVal;
        data.code = codeVal;
        data.id = id;
        var row = urlRedirect.drawRow(data);
        $('.urlRedirectTable tr[id='+id+']').replaceWith(row);
        admin.indication(response.status, response.msg);
      });
    },
  };
})();

// инициализация модуля при подключении
urlRedirect.init();