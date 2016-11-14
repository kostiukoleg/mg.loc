$(document).ready(function(){
  
  var phone = $('.content-modal-back-ring input');
  var comment = $('.content-modal-back-ring textarea');
  
  $('.back-ring-button').click(function(){
    $('.wrapper-modal-back-ring').show();
    comment.val('');
    phone.val('');
  });
  
  $('.close-ring-button').click(function(){
    $('.wrapper-modal-back-ring').hide();
  });
  
  $('.send-ring-button').click(function(){
    if(comment.val()=="" || phone.val()==""){
      alert('Необходимо заполнить поля формы');
      return false;
    }
    
    $('.send-ring-button').hide();
    $('.send-ring-button').before("<span class='loading-send-ring'>Подождите, идет отправка заявки...</span>");    
    
    $.ajax({    
      type: "POST",
			url: mgBaseDir+"/ajax",	
      dataType: 'json',
      data:{
        mguniqueurl: "action/saveEntity", // действия для выполнения на сервере
        pluginHandler: 'back-ring',
        value: comment.val(),   
        nameEntity: phone.val(),   
        invisible: 1,
      },
      success: function(response){       
        if(response.status!='error'){         
          alert('Ваша заявка #'+response.data.row.id+' принята. Наши менеджеры свяжутся с вами!');
          $('.wrapper-modal-back-ring').hide();
          $('.send-ring-button').show();
          $('.loading-send-ring').remove();         
        }
      }
    });
  }); 
  
});