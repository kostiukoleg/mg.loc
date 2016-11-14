$(document).ready(function () {
  if ($((window.location.hash).length)) {
    var id = window.location.hash.substring(1);
    answerId = window.location.hash.substring(1);
    $('[data-question-id =' + id + ']').attr('id', id); 
    $('[data-question-id =' + id + ']').addClass("open");
    $('[data-answer-id =' + id + ']').show();
  }
  $('.question').click(function () {
    var id = $(this).attr('data-question-id');
    $('[data-question-id =' + id + ']').removeAttr('id');
    $('[data-answer-id =' + id + ']').slideToggle(200);
    $(this).toggleClass("open");
  });
});
