 /* 
 * Модуль  sitemapGenerator, подключается на странице настроек плагина.
 */

var sitemapGenerator = (function() {
  
  return { 
    lang: [], // локаль плагина 
    init: function() {      
      
      // установка локали плагина 
      admin.ajaxRequest({
          mguniqueurl: "action/seLocalesToPlug",
          pluginName: 'sitemap-generator'
        },
        function(response) {
          sitemapGenerator.lang = response.data;        
        }
      );        
        
      // Выводит модальное окно для добавления
      $('.admin-center').on('click', '.section-sitemap-generator .add-new-button', function() {    
        sitemapGenerator.createMap();
      });
      
    },
            
    /**    
     * Запускает процесс создания карты в корне сайта
     * @param {type} data - данные для вывода в строке таблицы
     */           
    createMap: function(id) {      
      
      admin.ajaxRequest({
        mguniqueurl: "action/generateSitemap", // действия для выполнения на сервере
        pluginHandler: 'sitemap-generator', // плагин для обработки запроса              
      },
      
      function(response) {
        admin.indication(response.status, response.msg);  
        $('.sitemap-msg').html(response.data.msg);
      }      
      );
    },    

  }
})();

sitemapGenerator.init();
