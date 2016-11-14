/*Скрипты для публиной части плагина опросов*/
$(document).ready(function(){
  $('body').on('click', '.button-vote', function(){
    var container = $(this).parents('div.mg-poll-container');
    var questionId = $(this).attr('data-id');
    $(this).attr('data-id', '');
    var answerId = container.find('input[name=poll-answer]:checked').val();
    
    if(!questionId){
      return false;
    }
    
    $.ajax({
      type: "POST",
      url: mgBaseDir+"/ajaxrequest",
      data: {
        pluginHandler: 'mg-poll', // имя папки в которой лежит данный плагин
        actionerClass: 'Pactioner', // класс Pactioner в Pactioner.php - в папке плагина
        action: 'addVote', // название действия в пользовательском  классе  
        id: answerId, //id ответа
        question_id: questionId
      },
      cache: false,
      dataType: 'json',
      success: function(response){
        container.find('.mgPollForm').remove();
        container.append(response.data.html);
      }
    });
    
    return false;
  });
});