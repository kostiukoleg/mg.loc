/**
 * Модуль для обработки ошибок запроса.
 */

var errors = (function() {
  // тут будет обработка ошибок возращаемых в ajaxReuest
  return {
    noneReport: '',
    //Показывает всплывающее окно с текстом ошибки
    showErrorBlock: function (errorText) {
      var errorDescHide = '';

      if (errorText.length == 0) {
        errorDescHide = 'style="display:none;"';
      }

      var infoText = errors.getErrorText(errorText);
      var errorBox = "" +
        "<div class='error-box'>" +
        "<a href='javascript:void(0)' class='close-notification' onclick='$(\".error-box\").remove()'></a>" +
        "<div class='sorry-error'>" + lang.SORRY_ERROR + "<br>" +
        "<!--<a href='https://moguta.ru/feedback' "+errors.noneReport+">" + lang.TECHNICAL_SUPPORT + "</a>-->" +
        "</div><div class='text-error'><div class='description'></div>" +
        "<a href='javascript:void(0);' onclick='errors.openError()'"+errorDescHide+">" + lang.TECHNICAL_ERROR_DESCRIPTION + "</a>" +
        "<div class='original' style='display: none'>" + errorText + "</div></div>" +
        "<a href='javascript:void(0);' "+errors.noneReport+" class='custom-btn send-report-btn' onClick='admin.downimg()' >" +
        "<span>Отправить отчет об ошибке</span></a><div class='clear'></div></div>";
      $('.error-box').remove();
      $('body').append(errorBox);
      admin.centerPosition($('.error-box'));
      $('.error-box .text-error .description').html(infoText);
    },

    openError: function () {
      $('.error-box .text-error .original').toggle();
    },

    //Возвращает текст для всплывающего окна, основываясь на тесте полученной ошибки
    getErrorText: function (errorText) {
      lang.SORRY_ERROR = 'Произошла непредвиденная ошибка на стороне вашего веб-сервера. Для устранения, пожалуйста, обратитесь в техническую поддержку хостинг-провайдера.';

      if (/The requested URL could not be retrieved/.test(errorText)) {
        errorText = "Не удалось получить ответ от веб-сервера. Перезагрузите страницу браузера. Если проблема будет повторяться, " +
          "то необходимо обратиться к администратору сайта или в техническую поддержку вашего хостинг-провайдера.";
        errors.noneReport = 'style="display:none;"';
      } else if (/Connection timed out/.test(errorText)) {
        errorText = "Подключение было прервано, в связи с истечением времени ожидания отклика. Перезагрузите страницу браузера. Если проблема будет повторяться, " +
          "то необходимо обратиться к администратору сайта или в техническую поддержку вашего хостинг-провайдера.";
        errors.noneReport = 'style="display:none;"';
      } else if (/You have an error in your SQL syntax/.test(errorText)) {
        lang.SORRY_ERROR = 'Произошла непредвиденная ошибка системы или подключенного плагина.';
        errorText = "В одном из SQL запросов произошла ошибка. Вы можете сформировать автоматический отчет и отправить в службу технической поддержки Moguta.CMS";
      } else if (/server has gone away/.test(errorText)) {
        lang.SORRY_ERROR = 'Произошла непредвиденная ошибка системы или подключенного плагина.';
        errorText = "Сервер закрыл соединение по таймауту или превышен разрешенный хостингом объем SQL запроса. Инструкция по устранению ошибки: <a href='http://wiki.moguta.ru/faq/oshibki/mysql-server-has-gone-away'>http://wiki.moguta.ru/faq/oshibki/mysql-server-has-gone-away</a>";
      }  else if (/error 28 from table/.test(errorText)) {
        lang.SORRY_ERROR = 'Произошла непредвиденная ошибка на стороне вашего веб-сервера. Для устранения, пожалуйста, обратитесь в техническую поддержку хостинг-провайдера.';
        errorText = "Данная ошибка возникает если на хостинге остается слишком мало свободного места. Необходимо удалить лишние файлы с сервера или увеличить объем допустимого места.";
        errors.noneReport = 'style="display:none;"';
      }  else if (/Bad Gateway/.test(errorText)) {
        errorText = "Системе не удалось получить ответ от сервера, попробуйте выполнить следующие действия:" +
          "<br/> Проверьте, имеется ли доступ к Интернету;" +
          "<br/> Если проблем с доступом нет — можно удалить файлы cookies." +
          "<br/> Если указанное выше не помогло — проблема на стороне сервера. " +
          "<br/> Следует обратиться к системному администратору сайта или в техническую поддержку хостинг-провайдера.";
        errors.noneReport = 'style="display:none;"';
      } else if (/Your request timed out/.test(errorText)) {
        errorText = "Запрос не выполнился в отведенное для него время. Перезагрузите страницу браузера. Если проблема будет повторяться, " +
          "то необходимо обратиться к администратору сайта или в техническую поддержку хостинг-провайдера.";
        errors.noneReport = 'style="display:none;"';
      } else if (/Internal Server Error/.test(errorText)) {
        errorText = "Произошла внутренняя ошибка вашего веб-сервера. Для уточнения причин обратитесь, " +
          "пожалуйста, в техническую поддержку хостинг-провайдера.";
        errors.noneReport = 'style="display:none;"';
      } else if (/Access denied for user/.test(errorText)) {
        errorText = "Не удается подключиться к серверу баз данных, неверный логин или пароль. " +
          "Проверьте правильность введенных данных в файле config.ini";
        errors.noneReport = 'style="display:none;"';
      } else if (/Access denied/.test(errorText)) {
        errorText = "Нехватает прав доступа для выполнения запроса. Проверьте права доступа " +
          "к файлам на сервере.";
        errors.noneReport = 'style="display:none;"';
      } else if (/Can't connect to MySQL server on/.test(errorText)) {
        errorText = "Не удается произвести подключение к серверу баз данных MySQL. Проверьте, " +
          "пожалуйста, работу сервера баз данных.";
        errors.noneReport = 'style="display:none;"';
      } else if (/Proxy Error/.test(errorText)) {
        errorText = "Не удалось достучаться до сервера.";
        errors.noneReport = 'style="display:none;"';
      } else if (/Unknown MySQL server host/.test(errorText)) {
        errorText = "Не удается подключиться к серверу баз данных, неверный адрес сервера." +
          "Проверьте правильность введенных данных в файле config.ini";
        errors.noneReport = 'style="display:none;"';
      }

      if(errorText==''){
        errorText = 'Причиной данного сообщения могло послужить завершение сеанса работы пользователя.\n\
              Попробуйте авторизоваться заново. Если ошибка продолжает происходить, \
              проверьте содержание файла с логом ошибок сервера, для выявления причины. \
              Или включите показ ошибок в файле index.php заменив строку Error_Reporting(0); на Error_Reporting(1);';
        errors.noneReport = 'style="display:none;"';
        lang.SORRY_ERROR = "Произошла непредвиденная ошибка. Пожалуйста, убедитесь что ваш web-сервер соответствует" +
          " необходимым требованиям и все PHP модули подключены. Все файлы сайта и шаблона должны иметь кодировку " +
          "UTF-8 без BOM. <br> Необходимые для корректной работы движка модули:  mysqli, json, curl, php_zip, gd, " +
          "xmlwriter, xmlreader";
      }

      return errorText;
    }
  }
}());