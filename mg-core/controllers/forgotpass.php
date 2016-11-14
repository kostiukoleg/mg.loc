<?php

class Controllers_Forgotpass extends BaseController
{
    
    function __construct()
    {
        
        if (User::isAuth()) {
            MG::redirect('/');
        }
        
        $form  = 1;
        $fPass = new Models_Forgotpass;
        
        if (URL::getQueryParametr('forgotpass')) {
            $email = URL::getQueryParametr('email');
            
            if ($userInfo = USER::getUserInfoByEmail($email)) {
                $form    = 0;
                $message = '       <strong>' . $email . '</strong>';
                $hash    = $fPass->getHash($email);
                $fPass->sendHashToDB($email, $hash);
                $siteName = MG::getOption('sitename');
                
                
                $emailMessage = MG::layoutManager('email_forgot', array(
                    'siteName' => $siteName,
                    'email' => $email,
                    'hash' => $hash,
                    'userId' => $userInfo->id,
                    'link' => SITE . '/forgotpass?sec=' . $hash . '&id=' . $userInfo->id
                ));
                
                $emailData = array(
                    'nameFrom' => $siteName,
                    'emailFrom' => MG::getSetting('noReplyEmail'),
                    'nameTo' => '  ' . $siteName,
                    'emailTo' => $email,
                    'subject' => '    ' . $siteName,
                    'body' => $emailMessage,
                    'html' => true
                );
                $fPass->sendUrlToEmail($emailData);
            } else {
                $form  = 0;
                $error = ' ,    <br>
            ,    , ,   .';
            }
        }
        if ($_GET) {
            $userInfo = USER::getUserById(URL::getQueryParametr('id'));
            $hash     = URL::getQueryParametr('sec');
            if ($userInfo->restore == $hash) {
                $form = 2;
                $fPass->sendHashToDB($userInfo->email, $fPass->getHash('0'));
                $_SESSION['id'] = URL::getQueryParametr('id');
            } else {
                $form  = 0;
                $error = ' .     .';
            }
        }
        
        if (URL::getQueryParametr('chengePass')) {
            $form   = 2;
            $person = new Models_Personal;
            $msg    = $person->changePass(URL::getQueryParametr('newPass'), $_SESSION['id'], true);
            if (' ' == $msg) {
                $form    = 0;
                $message = $msg . '! ' . '        <a href="' . SITE . '/enter" >' . SITE . '/enter</a>';
                $fPass->activateUser($_SESSION['id']);
                unset($_SESSION['id']);
            } else {
                $error = $msg;
            }
        }
        
        $this->data = array(
            'error' => $error,
            'message' => $message,
            'form' => $form,
            'meta_title' => ' ',
            'meta_keywords' => $model->currentCategory['meta_keywords'] ? $model->currentCategory['meta_keywords'] : " ,  ,  ",
            'meta_desc' => $model->currentCategory['meta_desc'] ? $model->currentCategory['meta_desc'] : "      ,        ."
        );
    }
    
}