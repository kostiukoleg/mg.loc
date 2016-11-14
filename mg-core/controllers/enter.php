<?php
class Controllers_Enter extends BaseController {

  function __construct() {

    if (URL::getQueryParametr('logout')) {
      User::logout();
      header('Location: '.$_SERVER['HTTP_REFERER']);
    }

    if (User::isAuth()) {
      header('Location: '.SITE.'/personal');
    }

    $data = array(
      'meta_title' => '',
      'meta_keywords' => !empty($model->currentCategory['meta_keywords'])?$model->currentCategory['meta_keywords']:",,    ",
      'meta_desc' => !empty($model->currentCategory['meta_desc'])?$model->currentCategory['meta_desc']:"       ,    .",
    );

    if (URL::getQueryParametr('unlock')) {
      if (URL::getQueryParametr('unlock') == $_SESSION['unlockCode']) {
        unset($_SESSION['loginAttempt']);
        unset($_SESSION['blockTimeStart']);
        unset($_SESSION['unlockCode']);
      }
    }

    if (!User::isAuth() && (isset($_POST['email']) || isset($_POST['pass']))) {

      $loginAttempt = (int) LOGIN_ATTEMPT?LOGIN_ATTEMPT:5;

      $capcha = (isset($_POST['capcha'])?$_POST['capcha']:false);
      unset($_POST['capcha']);

      if (!User::auth(URL::get('email'), URL::get('pass'), $capcha)) {
        if ($_SESSION['loginAttempt'] < 2) {
          $data['msgError'] = '<span class="msgError">'.
            '  email-!   .'.'</span>';
        } elseif ($_SESSION['loginAttempt'] < $loginAttempt) {
          $data['msgError'] = '<span class="msgError">'.
            '    !   .'.
            '    - '.
            ($loginAttempt - $_SESSION['loginAttempt']).'</span>';
          $data['checkCapcha'] = '<div class="checkCapcha">
            <img style="margin-top: 5px; border: 1px solid gray; background: url("'.
            PATH_TEMPLATE.'/images/cap.png")" src = "captcha.html" width="140" height="36">
            <div>   :<span class="red-star">*</span> </div>
            <input type="text" name="capcha" class="captcha">';
        } else {
          if (!isset($_SESSION['blockTimeStart'])) {  
            //       15 .
            $_SESSION['blockTimeStart'] = time();
            $_SESSION['unlockCode'] = md5('mg'.time());
            $this->sendUnlockMail($_SESSION['unlockCode'],$_POST['email']);
          }
          $data['msgError'] = '<span class="msgError">'.
            '     '.
            '  15 .    '.
            date("H:i:s", $_SESSION['blockTimeStart']).'</span>';
        }
      } else {
        $this->successfulLogon();
      }
    }

    $this->data = $data;
  }

  public function successfulLogon() {       
    
    if (empty($_REQUEST['location']) || 
          $_REQUEST['location'] == SITE.$_SERVER['REQUEST_URI'] || 
          $_REQUEST['location'] == $_SERVER['REQUEST_URI'] ||
          $_REQUEST['location'] == '/mg-admin') {    
 
      header('Location: '.$_SERVER['HTTP_REFERER']);
      exit;
    }
    
    header('Location: '.$_REQUEST['location']);
    exit;
  }

  public function validForm() {
    $email = URL::getQueryParametr('email');
    $pass = URL::getQueryParametr('pass');

    if (!$email || !$pass) {
      //   ,   .
      if (strpos($_SERVER['HTTP_REFERER'], '/enter')) {
        $this->data = array(
          'msgError' => '<span class="msgError">'.'     !'.'</span>',
          'meta_title' => '',
          'meta_keywords' => !empty($model->currentCategory['meta_keywords'])?$model->currentCategory['meta_keywords']:",,    ",
          'meta_desc' => !empty($model->currentCategory['meta_desc'])?$model->currentCategory['meta_desc']:"       ,    .",
        );
      }
      return false;
    }
    return true;
  }

  private function sendUnlockMail($unlockCode,$postEmail) {
    $link = '<a href="'.SITE.'/enter?unlock='.$unlockCode.'" target="blank">'.SITE.'/enter?unlock='.$unlockCode.'</a>';
    $siteName = MG::getOption('sitename');
    
    $paramToMail = array(
      'siteName' => $siteName,
      'link' => $link,
	  'lastEmail' => $postEmail,
    );
    
    $message = MG::layoutManager('email_unclockauth', $paramToMail);
    $emailData = array(
      'nameFrom' => $siteName,
      'emailFrom' => MG::getSetting('noReplyEmail'),
      'nameTo' => '  '.$siteName,
      'emailTo' => MG::getSetting('adminEmail'),
      'subject' => '    '.$siteName.' !',
      'body' => $message,
      'html' => true
    );
    
    if (Mailer::sendMimeMail($emailData)) {
      return true;
    }
    
    return false;
  }

}