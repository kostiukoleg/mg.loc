<?php
MG::enableTemplate();
MG::titlePage('Faq');
if (class_exists('Faq')) {
  echo Faq::handleShortCode();
} else {
   echo "Плагин вопросов не подключен!";
}
