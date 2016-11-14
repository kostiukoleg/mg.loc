$(document).ready(function () {
  buyOneClickModule.init();
});

var buyOneClickModule = (function () {
  return {
    init: function () {
      
      if ($('.wrapper-modal-mg-buy-click').length == 0) {
        var html = '\
              <div class="wrapper-modal-mg-buy-click" >\
               <div class="header-modal-mg-buy-click">\
                <h2 class="title-modal-mg-buy-click">Быстрая покупка товара</h2>\
                <span class="close-mg-buy-button"><a href="javascript:void(0);"></a></span>\
              </div>\
              <div class="content-modal-mg-buy-click">\
               <div class="mg-product-info" style="display:none">\
                <div class="mg-product-img">\
			           <img class="product-image" src="" >\
                </div>\
                <h2 class="variant"></h2>\
               </div>\
               <div class="mg-order-buy-click">\
		          <form action="' + mgBaseDir + '/" method="post">\
               <ul class="modal-mg-order-list">\
		             <li><h3 class="title"></h3>\
               <h3 class="variant"></h3>\</li>\
                 <li class="fio" style="display:none">\
                  <span>Ваше имя:</span>\
                  <input type="text" name="bc-name" placeholder="Ваше Имя" value ="">\
                 </li>\
                 <li class="phone" style="display:none">\
                  <span>Телефон:<span class="red-star">*</span></span>\
                  <input type="text" name="bc-phone" placeholder="Телефон" value ="">\
                 </li>\
                <li class="email" style="display:none">\
                 <span>Ваш e-mail:<span class="red-star">*</span></span>\
                 <input type="text" name="bc-email" placeholder="Ваш e-mail" value ="">\
                </li>\
                <li class="address" style="display:none">\
                 <span>Адрес:</span>\
                 <textarea name="bc-address" placeholder="Адрес" value =""></textarea>\
                </li>\
                <li class="comment" style="display:none">\
                 <span>Комментарий:</span>\
                 <textarea name="bc-comment" placeholder="Комментарий" value =  ""></textarea>\</li>\
                <li class="mg-cap" style="display:none">\
                <div class="cap-left">\
                  <img style="margin-top: 5px; border: 1px solid gray;" src = "' + mgBaseDir + '/captcha.html" width="140" height="36">\
                  <span>Введите текст с картинки:<span class="red-star">*</span> </span>\
                  <input type="text" name="capcha" class="captcha">\
                </div>\
                <div style="clear:both;">\
                </div>\
               </li>\
              </ul>\
            </form>\
           </div>\
          </div>\
	        <div class="mg-price-buy-click">\
          Стоимость товара: <span class="bc-price"></span> *<span class="bc-count"> 1</span> шт.\
          </div>\
	        <div class="mg-action-buttons">\
		      <button class="close-mg-buy-button mg-plugin-btn">Закрыть</button>\
	  	    <button type = "submit" class="mg-send-order-click-button mg-buy-btn">Купить</button>\
         </div>\
        </div>';

        $('body').append(html);
      }
      // еслы выбран вариант,которого нет на складе
      $('.block-variants input[type=radio]:checked').each(function() {
        if ($(this).data('count') == 0 ) {
          if ($('.wrapper-mg-buy-click').length > 1) { 
            $(this).parents('.product-wrapper').find('.wrapper-mg-buy-click .mg-buy-click-button').hide();
          } else {
            $('.wrapper-mg-buy-click .mg-buy-click-button').hide();
          }
        }  
      });
      // при нажатии на кнопку купить открывается модальное окно
      $('body').on('click', '.mg-buy-click-button', function () {
        var id = $(this).data('product-id');
        if ($('.wrapper-modal-mg-buy-click .loading-send-order').data('buy')== id) {
          openModal($('.wrapper-modal-mg-buy-click'));		
          return true;
        }
        else {
          $('.wrapper-modal-mg-buy-click .loading-send-order').data('buy','');
          $('.wrapper-modal-mg-buy-click .loading-send-order').hide();
          $('.mg-action-buttons .mg-send-order-click-button').show();
          $('.mg-price-buy-click').show();
          $('.content-modal-mg-buy-click').show();
        }
        openOrderForm(id);
        var count = 1;
        var price = '';
        var variant = '';
        // данные из мини-карточки товара (из каталога) или из полной карточки товара 
        if ($(this).parents('.product-wrapper').length) {
          price = $(this).parents('.product-wrapper').find(".product-price .product-default-price:first").text();
          if (price == '') {
            price = $(this).parents('.product-wrapper').find(".product-price").text();
          }
          variant = $(this).parents('.product-wrapper').find('.block-variants input[type=radio]:checked').parents('tr').find('label').text();
        }
        else {
         price = $('body').find(".product-status-list li .price:first").text();
         count = $('body').find(".buy-block .property-form .buy-container input[name=amount_input]").val();
         variant = $('.block-variants input[type=radio]:checked').parents('tr').find('label').text();
        }
        count = (count) ? count : 1;
        if (price) {
          $('.wrapper-modal-mg-buy-click  .mg-price-buy-click .bc-price').text(price);
        }
        $('.wrapper-modal-mg-buy-click  .mg-price-buy-click .bc-count').text(count);
        $('.wrapper-modal-mg-buy-click  .variant').html(variant);
        var image = $('img[data-product-id='+ id +']').attr('src');
        if (image) {
          $('.wrapper-modal-mg-buy-click  .mg-product-img img').attr('src', image );
        }
        $('.wrapper-modal-mg-buy-click .error').remove();
        $('.wrapper-modal-mg-buy-click').show();
        
        openModal($('.wrapper-modal-mg-buy-click'));		
        $('.mg-order-buy-click input[name=bc-phone]').mask(phoneMask);
      });
        // закрытие модального окна
      $('.close-mg-buy-button').click(function () {
        $('.wrapper-modal-mg-buy-click .mg-action-buttons .mg-send-order-click-button').removeAttr('data-id');
        $('.wrapper-modal-mg-buy-click').hide();
        closeModal($('.wrapper-modal-mg-buy-click'));
      });
      
       // оформление заказа по нажатию кнопки купить
      $('.mg-send-order-click-button').click(function () {
        var id = $(this).attr('data-id');
        var name = $(this).parents('.wrapper-modal-mg-buy-click').find('input[name=bc-name]');
        var phone = $(this).parents('.wrapper-modal-mg-buy-click').find('input[name=bc-phone]');
        var email = $(this).parents('.wrapper-modal-mg-buy-click').find('input[name=bc-email]');
        var address = $(this).parents('.wrapper-modal-mg-buy-click').find('textarea[name=bc-address]');
        var comment = $(this).parents('.wrapper-modal-mg-buy-click').find('textarea[name=bc-comment]');
        var capcha = $(this).parents('.wrapper-modal-mg-buy-click').find('input[name=capcha]');
        $.ajax({
          type: "POST",
          url: mgBaseDir + "/ajaxrequest",
          dataType: 'json',
          data: {
            mguniqueurl: "action/sendOrderBuyClick", // действия для выполнения на сервере
            pluginHandler: 'buy-click',
            name: name.val(),
            phone: phone.val(),
            email: email.val(),
            address: address.val(),
            comment: comment.val(),
            capcha: capcha.val(),
          },
          success: function (response) {
            if (response.status != 'error') {
              $('.mg-action-buttons .mg-send-order-click-button').hide();
              $('.wrapper-modal-mg-buy-click .error').remove();
              $('.loading-send-order').remove();
              likeAddToCart(id);
            } else {
              $('.wrapper-modal-mg-buy-click .error').remove();
              $('.title-modal-mg-buy-click').after(response.data.msg);
              $('.loading-send-order').remove();
            }
          }
        });
      });

      /**
       * функция добавления товара в корзину
       * @param {type} e
       * @returns {undefined}
       */

      function likeAddToCart(id) {
        id = parseInt(id);
        if ($('.buy-block').find(".property-form").length) {
          var request = $('.buy-block').find(".property-form").formSerialize();
        } else {
          var inCartProd = 'inCartProductId=' + id;
          var request = inCartProd + '&amount_input=1';
          var variant = $('.mg-buy-click-button[data-product-id='+id+']').parents('.product-wrapper').find('.block-variants input[type=radio]:checked').val();
          if (variant) {
            request = request + '&variant='+ variant;
          }
        };
        $.ajax({
          type: "POST",
          url: mgBaseDir + "/cart",
          data: "ajax=buyclickflag&updateCart=1&" + request,
          dataType: "json",
          cache: false,
          success: function (response) {
            if ('success' == response.status) {
              $('.mg-action-buttons').before("<span data-buy="+id+" class='loading-send-order'>Спасибо за покупку! Наши менеджеры свяжутся с Вами!</span>");
              $('.mg-action-buttons .mg-send-order-click-button').hide();
              $('.mg-price-buy-click').hide();
              $('.content-modal-mg-buy-click').hide();
            }
            else {
              $('.mg-action-buttons').before("<span class='loading-send-order'>Извините, ошибка при отправке заявки. Попробуйте еще раз.</span>");
            }
          }
        });
      }
      
      $('.center').on('change', '.block-variants input[type=radio]', function(){
      if ($(this).data('count') == 0) {
        if ($('.wrapper-mg-buy-click').length > 1) { 
            $(this).parents('.product-wrapper').find('.wrapper-mg-buy-click .mg-buy-click-button').hide();
          } else {
            $('.wrapper-mg-buy-click .mg-buy-click-button').hide();
          }
      }
      else {
        if ($('.wrapper-mg-buy-click').length > 1) { 
            $(this).parents('.product-wrapper').find('.wrapper-mg-buy-click .mg-buy-click-button').show();
          } else {
            $('.wrapper-mg-buy-click .mg-buy-click-button').show();
          }
      }
    });
     
    /**
       * функция загрузки формы для заказа
       * @param {type} e
       * @returns {undefined}
       */
      function openOrderForm(id) {
        $.ajax({
          type: "POST",
          url: mgBaseDir + "/ajaxrequest",
          dataType: 'json',
          data: {
            mguniqueurl: "action/buildOrderForm", // действия для выполнения на сервере
            pluginHandler: 'buy-click',
            id: id
          },
          success: function (response) {
            $('.wrapper-modal-mg-buy-click .mg-action-buttons .mg-send-order-click-button').attr('data-id', id);
            if (response.data.options.header != '') {
              $('.wrapper-modal-mg-buy-click .title-modal-mg-buy-click').html(response.data.options.header);
            }
            $('.wrapper-modal-mg-buy-click  .modal-mg-order-list h3.title').html(response.data.product_title);
            $('.wrapper-modal-mg-buy-click  .mg-price-buy-click .bc-price').html(response.data.price); 
            if (response.data.options.product == 'true') {
              $('.wrapper-modal-mg-buy-click .mg-product-info').css("display","block");
              $('.wrapper-modal-mg-buy-click .modal-mg-order-list .variant').hide();
              if (!$('.wrapper-modal-mg-buy-click  .mg-product-img img').attr('src')) {
                $('.wrapper-modal-mg-buy-click  .mg-product-img img').attr('src', mgBaseDir + '/uploads/' + response.data.product_image );
              }
              }      
            
            if (response.data.options.name == 'true') {
              $('.wrapper-modal-mg-buy-click .modal-mg-order-list li.fio').css("display","block");
              $('.wrapper-modal-mg-buy-click .modal-mg-order-list input[name=bc-name]').val(response.data.user.name);
            }
            if (response.data.options.phone == 'true') {
              $('.wrapper-modal-mg-buy-click .modal-mg-order-list li.phone').css("display","block");
              $('.wrapper-modal-mg-buy-click .modal-mg-order-list input[name=bc-phone]').val(response.data.user.phone);
            }
            if (response.data.options.email == 'true') {
              $('.wrapper-modal-mg-buy-click .modal-mg-order-list li.email').css("display","block");
              $('.wrapper-modal-mg-buy-click .modal-mg-order-list input[name=bc-email]').val(response.data.user.email);
            }
            if (response.data.options.address == 'true') {
              $('.wrapper-modal-mg-buy-click .modal-mg-order-list li.address').css("display","block");
              $('.wrapper-modal-mg-buy-click .modal-mg-order-list textarea[name=bc-address]').val(response.data.user.address);
            }
            if (response.data.options.comment == 'true') {
              $('.wrapper-modal-mg-buy-click .modal-mg-order-list li.comment').css("display","block");
              $('.wrapper-modal-mg-buy-click .modal-mg-order-list textarea[name=bc-comment]').val('');
            }
            if (response.data.options.capcha == 'true') {
              $('.wrapper-modal-mg-buy-click .modal-mg-order-list li.mg-cap').css("display","block");
              $('.wrapper-modal-mg-buy-click .modal-mg-order-list input[name=capcha]').val('');
            };
          }
        });
        return true;
        
      }
      /**
       * Открывает модальное окно
       */
      function openModal(object) {
        //$("body").animate({scrollTop:0}, 300);
        overlay();
        object.fadeIn(300);
        object.css('z-index', 200);
        var offset = (window.pageYOffset );
        object.css('margin-top', offset);
      }

      /**
       * Закрывает модальное окно
       */
      function closeModal(object) {
        object.fadeOut(300);
        $("#overlay-buy-click").remove();
      }

      /**
       * Фон для заднего плана при открытии всплывающего окна
       */
      function overlay() {
        var docHeight = $(document).height();
        $("body").append("<div id='overlay-buy-click'></div>");
        $("#overlay-buy-click").height(docHeight);
      }
    }}
})();
