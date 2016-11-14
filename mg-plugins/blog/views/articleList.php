<?php
mgSEO($data['category']);
if (!class_exists('Blog')) {
  echo "Плагин не подключен!";
  return false;
}?>
<div class="news-block">
	<div class="news-header">

	</div>
	
	<div class="news-body">  
	  <div class="news-item">		
			<div class="news-details">
				<div class="news-date"><a href="http://moguta.ru/plugins/novostnaya-lenta-s-rss-podpiskoy">Плагин новостей</a> не установлен!</div>			
			</div>
		</div>
		
		<div class="news-item">
			<a href="javascript:void(0);" class="news-img">
			  <img src="<?php echo PATH_SITE_TEMPLATE?>/images/newsico.jpg" alt="">
			</a>
			<div class="news-details">
				<div class="news-date">28 апреля</div>
				<a href="javascript:void(0);" class="news-text">
					Заголовок новости
				</a>
			</div>
		</div>

		<div class="news-item">
			<a href="javascript:void(0);" class="news-img">
				<img src="<?php echo PATH_SITE_TEMPLATE?>/images/newsico.jpg" alt="">
			</a>
			<div class="news-details">
				<div class="news-date">22 апреля</div>
				<a href="javascript:void(0);" class="news-text">
					Заголовок новости
				</a>
			</div>
		</div>

		<div class="news-item">
			<a href="javascript:void(0);" class="news-img">
				<img src="<?php echo PATH_SITE_TEMPLATE?>/images/newsico.jpg" alt="">
			</a>
			<div class="news-details">
				<div class="news-date">18 апреля</div>
				<a href="javascript:void(0);" class="news-text">
					Заголовок новости
				</a>
			</div>
		</div>

		<div class="news-item">
			<a href="javascript:void(0);" class="news-img">
				<img src="<?php echo PATH_SITE_TEMPLATE?>/images/newsico.jpg" alt="">
			</a>
			<div class="news-details">
				<div class="news-date">11 апреля</div>
				<a href="javascript:void(0);" class="news-text">
					Заголовок новости
				</a>
			</div>
		</div>

		<div class="news-item">
			<a href="javascript:void(0);" class="news-img">
				<img src="<?php echo PATH_SITE_TEMPLATE?>/images/newsico.jpg" alt="">
			</a>
			<div class="news-details">
				<div class="news-date">9 апреля</div>
				<a href="javascript:void(0);" class="news-text">
					Заголовок новости
				</a>
			</div>
		</div>
   
	</div>
	<div class="news-footer"> 
    <a href="http://moguta.ru/plugins/novostnaya-lenta-s-rss-podpiskoy" class="show-all">Подключить плагин</a> 
	</div>  
</div>