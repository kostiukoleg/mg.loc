<?php
$arSql[] = 'ALTER TABLE `'.PREFIX.'blog_categories` ADD COLUMN `sort` INT(11);';
$arSql[] = 'UPDATE `'.PREFIX.'blog_categories` SET `sort` = `id`;';
$arSql[] = 'ALTER TABLE `'.PREFIX.'blog_items` ADD COLUMN `tags` varchar(255);';

foreach($arSql as $sql){
  DB::query($sql);
}