CREATE TABLE IF NOT EXISTS `mg_blog_items` (
    `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Порядковый номер записи',
    `title` varchar(255) NOT NULL COMMENT 'Заголовок',
    `image_url` varchar(255) NOT NULL COMMENT 'Изображение',
    `date_active_to` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT 'Дата окончания активности',
    `date_active_from` timestamp DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата создания/начала активности',
    `activity` tinyint(4) NOT NULL DEFAULT '1' COMMENT 'Активность элемента',
    `url` varchar(255) NOT NULL COMMENT 'Ссылка',
    `tags` varchar(255) COMMENT 'Тэги',
    `description` longtext NOT NULL COMMENT 'Содержание статьи',
    `meta_title` varchar(255) NOT NULL,
    `meta_keywords` varchar(255) NOT NULL,
    `meta_desc` text NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
	  
      CREATE TABLE IF NOT EXISTS `mg_blog_categories` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `title` varchar(255) NOT NULL,
        `url` varchar(255) NOT NULL COMMENT 'Ссылка на категорию',
        `image_url` varchar(255) NOT NULL COMMENT 'Изображение',
        `description` text NOT NULL COMMENT 'Описание категории',
        `meta_title` varchar(255) NOT NULL,
        `meta_keywords` varchar(255) NOT NULL,
        `meta_desc` text NOT NULL,
        PRIMARY KEY (`id`)
      ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
    
      CREATE TABLE IF NOT EXISTS `mg_blog_item2category` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `item_id` int(11) NOT NULL,
        `category_id` int(11) NOT NULL,
        PRIMARY KEY (`id`)
      ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;