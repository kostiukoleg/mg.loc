<?php

/**
 * Модель: Forgotpass
 *
 * Класс Models_Forgotpass реализует логику восстановления пароля пользователей.
 *
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Model
 */
class Models_Forgotpass{

  /**
   * Генерация случайного хэша.
   * @param string $string строка на основе которой готовится хэш.
   * @return string случайный хэш
   */
  public function getHash($string){
    $hash = htmlspecialchars(crypt($string));
    return $hash;
  }

  /**
   * Метод записывает хэш в таблиц пользователей.
   * @param string $email электронный адрес пользователя, для которого записываем хэш.
   * @param string $hash хэш.
   * @return boolean результат выполнения операции.
   */
  public function sendHashToDB($email, $hash){
    if(DB::query('
        UPDATE `'.PREFIX.'user`
        SET `restore` = "%s"
        WHERE email = "%s"
      ', $hash, $email)){
      return true;
    }
    return false;
  }

  /**
   * Отправка письма со ссылкой на восстановление пароля.
   * @param array $emailData массив с передаваемыми данными.
   * @return boolean результат выполнения операции.
   */
  public function sendUrlToEmail($emailData){
    if(Mailer::sendMimeMail($emailData)){
      return true;
    }
    return false;
  }

  /**
   * Активация пользователя по переданному id.
   * @param int $id
   */
  public function activateUser($id){
    $data = array(
      'activity' => 1,
    );
    USER::update($id, $data, 1);
  }

}